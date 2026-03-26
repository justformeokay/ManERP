<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $movements = StockMovement::query()
            ->with(['product', 'warehouse'])
            ->when($request->input('product_id'), fn($q, $p) => $q->where('product_id', $p))
            ->when($request->input('warehouse_id'), fn($q, $w) => $q->where('warehouse_id', $w))
            ->when($request->input('type'), fn($q, $t) => $q->where('type', $t))
            ->when($request->input('search'), function ($q, $term) {
                $q->where(function ($q) use ($term) {
                    $q->whereHas('product', fn($p) => $p->search($term))
                      ->orWhere('reference_type', 'like', "%{$term}%")
                      ->orWhere('notes', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('inventory.movements.index', compact('movements', 'warehouses'));
    }

    public function create()
    {
        return view('inventory.movements.form', [
            'products'   => Product::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
        ]);
    }

    public function store(StockMovementRequest $request)
    {
        try {
            $this->stockService->processMovement($request->validated());

            return redirect()
                ->route('inventory.movements.index')
                ->with('success', 'Stock movement recorded successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
        }
    }
}
