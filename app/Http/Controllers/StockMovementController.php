<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockMovementRequest;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Services\StockValuationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    public function __construct(
        private StockService $stockService,
        private StockValuationService $valuationService,
    ) {}

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
            $data = $request->validated();

            if ($data['type'] === 'adjustment') {
                // Wrap in transaction to get consistent old qty for valuation layer
                $movement = DB::transaction(function () use ($data) {
                    $stock = InventoryStock::where('product_id', $data['product_id'])
                        ->where('warehouse_id', $data['warehouse_id'])
                        ->lockForUpdate()
                        ->first();

                    $oldQty = $stock ? (float) $stock->quantity : 0;

                    $movement = $this->stockService->processMovement($data);

                    $delta = (float) $data['quantity'] - $oldQty;

                    if (abs($delta) > 0.001) {
                        $product = Product::find($data['product_id']);
                        $avgCost = (float) $product->avg_cost;

                        if ($delta > 0) {
                            $this->valuationService->recordIncoming(
                                $data['product_id'],
                                $data['warehouse_id'],
                                abs($delta),
                                $avgCost,
                                $movement,
                                'stock_adjustment',
                                $movement->id,
                                'Stock adjustment (increase)'
                            );
                        } else {
                            $this->valuationService->recordOutgoing(
                                $data['product_id'],
                                $data['warehouse_id'],
                                abs($delta),
                                $movement,
                                'stock_adjustment',
                                $movement->id,
                                'Stock adjustment (decrease)'
                            );
                        }
                    }

                    return $movement;
                });
            } else {
                $movement = $this->stockService->processMovement($data);
            }

            audit_log('inventory', $data['type'] === 'in' ? 'create' : 'transfer', "Manual stock {$data['type']} for product #{$data['product_id']}", null, $data);

            return redirect()
                ->route('inventory.movements.index')
                ->with('success', 'Stock movement recorded successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
        }
    }
}
