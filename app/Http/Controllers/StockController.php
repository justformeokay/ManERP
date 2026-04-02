<?php

namespace App\Http\Controllers;

use App\Models\InventoryStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $stocks = InventoryStock::query()
            ->with(['product', 'warehouse'])
            ->when($request->input('warehouse_id'), fn($q, $w) => $q->where('warehouse_id', $w))
            ->when($request->input('search'), function ($q, $term) {
                $q->whereHas('product', fn($p) => $p->search($term));
            })
            ->when($request->input('low_stock'), function ($q) {
                $q->join('products as p', 'p.id', '=', 'inventory_stocks.product_id')
                  ->where('p.min_stock', '>', 0)
                  ->whereRaw('inventory_stocks.quantity <= p.min_stock')
                  ->select('inventory_stocks.*');
            })
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('inventory.stocks.index', compact('stocks', 'warehouses'));
    }

    /**
     * Unified stock hub: levels + movements + transfers in tabs.
     */
    public function hub(Request $request)
    {
        $warehouses = Warehouse::active()->orderBy('name')->get();

        $stocks = InventoryStock::query()
            ->with(['product', 'warehouse'])
            ->when($request->input('warehouse_id'), fn($q, $w) => $q->where('warehouse_id', $w))
            ->when($request->input('search'), fn($q, $t) => $q->whereHas('product', fn($p) => $p->search($t)))
            ->latest('updated_at')
            ->paginate(15, ['*'], 'stock_page')
            ->withQueryString();

        $movements = StockMovement::query()
            ->with(['product', 'warehouse'])
            ->when($request->input('mv_search'), function ($q, $term) {
                $q->whereHas('product', fn($p) => $p->search($term));
            })
            ->when($request->input('mv_type'), fn($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(15, ['*'], 'mv_page')
            ->withQueryString();

        $transfers = StockTransfer::query()
            ->with(['product', 'fromWarehouse', 'toWarehouse'])
            ->when($request->input('tf_search'), function ($q, $term) {
                $q->where('number', 'like', "%{$term}%")
                  ->orWhereHas('product', fn($p) => $p->search($term));
            })
            ->when($request->input('tf_status'), fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15, ['*'], 'tf_page')
            ->withQueryString();

        return view('inventory.stock.index', compact('stocks', 'movements', 'transfers', 'warehouses'));
    }
}
