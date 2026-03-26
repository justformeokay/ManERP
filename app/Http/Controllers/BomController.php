<?php

namespace App\Http\Controllers;

use App\Http\Requests\BomRequest;
use App\Models\BillOfMaterial;
use App\Models\Product;
use Illuminate\Http\Request;

class BomController extends Controller
{
    public function index(Request $request)
    {
        $boms = BillOfMaterial::query()
            ->with(['product', 'items'])
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
        ]);
    }

    public function store(BomRequest $request)
    {
        $bom = BillOfMaterial::create($request->safe()->except('items'));

        foreach ($request->validated()['items'] as $item) {
            $bom->items()->create($item);
        }

        return redirect()->route('manufacturing.boms.index')->with('success', 'Bill of Materials created successfully.');
    }

    public function show(BillOfMaterial $bom)
    {
        $bom->load(['product', 'items.product']);

        return view('manufacturing.boms.show', compact('bom'));
    }

    public function edit(BillOfMaterial $bom)
    {
        $bom->load('items');

        return view('manufacturing.boms.form', [
            'bom'      => $bom,
            'products' => Product::active()->orderBy('name')->get(),
        ]);
    }

    public function update(BomRequest $request, BillOfMaterial $bom)
    {
        $bom->update($request->safe()->except('items'));

        // Sync items: delete existing, recreate
        $bom->items()->delete();
        foreach ($request->validated()['items'] as $item) {
            $bom->items()->create($item);
        }

        return redirect()->route('manufacturing.boms.index')->with('success', 'Bill of Materials updated successfully.');
    }

    public function destroy(BillOfMaterial $bom)
    {
        $bom->delete();

        return redirect()->route('manufacturing.boms.index')->with('success', 'Bill of Materials deleted successfully.');
    }
}
