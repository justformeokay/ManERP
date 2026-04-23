<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseOrderRequest;
use App\Models\Department;
use App\Models\Product;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\PurchaseOrderReceivedNotification;
use App\Services\StockService;
use App\Services\StockValuationService;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    use Auditable;

    protected string $model = 'purchasing';

    public function __construct(
        private StockService $stockService,
        private StockValuationService $valuationService,
    ) {}

    public function index(Request $request)
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier', 'warehouse'])
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('purchase_type'), fn($q, $t) => $q->where('purchase_type', $t))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('purchasing.index', compact('orders'));
    }

    public function create()
    {
        $salesProjects = Project::where('status', 'active')->where('type', 'sales')->orderBy('name')->get();
        $capexProjects = Project::where('status', 'active')->where('type', 'internal_capex')->orderBy('name')->get();

        return view('purchasing.form', [
            'order'         => new PurchaseOrder(['status' => 'draft', 'order_date' => now(), 'purchase_type' => 'operational', 'priority' => 'normal']),
            'suppliers'     => Supplier::active()->orderBy('name')->get(),
            'warehouses'    => Warehouse::active()->orderBy('name')->get(),
            'salesProjects' => $salesProjects,
            'capexProjects' => $capexProjects,
            'departments'   => Department::active()->orderBy('name')->get(),
            'products'      => Product::active()->orderBy('name')->get(),
        ]);
    }

    public function store(PurchaseOrderRequest $request)
    {
        $data = $request->validated();

        // F-14 HMAC verification for project_id
        if (!empty($data['project_id']) && !empty($data['project_sig'])) {
            $expected = PurchaseOrder::projectHmac((int) $data['project_id']);
            if (! hash_equals($expected, $data['project_sig'])) {
                abort(403, 'Financial data integrity check failed (F-14).');
            }
        }

        $order = PurchaseOrder::create([
            'purchase_type' => $data['purchase_type'],
            'supplier_id'   => $data['supplier_id'],
            'warehouse_id'  => $data['warehouse_id'],
            'department_id' => $data['department_id'],
            'project_id'    => $data['project_id'] ?? null,
            'priority'      => $data['priority'],
            'order_date'    => $data['order_date'],
            'expected_date' => $data['expected_date'] ?? null,
            'tax_amount'    => $data['tax_amount'] ?? 0,
            'justification' => $data['justification'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? 'net_30',
            'shipping_address' => $data['shipping_address'] ?? null,
            'status'        => 'draft',
            'created_by'    => auth()->id(),
        ]);

        foreach ($data['items'] as $item) {
            $total = $item['quantity'] * $item['unit_price'];
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total'      => $total,
            ]);
        }

        $order->recalculateTotals();
        $this->logCreate($order);

        return redirect()->route('purchasing.show', $order)->with('success', 'Purchase order created.');
    }

    public function show(PurchaseOrder $order)
    {
        $order->load(['supplier', 'warehouse', 'project', 'department', 'creator', 'items.product', 'purchaseRequest']);

        return view('purchasing.show', compact('order'));
    }

    public function edit(PurchaseOrder $order)
    {
        $check = $order->requireStatus('draft');
        if ($check !== true) {
            return redirect()->route('purchasing.show', $order)->with('error', $check);
        }

        $order->load('items');

        $salesProjects = Project::where('status', 'active')->where('type', 'sales')->orderBy('name')->get();
        $capexProjects = Project::where('status', 'active')->where('type', 'internal_capex')->orderBy('name')->get();

        return view('purchasing.form', [
            'order'         => $order,
            'suppliers'     => Supplier::active()->orderBy('name')->get(),
            'warehouses'    => Warehouse::active()->orderBy('name')->get(),
            'salesProjects' => $salesProjects,
            'capexProjects' => $capexProjects,
            'departments'   => Department::active()->orderBy('name')->get(),
            'products'      => Product::active()->orderBy('name')->get(),
        ]);
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $order)
    {
        $check = $order->requireStatus('draft');
        if ($check !== true) {
            return redirect()->route('purchasing.show', $order)->with('error', $check);
        }

        $data = $request->validated();
        $oldData = $order->getOriginal();

        // F-14 HMAC verification for project_id
        if (!empty($data['project_id']) && !empty($data['project_sig'])) {
            $expected = PurchaseOrder::projectHmac((int) $data['project_id']);
            if (! hash_equals($expected, $data['project_sig'])) {
                abort(403, 'Financial data integrity check failed (F-14).');
            }
        }

        $order->update([
            'purchase_type' => $data['purchase_type'],
            'supplier_id'   => $data['supplier_id'],
            'warehouse_id'  => $data['warehouse_id'],
            'department_id' => $data['department_id'],
            'project_id'    => $data['project_id'] ?? null,
            'priority'      => $data['priority'],
            'order_date'    => $data['order_date'],
            'expected_date' => $data['expected_date'] ?? null,
            'tax_amount'    => $data['tax_amount'] ?? 0,
            'justification' => $data['justification'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? 'net_30',
            'shipping_address' => $data['shipping_address'] ?? null,
        ]);

        $order->items()->delete();

        foreach ($data['items'] as $item) {
            $total = $item['quantity'] * $item['unit_price'];
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total'      => $total,
            ]);
        }

        $order->recalculateTotals();
        $this->logUpdate($order, $oldData);

        return redirect()->route('purchasing.show', $order)->with('success', 'Purchase order updated.');
    }

    /**
     * Confirm a draft PO (no stock movement yet, just status change).
     */
    public function confirm(PurchaseOrder $order)
    {
        $check = $order->requireTransition('confirmed');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $oldData = $order->toArray();
        $order->transitionToAndSave('confirmed');
        $this->logAction($order, 'confirm', "Purchase order {$order->number} confirmed", $oldData);

        return back()->with('success', 'Purchase order confirmed.');
    }

    /**
     * Receive items: stock IN via StockService.
     */
    public function receive(Request $request, PurchaseOrder $order)
    {
        $check = $order->requireStatus(['confirmed', 'partial']);
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $request->validate([
            'receive'              => ['required', 'array', 'min:1'],
            'receive.*.item_id'    => ['required', 'exists:purchase_order_items,id'],
            'receive.*.quantity'   => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($request, $order) {
                $order->load('items');
                $receivedAny = false;
                $totalReceiveValue = 0;

                foreach ($request->input('receive') as $row) {
                    $qty = (float) $row['quantity'];
                    if ($qty <= 0) continue;

                    $item = $order->items->firstWhere('id', $row['item_id']);
                    if (!$item) continue;

                    $remaining = $item->quantity - $item->received_quantity;
                    $qty = min($qty, $remaining);
                    if ($qty <= 0) continue;

                    $unitCost = (float) $item->unit_price;

                    // Stock IN with unit cost
                    $movement = $this->stockService->processMovement([
                        'product_id'     => $item->product_id,
                        'warehouse_id'   => $order->warehouse_id,
                        'type'           => 'in',
                        'quantity'       => $qty,
                        'unit_cost'      => $unitCost,
                        'reference_type' => 'purchase_order',
                        'reference_id'   => $order->id,
                        'notes'          => "Received from {$order->number}",
                    ]);

                    // Record WAC valuation layer
                    $this->valuationService->recordIncoming(
                        $item->product_id,
                        $order->warehouse_id,
                        $qty,
                        $unitCost,
                        $movement,
                        'purchase_order',
                        $order->id,
                        'PO ' . $order->number . ' — ' . ($item->product->name ?? '')
                    );

                    $totalReceiveValue = bcadd((string) $totalReceiveValue, bcmul((string) $qty, (string) $unitCost, 4), 4);

                    $item->increment('received_quantity', $qty);
                    $receivedAny = true;
                }

                if (!$receivedAny) {
                    throw new \RuntimeException('no_items_received');
                }

                // Auto-journal: Dr Inventory / Cr AP
                if ($totalReceiveValue > 0) {
                    $this->valuationService->journalPurchaseReceive(
                        $order->number,
                        now()->toDateString(),
                        (float) $totalReceiveValue,
                        "Goods received — {$order->number}",
                        PurchaseOrder::class,
                        $order->id
                    );
                }

                // Update status
                $order->refresh()->load('items');
                $isFullyReceived = $order->isFullyReceived();
                $order->transitionToAndSave($isFullyReceived ? 'received' : 'partial');
                $this->logAction($order, 'receive', "Purchase order {$order->number} items received", $order->toArray());

                // Notify admin users when fully received
                if ($isFullyReceived) {
                    $order->load('supplier');
                    $admins = User::where('role', 'admin')->get();
                    foreach ($admins as $admin) {
                        $admin->notify(new PurchaseOrderReceivedNotification($order));
                    }
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'no_items_received') {
                return back()->with('error', 'No items were received.');
            }
            throw $e;
        }

        return back()->with('success', 'Items received and stock updated.');
    }

    /**
     * Cancel a purchase order. Reverse stock if items were already received.
     */
    public function cancel(PurchaseOrder $order)
    {
        $check = $order->requireTransition('cancelled');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        // Reverse any partially received stock — all-or-nothing
        DB::transaction(function () use ($order) {
            $order->load('items.product');
            $totalReverseValue = 0;
            foreach ($order->items as $item) {
                if ($item->received_quantity > 0) {
                    $unitCost = (float) $item->unit_price;

                    $movement = $this->stockService->processMovement([
                        'product_id'     => $item->product_id,
                        'warehouse_id'   => $order->warehouse_id,
                        'type'           => 'out',
                        'quantity'       => $item->received_quantity,
                        'unit_cost'      => $unitCost,
                        'reference_type' => 'purchase_order_cancel',
                        'reference_id'   => $order->id,
                        'notes'          => "Stock reversed — cancelled {$order->number}",
                    ]);

                    $this->valuationService->recordPurchaseReturn(
                        $item->product_id,
                        $order->warehouse_id,
                        $item->received_quantity,
                        $unitCost,
                        $movement,
                        'purchase_order_cancel',
                        $order->id,
                        'PO cancel ' . $order->number . ' — ' . ($item->product->name ?? '')
                    );

                    $totalReverseValue = bcadd(
                        (string) $totalReverseValue,
                        bcmul((string) $item->received_quantity, (string) $unitCost, 4),
                        4
                    );

                    $item->update(['received_quantity' => 0]);
                }
            }

            // Auto-journal: Dr AP / Cr Inventory (reverse)
            if ($totalReverseValue > 0) {
                $this->valuationService->journalPurchaseCancel(
                    $order->number . '-CANCEL',
                    now()->toDateString(),
                    (float) $totalReverseValue,
                    "PO cancelled — {$order->number}",
                    PurchaseOrder::class,
                    $order->id
                );
            }

            $order->transitionToAndSave('cancelled');
            $this->logAction($order, 'cancel', "Purchase order {$order->number} cancelled", $order->toArray());
        });

        return back()->with('success', 'Purchase order cancelled.');
    }

    public function destroy(PurchaseOrder $order)
    {
        $check = $order->requireStatus('draft');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $this->logDelete($order);
        $order->items()->delete();
        $order->delete();

        return redirect()->route('purchasing.index')->with('success', 'Purchase order deleted.');
    }
}
