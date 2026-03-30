<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesOrderRequest;
use App\Models\Client;
use App\Models\Product;
use App\Models\Project;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    public function __construct(private StockService $stockService) {}

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

        return redirect()->route('sales.show', $order)->with('success', 'Sales order created successfully.');
    }

    public function show(SalesOrder $order)
    {
        $order->load(['client', 'warehouse', 'project', 'creator', 'items.product.inventoryStocks']);

        return view('sales.show', compact('order'));
    }

    public function edit(SalesOrder $order)
    {
        if ($order->status !== 'draft') {
            return redirect()->route('sales.show', $order)
                ->with('error', 'Only draft orders can be edited.');
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
        if ($order->status !== 'draft') {
            return redirect()->route('sales.show', $order)
                ->with('error', 'Only draft orders can be edited.');
        }

        $data = $request->validated();

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

        return redirect()->route('sales.show', $order)->with('success', 'Sales order updated successfully.');
    }

    /**
     * Confirm order: validate stock availability and deduct from inventory.
     */
    public function confirm(SalesOrder $order)
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be confirmed.');
        }

        $order->load('items.product');

        // Pre-check stock availability for all items
        foreach ($order->items as $item) {
            $available = $item->product->inventoryStocks()
                ->where('warehouse_id', $order->warehouse_id)
                ->value('quantity') ?? 0;

            if ($available < $item->quantity) {
                return back()->withErrors([
                    'stock' => "Insufficient stock for {$item->product->name}. Available: {$available}, Required: {$item->quantity}",
                ]);
            }
        }

        // Deduct stock for each item
        foreach ($order->items as $item) {
            $this->stockService->processMovement([
                'product_id'     => $item->product_id,
                'warehouse_id'   => $order->warehouse_id,
                'type'           => 'out',
                'quantity'       => $item->quantity,
                'reference_type' => 'sales_order',
                'reference_id'   => $order->id,
                'notes'          => "Sales order {$order->number}",
            ]);
        }

        $order->update(['status' => 'confirmed']);

        return back()->with('success', 'Order confirmed and stock has been deducted.');
    }

    /**
     * Mark order as delivered (shipped).
     */
    public function deliver(SalesOrder $order)
    {
        if (!in_array($order->status, ['confirmed', 'processing'])) {
            return back()->with('error', 'Only confirmed or processing orders can be delivered.');
        }

        $order->update(['status' => 'shipped']);

        return back()->with('success', 'Order marked as shipped / delivered.');
    }

    /**
     * Mark order as invoiced (completed).
     */
    public function invoice(SalesOrder $order)
    {
        if (!in_array($order->status, ['confirmed', 'shipped'])) {
            return back()->with('error', 'Only confirmed or shipped orders can be invoiced.');
        }

        $order->update(['status' => 'completed']);

        return back()->with('success', 'Order invoiced and marked as completed.');
    }

    /**
     * Cancel an order and restore stock if previously confirmed.
     */
    public function cancel(SalesOrder $order)
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        $wasConfirmed = in_array($order->status, ['confirmed', 'processing', 'shipped']);

        if ($wasConfirmed) {
            $order->load('items');
            foreach ($order->items as $item) {
                $this->stockService->processMovement([
                    'product_id'     => $item->product_id,
                    'warehouse_id'   => $order->warehouse_id,
                    'type'           => 'in',
                    'quantity'       => $item->quantity,
                    'reference_type' => 'sales_order_cancel',
                    'reference_id'   => $order->id,
                    'notes'          => "Stock restored — cancelled {$order->number}",
                ]);
            }
        }

        $order->update(['status' => 'cancelled']);

        $msg = $wasConfirmed
            ? 'Order cancelled and stock has been restored.'
            : 'Order cancelled.';

        return back()->with('success', $msg);
    }

    public function destroy(SalesOrder $order)
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be deleted.');
        }

        $order->items()->delete();
        $order->delete();

        return redirect()->route('sales.index')->with('success', 'Sales order deleted.');
    }
}
