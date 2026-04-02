<?php

namespace App\Http\Controllers;

use App\Http\Requests\BomRequest;
use App\Models\BillOfMaterial;
use App\Models\Product;
use App\Services\CostingService;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class BomController extends Controller
{
    use Auditable;

    protected string $model = 'manufacturing';

    public function __construct(protected CostingService $costingService)
    {
    }

    public function index(Request $request)
    {
        $boms = BillOfMaterial::query()
            ->with(['product', 'items', 'parentBom'])
            ->search($request->input('search'))
            ->when($request->has('active'), fn($q) => $q->where('is_active', $request->boolean('active')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('manufacturing.boms.index', compact('boms'));
    }

    public function create()
    {
        return view('manufacturing.boms.form', [
            'bom'      => new BillOfMaterial(['output_quantity' => 1, 'is_active' => true]),
            'products' => Product::active()->orderBy('name')->get(),
            'bomList'  => BillOfMaterial::active()->orderBy('name')->get(),
        ]);
    }

    public function store(BomRequest $request)
    {
        $bom = BillOfMaterial::create($request->safe()->except('items'));

        foreach ($request->validated()['items'] as $item) {
            $product = Product::find($item['product_id']);
            $item['unit_cost'] = $item['unit_cost'] ?? ($product->cost_price ?? 0);
            $item['line_cost'] = $item['unit_cost'] * $item['quantity'];
            $bom->items()->create($item);
        }

        $this->logCreate($bom);

        return redirect()->route('manufacturing.boms.index')
            ->with('success', __('messages.bom_created_success'));
    }

    public function show(BillOfMaterial $bom)
    {
        $bom->load(['product', 'items.product', 'items.subBom', 'parentBom', 'childBoms']);

        $costBreakdown = $this->costingService->calculateBomCost($bom);
        $flattenedMaterials = $bom->getFlattenedMaterials();
        $maxDepth = $bom->getMaxDepth();

        return view('manufacturing.boms.show', compact('bom', 'costBreakdown', 'flattenedMaterials', 'maxDepth'));
    }

    public function edit(BillOfMaterial $bom)
    {
        $bom->load('items');

        return view('manufacturing.boms.form', [
            'bom'      => $bom,
            'products' => Product::active()->orderBy('name')->get(),
            'bomList'  => BillOfMaterial::active()->where('id', '!=', $bom->id)->orderBy('name')->get(),
        ]);
    }

    public function update(BomRequest $request, BillOfMaterial $bom)
    {
        $oldData = $bom->getOriginal();
        $bom->update($request->safe()->except('items'));

        $bom->items()->delete();
        foreach ($request->validated()['items'] as $item) {
            $product = Product::find($item['product_id']);
            $item['unit_cost'] = $item['unit_cost'] ?? ($product->cost_price ?? 0);
            $item['line_cost'] = $item['unit_cost'] * $item['quantity'];
            $bom->items()->create($item);
        }

        $this->logUpdate($bom, $oldData);

        return redirect()->route('manufacturing.boms.index')
            ->with('success', __('messages.bom_updated_success'));
    }

    public function destroy(BillOfMaterial $bom)
    {
        $this->logDelete($bom);
        $bom->delete();

        return redirect()->route('manufacturing.boms.index')
            ->with('success', __('messages.bom_deleted_success'));
    }

    /**
     * Create a new version of an existing BOM.
     */
    public function newVersion(BillOfMaterial $bom)
    {
        $newBom = $bom->createNewVersion();

        return redirect()->route('manufacturing.boms.edit', $newBom)
            ->with('success', __('messages.bom_version_created', ['version' => $newBom->version]));
    }
}
