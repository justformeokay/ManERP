<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseOrderRequest;
use App\Models\Product;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\PurchaseOrderReceivedNotification;
use App\Services\StockService;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    use Auditable;

    protected string $model = 'purchasing';

    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier', 'warehouse'])
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('purchasing.index', compact('orders'));
    }

    public function create()
    {
        return view('purchasing.form', [
            'order'      => new PurchaseOrder(['status' => 'draft', 'order_date' => now()]),
            'suppliers'  => Supplier::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'projects'   => Project::where('status', 'active')->orderBy('name')->get(),
            'products'   => Product::active()->orderBy('name')->get(),
        ]);
    }

    public function store(PurchaseOrderRequest $request)
    {
        $data = $request->validated();

        $order = PurchaseOrder::create([
            'supplier_id'  => $data['supplier_id'],
            'warehouse_id' => $data['warehouse_id'],
            'project_id'   => $data['project_id'] ?? null,
            'order_date'   => $data['order_date'],
            'expected_date'=> $data['expected_date'] ?? null,
            'tax_amount'   => $data['tax_amount'] ?? 0,
            'notes'        => $data['notes'] ?? null,
            'status'       => 'draft',
            'created_by'   => auth()->id(),
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
        $order->load(['supplier', 'warehouse', 'project', 'creator', 'items.product']);

        return view('purchasing.show', compact('order'));
    }

    public function edit(PurchaseOrder $order)
    {
        if ($order->status !== 'draft') {
            return redirect()->route('purchasing.show', $order)
                ->with('error', 'Only draft orders can be edited.');
        }

        $order->load('items');

        return view('purchasing.form', [
            'order'      => $order,
            'suppliers'  => Supplier::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'projects'   => Project::where('status', 'active')->orderBy('name')->get(),
            'products'   => Product::active()->orderBy('name')->get(),
        ]);
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $order)
    {
        if ($order->status !== 'draft') {
            return redirect()->route('purchasing.show', $order)
                ->with('error', 'Only draft orders can be edited.');
        }

        $data = $request->validated();
        $oldData = $order->getOriginal();

        $order->update([
            'supplier_id'  => $data['supplier_id'],
            'warehouse_id' => $data['warehouse_id'],
            'project_id'   => $data['project_id'] ?? null,
            'order_date'   => $data['order_date'],
            'expected_date'=> $data['expected_date'] ?? null,
            'tax_amount'   => $data['tax_amount'] ?? 0,
            'notes'        => $data['notes'] ?? null,
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
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be confirmed.');
        }

        $oldData = $order->toArray();
        $order->update(['status' => 'confirmed']);
        $this->logAction($order, 'confirm', "Purchase order {$order->number} confirmed", $oldData);

        return back()->with('success', 'Purchase order confirmed.');
    }

    /**
     * Receive items: stock IN via StockService.
     */
    public function receive(Request $request, PurchaseOrder $order)
    {
        if (!in_array($order->status, ['confirmed', 'partial'])) {
            return back()->with('error', 'Order must be confirmed before receiving.');
        }

        $request->validate([
            'receive'              => ['required', 'array', 'min:1'],
            'receive.*.item_id'    => ['required', 'exists:purchase_order_items,id'],
            'receive.*.quantity'   => ['required', 'numeric', 'min:0'],
        ]);

        $order->load('items');
        $receivedAny = false;

        foreach ($request->input('receive') as $row) {
            $qty = (float) $row['quantity'];
            if ($qty <= 0) continue;

            $item = $order->items->firstWhere('id', $row['item_id']);
            if (!$item) continue;

            $remaining = $item->quantity - $item->received_quantity;
            $qty = min($qty, $remaining);
            if ($qty <= 0) continue;

            // Stock IN
            $this->stockService->processMovement([
                'product_id'     => $item->product_id,
                'warehouse_id'   => $order->warehouse_id,
                'type'           => 'in',
                'quantity'       => $qty,
                'reference_type' => 'purchase_order',
                'reference_id'   => $order->id,
                'notes'          => "Received from {$order->number}",
            ]);

            $item->increment('received_quantity', $qty);
            $receivedAny = true;
        }

        if (!$receivedAny) {
            return back()->with('error', 'No items were received.');
        }

        // Update status
        $oldData = $order->toArray();
        $order->refresh()->load('items');
        $isFullyReceived = $order->isFullyReceived();
        $order->update([
            'status' => $isFullyReceived ? 'received' : 'partial',
        ]);
        $this->logAction($order, 'receive', "Purchase order {$order->number} items received", $oldData);

        // Notify admin users when fully received
        if ($isFullyReceived) {
            $order->load('supplier');
            $admins = User::where('is_admin', true)->get();
            foreach ($admins as $admin) {
                $admin->notify(new PurchaseOrderReceivedNotification($order));
            }
        }

        return back()->with('success', 'Items received and stock updated.');
    }

    /**
     * Cancel a purchase order. Reverse stock if items were already received.
     */
    public function cancel(PurchaseOrder $order)
    {
        if (in_array($order->status, ['received', 'cancelled'])) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        // Reverse any partially received stock
        $order->load('items');
        foreach ($order->items as $item) {
            if ($item->received_quantity > 0) {
                $this->stockService->processMovement([
                    'product_id'     => $item->product_id,
                    'warehouse_id'   => $order->warehouse_id,
                    'type'           => 'out',
                    'quantity'       => $item->received_quantity,
                    'reference_type' => 'purchase_order_cancel',
                    'reference_id'   => $order->id,
                    'notes'          => "Stock reversed — cancelled {$order->number}",
                ]);
                $item->update(['received_quantity' => 0]);
            }
        }

        $oldData = $order->toArray();
        $order->update(['status' => 'cancelled']);
        $this->logAction($order, 'cancel', "Purchase order {$order->number} cancelled", $oldData);

        return back()->with('success', 'Purchase order cancelled.');
    }

    public function destroy(PurchaseOrder $order)
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be deleted.');
        }

        $this->logDelete($order);
        $order->items()->delete();
        $order->delete();

        return redirect()->route('purchasing.index')->with('success', 'Purchase order deleted.');
    }
}
