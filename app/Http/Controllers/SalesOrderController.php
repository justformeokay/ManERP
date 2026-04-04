<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesOrderRequest;
use App\Models\Client;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Project;
use App\Models\SalesOrder;
use App\Models\StockValuationLayer;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\SalesOrderConfirmedNotification;
use App\Services\StockService;
use App\Services\StockValuationService;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    use Auditable;

    protected string $model = 'sales';

    public function __construct(
        private StockService $stockService,
        private StockValuationService $valuationService,
    ) {}

    public function index(Request $request)
    {
        $orders = SalesOrder::query()
            ->with(['client', 'warehouse'])
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', compact('orders'));
    }

    public function create()
    {
        return view('sales.form', [
            'order'      => new SalesOrder(['status' => 'draft', 'order_date' => now()]),
            'clients'    => Client::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'projects'   => Project::where('status', 'active')->orderBy('name')->get(),
            'products'   => Product::active()->with('inventoryStocks')->orderBy('name')->get(),
        ]);
    }

    public function store(SalesOrderRequest $request)
    {
        $data = $request->validated();

        $order = SalesOrder::create([
            'client_id'    => $data['client_id'],
            'warehouse_id' => $data['warehouse_id'],
            'project_id'   => $data['project_id'] ?? null,
            'order_date'   => $data['order_date'],
            'delivery_date'=> $data['delivery_date'] ?? null,
            'tax_amount'   => $data['tax_amount'] ?? 0,
            'discount'     => $data['discount'] ?? 0,
            'notes'        => $data['notes'] ?? null,
            'status'       => 'draft',
            'created_by'   => auth()->id(),
        ]);

        foreach ($data['items'] as $item) {
            $itemDiscount = $item['discount'] ?? 0;
            $total = ($item['quantity'] * $item['unit_price']) - $itemDiscount;

            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount'   => $itemDiscount,
                'total'      => $total,
            ]);
        }

        $order->recalculateTotals();
        $this->logCreate($order);

        return redirect()->route('sales.show', $order)->with('success', 'Sales order created successfully.');
    }

    public function show(SalesOrder $order)
    {
        $order->load(['client', 'warehouse', 'project', 'creator', 'items.product.inventoryStocks']);

        return view('sales.show', compact('order'));
    }

    public function edit(SalesOrder $order)
    {
        $check = $order->requireStatus('draft');
        if ($check !== true) {
            return redirect()->route('sales.show', $order)->with('error', $check);
        }

        $order->load('items');

        return view('sales.form', [
            'order'      => $order,
            'clients'    => Client::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'projects'   => Project::where('status', 'active')->orderBy('name')->get(),
            'products'   => Product::active()->with('inventoryStocks')->orderBy('name')->get(),
        ]);
    }

    public function update(SalesOrderRequest $request, SalesOrder $order)
    {
        $check = $order->requireStatus('draft');
        if ($check !== true) {
            return redirect()->route('sales.show', $order)->with('error', $check);
        }

        $data = $request->validated();
        $oldData = $order->getOriginal();

        $order->update([
            'client_id'    => $data['client_id'],
            'warehouse_id' => $data['warehouse_id'],
            'project_id'   => $data['project_id'] ?? null,
            'order_date'   => $data['order_date'],
            'delivery_date'=> $data['delivery_date'] ?? null,
            'tax_amount'   => $data['tax_amount'] ?? 0,
            'discount'     => $data['discount'] ?? 0,
            'notes'        => $data['notes'] ?? null,
        ]);

        // Rebuild items
        $order->items()->delete();

        foreach ($data['items'] as $item) {
            $itemDiscount = $item['discount'] ?? 0;
            $total = ($item['quantity'] * $item['unit_price']) - $itemDiscount;

            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount'   => $itemDiscount,
                'total'      => $total,
            ]);
        }

        $order->recalculateTotals();
        $this->logUpdate($order, $oldData);

        return redirect()->route('sales.show', $order)->with('success', 'Sales order updated successfully.');
    }

    /**
     * Confirm order: validate available stock and reserve quantities.
     */
    public function confirm(SalesOrder $order)
    {
        $check = $order->requireTransition('confirmed');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $order->load('items.product');

        // Pre-check available stock (respecting existing reservations)
        foreach ($order->items as $item) {
            $stock = $item->product->inventoryStocks()
                ->where('warehouse_id', $order->warehouse_id)
                ->first();

            $available = $stock ? $stock->availableQuantity() : 0;

            if ($available < $item->quantity) {
                return back()->withErrors([
                    'stock' => "Insufficient stock for {$item->product->name}. Available: {$available}, Required: {$item->quantity}",
                ]);
            }
        }

        // Reserve stock for each item (soft lock)
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $stock = InventoryStock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->increment('reserved_quantity', $item->quantity);
                }
            }
        });

        $oldData = $order->toArray();
        $order->transitionToAndSave('confirmed');
        $this->logAction($order, 'confirm', "Sales order {$order->number} confirmed", $oldData);

        // Notify admin users
        $order->load('client');
        $admins = User::where('is_admin', true)->get();
        foreach ($admins as $admin) {
            $admin->notify(new SalesOrderConfirmedNotification($order));
        }

        return back()->with('success', 'Order confirmed and stock has been reserved.');
    }

    /**
     * Deliver order: deduct stock, record COGS, and release reservations.
     */
    public function deliver(SalesOrder $order)
    {
        $check = $order->requireTransition('shipped');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $order->load('items.product');

        $totalCogs = 0;

        DB::transaction(function () use ($order, &$totalCogs) {
            foreach ($order->items as $item) {
                $product = $item->product;
                $unitCost = (float) $product->avg_cost;

                // Actual stock OUT
                $movement = $this->stockService->processMovement([
                    'product_id'     => $item->product_id,
                    'warehouse_id'   => $order->warehouse_id,
                    'type'           => 'out',
                    'quantity'       => $item->quantity,
                    'unit_cost'      => $unitCost,
                    'reference_type' => 'sales_order',
                    'reference_id'   => $order->id,
                    'notes'          => "Delivered — {$order->number}",
                ]);

                // Record WAC outgoing layer
                $this->valuationService->recordOutgoing(
                    $item->product_id,
                    $order->warehouse_id,
                    $item->quantity,
                    $movement,
                    'sales_order',
                    $order->id,
                    'SO ' . $order->number . ' — ' . ($product->name ?? '')
                );

                $totalCogs = bcadd((string) $totalCogs, bcmul((string) $item->quantity, (string) $unitCost, 4), 4);

                // Release reservation
                $stock = InventoryStock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock && $stock->reserved_quantity > 0) {
                    $release = min((float) $stock->reserved_quantity, $item->quantity);
                    $stock->decrement('reserved_quantity', $release);
                }
            }
        });

        // Auto-journal: Dr COGS / Cr Inventory
        if ($totalCogs > 0) {
            $this->valuationService->journalSalesCogs(
                $order->number . '-COGS',
                now()->toDateString(),
                (float) $totalCogs,
                "COGS — {$order->number}"
            );
        }

        $oldData = $order->toArray();
        $order->transitionToAndSave('shipped');
        $this->logAction($order, 'deliver', "Sales order {$order->number} delivered", $oldData);

        return back()->with('success', 'Order delivered and stock has been deducted.');
    }

    /**
     * Redirect to create invoice from this sales order.
     */
    public function invoice(SalesOrder $order)
    {
        if (! $order->canTransitionTo('shipped') && ! in_array($order->status, ['confirmed', 'shipped'])) {
            return back()->with('error', 'Only confirmed or shipped orders can be invoiced.');
        }

        if ($order->invoices()->exists()) {
            return redirect()->route('finance.invoices.show', $order->invoices()->first())
                ->with('info', 'Invoice already exists for this order.');
        }

        return redirect()->route('finance.invoices.create', ['sales_order' => $order->id]);
    }

    /**
     * Cancel an order: release reservations if confirmed, restore stock if shipped.
     */
    public function cancel(SalesOrder $order)
    {
        $check = $order->requireTransition('cancelled');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $wasShipped = in_array($order->status, ['shipped']);
        $wasReserved = in_array($order->status, ['confirmed', 'processing', 'partial']);

        $order->load('items.product');

        if ($wasReserved) {
            // Release reservations only (no stock was deducted yet)
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    $stock = InventoryStock::where('product_id', $item->product_id)
                        ->where('warehouse_id', $order->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if ($stock && $stock->reserved_quantity > 0) {
                        $release = min((float) $stock->reserved_quantity, $item->quantity);
                        $stock->decrement('reserved_quantity', $release);
                    }
                }
            });
        }

        if ($wasShipped) {
            // Stock was deducted on delivery — restore using original unit_cost
            $totalReverseCogs = 0;
            foreach ($order->items as $item) {
                $product = $item->product;

                // Find original outgoing valuation layer to get the original unit_cost
                $originalLayer = StockValuationLayer::where('reference_type', 'sales_order')
                    ->where('reference_id', $order->id)
                    ->where('product_id', $item->product_id)
                    ->where('direction', 'out')
                    ->first();

                $unitCost = $originalLayer ? (float) $originalLayer->unit_cost : (float) $product->avg_cost;

                $movement = $this->stockService->processMovement([
                    'product_id'     => $item->product_id,
                    'warehouse_id'   => $order->warehouse_id,
                    'type'           => 'in',
                    'quantity'       => $item->quantity,
                    'unit_cost'      => $unitCost,
                    'reference_type' => 'sales_order_cancel',
                    'reference_id'   => $order->id,
                    'notes'          => "Stock restored — cancelled {$order->number}",
                ]);

                // Record WAC incoming layer at ORIGINAL sale cost (not current avg)
                $this->valuationService->recordIncoming(
                    $item->product_id,
                    $order->warehouse_id,
                    $item->quantity,
                    $unitCost,
                    $movement,
                    'sales_order_cancel',
                    $order->id,
                    'SO cancel ' . $order->number . ' — ' . ($product->name ?? '')
                );

                $totalReverseCogs = bcadd(
                    (string) $totalReverseCogs,
                    bcmul((string) $item->quantity, (string) $unitCost, 4),
                    4
                );
            }

            // Auto-journal: Dr Inventory / Cr COGS (reverse)
            if ($totalReverseCogs > 0) {
                $this->valuationService->journalSalesCancel(
                    $order->number . '-COGS-REV',
                    now()->toDateString(),
                    (float) $totalReverseCogs,
                    "COGS reversal — {$order->number}"
                );
            }
        }

        $oldData = $order->toArray();
        $order->transitionToAndSave('cancelled');
        $this->logAction($order, 'cancel', "Sales order {$order->number} cancelled", $oldData);

        $msg = $wasShipped
            ? 'Order cancelled and stock has been restored.'
            : ($wasReserved ? 'Order cancelled and reservations released.' : 'Order cancelled.');

        return back()->with('success', $msg);
    }

    public function destroy(SalesOrder $order)
    {
        $check = $order->requireStatus('draft');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $this->logDelete($order);
        $order->items()->delete();
        $order->delete();

        return redirect()->route('sales.index')->with('success', 'Sales order deleted.');
    }
}
