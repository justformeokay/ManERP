<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManufacturingOrderRequest;
use App\Models\BillOfMaterial;
use App\Models\ManufacturingOrder;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\ManufacturingOrderCompletedNotification;
use App\Services\CostingService;
use App\Services\StockService;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class ManufacturingOrderController extends Controller
{
    use Auditable;

    protected string $model = 'manufacturing';

    public function __construct(
        private StockService $stockService,
        private CostingService $costingService,
    ) {}

    public function index(Request $request)
    {
        $orders = ManufacturingOrder::query()
            ->with(['product', 'bom', 'warehouse'])
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('priority'), fn($q, $p) => $q->where('priority', $p))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('manufacturing.orders.index', compact('orders'));
    }

    public function create()
    {
        return view('manufacturing.orders.form', [
            'order'      => new ManufacturingOrder(['status' => 'draft', 'priority' => 'normal']),
            'boms'       => BillOfMaterial::active()->with('product')->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'projects'   => Project::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(ManufacturingOrderRequest $request)
    {
        $data = $request->validated();
        $bom = BillOfMaterial::findOrFail($data['bom_id']);
        $data['product_id'] = $bom->product_id;
        $data['created_by'] = auth()->id();

        $order = ManufacturingOrder::create($data);
        $this->logCreate($order);

        return redirect()->route('manufacturing.orders.index')->with('success', 'Manufacturing order created successfully.');
    }

    public function show(ManufacturingOrder $order)
    {
        $order->load(['product', 'bom.items.product', 'warehouse', 'project', 'creator']);

        return view('manufacturing.orders.show', compact('order'));
    }

    public function edit(ManufacturingOrder $order)
    {
        return view('manufacturing.orders.form', [
            'order'      => $order,
            'boms'       => BillOfMaterial::active()->with('product')->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'projects'   => Project::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(ManufacturingOrderRequest $request, ManufacturingOrder $order)
    {
        $data = $request->validated();
        $bom = BillOfMaterial::findOrFail($data['bom_id']);
        $data['product_id'] = $bom->product_id;
        $oldData = $order->getOriginal();

        $order->update($data);
        $this->logUpdate($order, $oldData);

        return redirect()->route('manufacturing.orders.index')->with('success', 'Manufacturing order updated successfully.');
    }

    /**
     * Confirm a draft manufacturing order.
     */
    public function confirm(ManufacturingOrder $order)
    {
        $check = $order->requireTransition('confirmed');
        if ($check !== true) {
            return back()->withErrors(['status' => $check]);
        }

        $oldData = $order->toArray();
        $order->transitionToAndSave('confirmed');
        $this->logAction($order, 'confirm', "Manufacturing order {$order->number} confirmed", $oldData);

        return back()->with('success', 'Manufacturing order confirmed successfully.');
    }

    /**
     * Produce output: consume materials (stock out) and produce finished goods (stock in).
     */
    public function produce(Request $request, ManufacturingOrder $order)
    {
        $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $quantity = $request->input('quantity');
        $remaining = $order->planned_quantity - $order->produced_quantity;

        if ($quantity > $remaining) {
            return back()->withErrors(['quantity' => "Cannot produce more than remaining ({$remaining})."]);
        }

        $order->load('bom.items');
        $ratio = $quantity / max(1, $order->bom->output_quantity);

        // Pre-validate ALL materials before consuming any
        foreach ($order->bom->items as $item) {
            $consumeQty = round($item->quantity * $ratio, 4);
            $available = $item->product->inventoryStocks()
                ->where('warehouse_id', $order->warehouse_id)
                ->value('quantity') ?? 0;

            if ($available < $consumeQty) {
                return back()->withErrors([
                    'quantity' => "Insufficient stock for {$item->product->name}. Available: {$available}, Required: {$consumeQty}",
                ]);
            }
        }

        try {
            // Consume raw materials
            foreach ($order->bom->items as $item) {
                $consumeQty = round($item->quantity * $ratio, 4);
                $this->stockService->processMovement([
                    'product_id'     => $item->product_id,
                    'warehouse_id'   => $order->warehouse_id,
                    'type'           => 'out',
                    'quantity'       => $consumeQty,
                    'reference_type' => 'manufacturing_order',
                    'reference_id'   => $order->id,
                    'notes'          => "Consumed for {$order->number}",
                ]);
            }

            // Produce finished goods
            $this->stockService->processMovement([
                'product_id'     => $order->product_id,
                'warehouse_id'   => $order->warehouse_id,
                'type'           => 'in',
                'quantity'       => $quantity,
                'reference_type' => 'manufacturing_order',
                'reference_id'   => $order->id,
                'notes'          => "Produced from {$order->number}",
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        // Update order
        $order->produced_quantity += $quantity;

        if (!$order->actual_start) {
            $order->actual_start = now();
            if ($order->canTransitionTo('in_progress')) {
                $order->transitionTo('in_progress');
            }
        }

        if ($order->produced_quantity >= $order->planned_quantity) {
            $order->transitionTo('done');
            $order->actual_end = now();

            // Auto-calculate HPP (production cost)
            $this->costingService->calculateProductionCost($order);
            
            // Notify admin users when manufacturing order is completed
            $order->load('product');
            $admins = User::where('is_admin', true)->get();
            foreach ($admins as $admin) {
                $admin->notify(new ManufacturingOrderCompletedNotification($order));
            }
        }

        $oldData = $order->toArray();
        $order->save();
        $this->logAction($order, 'produce', "Manufacturing order {$order->number} produced {$quantity} units", $oldData);

        return back()->with('success', "Produced {$quantity} units successfully.");
    }

    public function destroy(ManufacturingOrder $order)
    {
        $this->logDelete($order);
        $order->delete();

        return redirect()->route('manufacturing.orders.index')->with('success', 'Manufacturing order deleted successfully.');
    }
}
