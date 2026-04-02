<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\ProductionCost;
use App\Services\CostingService;
use Illuminate\Http\Request;

class CostingController extends Controller
{
    public function __construct(protected CostingService $costingService)
    {
    }

    /**
     * HPP Dashboard — list all production costs.
     */
    public function index(Request $request)
    {
        $costs = ProductionCost::query()
            ->with(['manufacturingOrder', 'product'])
            ->when($request->input('search'), function ($q, $term) {
                $q->whereHas('manufacturingOrder', fn($mo) => $mo->where('number', 'like', "%{$term}%"))
                  ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total_production_cost' => ProductionCost::sum('total_cost'),
            'total_material_cost'   => ProductionCost::sum('material_cost'),
            'total_labor_cost'      => ProductionCost::sum('labor_cost'),
            'total_overhead_cost'   => ProductionCost::sum('overhead_cost'),
            'total_orders'          => ProductionCost::count(),
        ];

        return view('manufacturing.costing.index', compact('costs', 'summary'));
    }

    /**
     * Show cost details for a manufacturing order.
     */
    public function show(ManufacturingOrder $order)
    {
        $order->load(['bom.items.product', 'bom.items.subBom', 'product']);

        $productionCost = ProductionCost::where('manufacturing_order_id', $order->id)->first();
        if (!$productionCost) {
            $productionCost = $this->costingService->calculateProductionCost($order);
        }

        $variance = $this->costingService->getCostVariance($order);

        return view('manufacturing.costing.show', compact('order', 'productionCost', 'variance'));
    }

    /**
     * Recalculate production cost for a manufacturing order.
     */
    public function recalculate(ManufacturingOrder $order)
    {
        $this->costingService->calculateProductionCost($order);

        return back()->with('success', __('messages.cost_recalculated'));
    }

    /**
     * BOM cost simulation — calculate cost for a BOM at a given quantity.
     */
    public function simulate(Request $request)
    {
        $request->validate([
            'bom_id'   => 'required|exists:bill_of_materials,id',
            'quantity'  => 'required|numeric|min:0.01',
        ]);

        $bom = BillOfMaterial::with('items.product', 'items.subBom.items.product')->findOrFail($request->bom_id);
        $costBreakdown = $this->costingService->calculateBomCost($bom, $request->quantity);

        $product = $bom->product;
        $laborTotal = ($product->labor_cost ?? 0) * $request->quantity;
        $overheadTotal = ($product->overhead_cost ?? 0) * $request->quantity;
        $grandTotal = $costBreakdown['material_cost'] + $laborTotal + $overheadTotal;

        return view('manufacturing.costing.simulate', [
            'bom'            => $bom,
            'costBreakdown'  => $costBreakdown,
            'laborTotal'     => $laborTotal,
            'overheadTotal'  => $overheadTotal,
            'grandTotal'     => $grandTotal,
            'quantity'        => $request->quantity,
            'boms'           => BillOfMaterial::active()->with('product')->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the simulation form.
     */
    public function simulateForm()
    {
        return view('manufacturing.costing.simulate', [
            'bom'            => null,
            'costBreakdown'  => null,
            'laborTotal'     => 0,
            'overheadTotal'  => 0,
            'grandTotal'     => 0,
            'quantity'        => 1,
            'boms'           => BillOfMaterial::active()->with('product')->orderBy('name')->get(),
        ]);
    }

    /**
     * Update a product's standard cost from its active BOM.
     */
    public function updateStandardCost(Product $product)
    {
        $this->costingService->updateStandardCost($product);

        return back()->with('success', __('messages.standard_cost_updated'));
    }
}
