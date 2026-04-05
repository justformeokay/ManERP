<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManufacturingOrderRequest;
use App\Models\BillOfMaterial;
use App\Models\InventoryStock;
use App\Models\ManufacturingOrder;
use App\Models\Project;
use App\Models\QcInspection;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\ManufacturingOrderCompletedNotification;
use App\Services\CostingService;
use App\Services\StockService;
use App\Services\StockValuationService;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManufacturingOrderController extends Controller
{
    use Auditable;

    protected string $model = 'manufacturing';

    public function __construct(
        private StockService $stockService,
        private CostingService $costingService,
        private StockValuationService $valuationService,
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
     * Pre-checks material availability for the full planned quantity.
     */
    public function confirm(ManufacturingOrder $order)
    {
        $check = $order->requireTransition('confirmed');
        if ($check !== true) {
            return back()->withErrors(['status' => $check]);
        }

        // TUGAS 4: Pre-check material availability on confirm
        $order->load('bom.items.product');
        $ratio = $order->planned_quantity / max(1, $order->bom->output_quantity);
        $warnings = [];

        foreach ($order->bom->items as $item) {
            $requiredQty = round($item->quantity * $ratio, 4);
            $stock = $item->product->inventoryStocks()
                ->where('warehouse_id', $order->warehouse_id)
                ->first();

            $available = $stock ? $stock->availableQuantity() : 0;

            if ($available < $requiredQty) {
                $warnings[] = "{$item->product->name}: available {$available}, required {$requiredQty}";
            }
        }

        // Confirm proceeds with warning (non-blocking) — material may arrive before production
        $oldData = $order->toArray();
        $order->transitionToAndSave('confirmed');
        $this->logAction($order, 'confirm', "Manufacturing order {$order->number} confirmed", $oldData);

        $message = 'Manufacturing order confirmed successfully.';
        if (!empty($warnings)) {
            $message .= ' Warning — insufficient material: ' . implode('; ', $warnings);
        }

        return back()->with('success', $message);
    }

    /**
     * Produce output: consume materials (stock out) and produce finished goods (stock in).
     * Wrapped in DB::transaction for full atomicity.
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

        $order->load('bom.items.product', 'product');
        $ratio = $quantity / max(1, $order->bom->output_quantity);

        // Pre-validate ALL materials using availableQuantity (respects reservations)
        foreach ($order->bom->items as $item) {
            $consumeQty = round($item->quantity * $ratio, 4);
            $stock = InventoryStock::where('product_id', $item->product_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->first();

            $available = $stock ? $stock->availableQuantity() : 0;

            if ($available < $consumeQty) {
                return back()->withErrors([
                    'quantity' => "Insufficient available stock for {$item->product->name}. Available: {$available} (after reservations), Required: {$consumeQty}",
                ]);
            }
        }

        try {
            $totalMaterialCost = 0;

            DB::transaction(function () use ($order, $quantity, $ratio, &$totalMaterialCost) {
                // ── Step 1: Consume raw materials ──
                foreach ($order->bom->items as $item) {
                    $consumeQty = round($item->quantity * $ratio, 4);
                    $product = $item->product;
                    $unitCost = (float) $product->avg_cost;

                    $movement = $this->stockService->processMovement([
                        'product_id'     => $item->product_id,
                        'warehouse_id'   => $order->warehouse_id,
                        'type'           => 'out',
                        'quantity'       => $consumeQty,
                        'unit_cost'      => $unitCost,
                        'reference_type' => 'manufacturing_order',
                        'reference_id'   => $order->id,
                        'notes'          => "Consumed for {$order->number}",
                    ]);

                    $this->valuationService->recordOutgoing(
                        $item->product_id,
                        $order->warehouse_id,
                        $consumeQty,
                        $movement,
                        'manufacturing_order',
                        $order->id,
                        'MO ' . $order->number . ' — consumed ' . ($product->name ?? '')
                    );

                    $totalMaterialCost = bcadd(
                        (string) $totalMaterialCost,
                        bcmul((string) $consumeQty, (string) $unitCost, 4),
                        4
                    );
                }

                // ── Step 1 Journal: Dr WIP (1400) / Cr Raw Materials (1300-RM) ──
                if ($totalMaterialCost > 0) {
                    $this->valuationService->journalMaterialToWip(
                        $order->number . '-WIP-IN',
                        now()->toDateString(),
                        (float) $totalMaterialCost,
                        "Material consumed to WIP — {$order->number}",
                        ManufacturingOrder::class,
                        $order->id
                    );
                }

                // ── Step 2: Produce finished goods ──
                $fgUnitCost = $quantity > 0
                    ? (float) bcdiv((string) $totalMaterialCost, (string) $quantity, 4)
                    : 0;

                $fgMovement = $this->stockService->processMovement([
                    'product_id'     => $order->product_id,
                    'warehouse_id'   => $order->warehouse_id,
                    'type'           => 'in',
                    'quantity'       => $quantity,
                    'unit_cost'      => $fgUnitCost,
                    'reference_type' => 'manufacturing_order',
                    'reference_id'   => $order->id,
                    'notes'          => "Produced from {$order->number}",
                ]);

                $this->valuationService->recordManufacturingIncoming(
                    $order->product_id,
                    $order->warehouse_id,
                    $quantity,
                    (float) $totalMaterialCost,
                    $fgMovement,
                    'manufacturing_order',
                    $order->id,
                    'MO ' . $order->number . ' — produced ' . ($order->product->name ?? '')
                );

                // ── Step 2 Journal: Dr Finished Goods (1300-FG) / Cr WIP (1400) ──
                if ($totalMaterialCost > 0) {
                    $this->valuationService->journalWipToFinishedGoods(
                        $order->number . '-WIP-OUT',
                        now()->toDateString(),
                        (float) $totalMaterialCost,
                        "WIP completed to FG — {$order->number}",
                        ManufacturingOrder::class,
                        $order->id
                    );
                }

                // ── Update order within transaction ──
                $order->produced_quantity += $quantity;

                if (!$order->actual_start) {
                    $order->actual_start = now();
                    if ($order->canTransitionTo('in_progress')) {
                        $order->transitionTo('in_progress');
                    }
                }

                // ── QC gate: block done if any QC inspection failed ──
                if ($order->produced_quantity >= $order->planned_quantity) {
                    $hasFailedQc = QcInspection::where('reference_type', ManufacturingOrder::class)
                        ->where('reference_id', $order->id)
                        ->where('result', 'failed')
                        ->exists();

                    if ($hasFailedQc) {
                        // Do NOT transition to done — keep in_progress until QC resolved
                        $order->save();
                        return;
                    }

                    $order->transitionTo('done');
                    $order->actual_end = now();

                    // Auto-calculate HPP
                    $this->costingService->calculateProductionCost($order);

                    // ── Variance journal ──
                    $this->journalVarianceIfNeeded($order);

                    $order->load('product');
                    $admins = User::where('role', 'admin')->get();
                    foreach ($admins as $admin) {
                        $admin->notify(new ManufacturingOrderCompletedNotification($order));
                    }
                }

                $order->save();
            });

            // ── Auto-create QC draft inspection when first entering in_progress ──
            $order->refresh();
            if ($order->status === 'in_progress') {
                $existingQc = QcInspection::where('reference_type', ManufacturingOrder::class)
                    ->where('reference_id', $order->id)
                    ->exists();

                if (!$existingQc) {
                    QcInspection::create([
                        'inspection_type'    => 'in_process',
                        'reference_type'     => ManufacturingOrder::class,
                        'reference_id'       => $order->id,
                        'product_id'         => $order->product_id,
                        'warehouse_id'       => $order->warehouse_id,
                        'inspected_quantity' => $order->planned_quantity,
                        'passed_quantity'    => 0,
                        'failed_quantity'    => 0,
                        'result'             => 'pending',
                        'status'             => 'draft',
                        'inspector_id'       => auth()->id(),
                    ]);
                }
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        $this->logAction($order, 'produce', "Manufacturing order {$order->number} produced {$quantity} units", []);

        if ($order->status === 'in_progress' && $order->produced_quantity >= $order->planned_quantity) {
            return back()->with('warning', "Produced {$quantity} units. Production complete but QC inspection has failures — resolve QC before order can be marked done.");
        }

        return back()->with('success', "Produced {$quantity} units successfully.");
    }

    /**
     * Journal manufacturing cost variance when MO completes.
     * Positive variance (unfavorable): Dr Manufacturing Variance / Cr WIP
     * Negative variance (favorable):   Dr WIP / Cr Manufacturing Variance
     */
    private function journalVarianceIfNeeded(ManufacturingOrder $order): void
    {
        $variance = $this->costingService->getCostVariance($order);

        if (abs($variance['variance']) < 0.01) {
            return;
        }

        $this->valuationService->journalManufacturingVariance(
            $order->number . '-VAR',
            now()->toDateString(),
            $variance['variance'],
            "Manufacturing variance — {$order->number} ({$variance['variance_pct']}%)",
            ManufacturingOrder::class,
            $order->id
        );
    }

    public function destroy(ManufacturingOrder $order)
    {
        $this->logDelete($order);
        $order->delete();

        return redirect()->route('manufacturing.orders.index')->with('success', 'Manufacturing order deleted successfully.');
    }
}
