<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\ManufacturingOrder;
use App\Models\InventoryStock;

class DashboardController extends Controller
{
    public function index()
    {
        // Summary stats
        $stats = [
            'total_clients' => Client::count(),
            'total_products' => Product::count(),
            'sales_orders' => SalesOrder::count(),
            'purchase_orders' => PurchaseOrder::count(),
            'pending_manufacturing' => ManufacturingOrder::whereIn('status', ['draft', 'confirmed', 'in_progress'])->count(),
            'low_stock_items' => InventoryStock::join('products', 'inventory_stocks.product_id', '=', 'products.id')
                ->whereRaw('inventory_stocks.quantity <= products.min_stock')
                ->where('products.min_stock', '>', 0)
                ->count(),
        ];

        // Sales stats
        $salesStats = [
            'total_revenue' => SalesOrder::where('status', 'invoiced')->sum('total') ?? 0,
            'pending_orders' => SalesOrder::whereIn('status', ['draft', 'confirmed'])->count(),
            'this_month' => SalesOrder::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total') ?? 0,
        ];

        // Recent orders
        $recentSales = SalesOrder::with('client')
            ->latest()
            ->take(5)
            ->get();

        $recentPurchases = PurchaseOrder::with('supplier')
            ->latest()
            ->take(5)
            ->get();

        // Low stock items
        $lowStockItems = InventoryStock::with('product')
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->whereRaw('inventory_stocks.quantity <= products.min_stock')
            ->where('products.min_stock', '>', 0)
            ->select('inventory_stocks.*')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'stats',
            'salesStats',
            'recentSales',
            'recentPurchases',
            'lowStockItems'
        ));
    }
}
