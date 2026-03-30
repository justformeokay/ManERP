<?php

namespace App\Http\Controllers;

use App\Models\InventoryStock;
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
}
