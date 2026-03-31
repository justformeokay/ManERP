<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\ManufacturingOrder;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SupplierBill;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

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
            'total_revenue' => SalesOrder::where('status', 'completed')->sum('total') ?? 0,
            'pending_orders' => SalesOrder::whereIn('status', ['draft', 'confirmed'])->count(),
            'this_month' => SalesOrder::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total') ?? 0,
        ];

        // Accounts Receivable (AR) - Outstanding invoices
        $arStats = [
            'total_outstanding' => Invoice::whereIn('status', ['unpaid', 'partial'])->sum(DB::raw('total_amount - paid_amount')),
            'overdue_count' => Invoice::whereIn('status', ['unpaid', 'partial'])->where('due_date', '<', now())->count(),
            'overdue_amount' => Invoice::whereIn('status', ['unpaid', 'partial'])->where('due_date', '<', now())->sum(DB::raw('total_amount - paid_amount')),
            'due_this_week' => Invoice::whereIn('status', ['unpaid', 'partial'])->whereBetween('due_date', [now(), now()->addDays(7)])->count(),
        ];

        // Accounts Payable (AP) - Outstanding bills  
        $apStats = [
            'total_outstanding' => SupplierBill::whereIn('status', ['posted', 'partial'])->sum(DB::raw('total - paid_amount')),
            'overdue_count' => SupplierBill::whereIn('status', ['posted', 'partial'])->where('due_date', '<', now())->count(),
            'overdue_amount' => SupplierBill::whereIn('status', ['posted', 'partial'])->where('due_date', '<', now())->sum(DB::raw('total - paid_amount')),
            'due_this_week' => SupplierBill::whereIn('status', ['posted', 'partial'])->whereBetween('due_date', [now(), now()->addDays(7)])->count(),
        ];

        // Monthly revenue trend (last 6 months)
        $monthlyRevenue = SalesOrder::where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month')
            ->toArray();

        // Fill in missing months with 0
        $revenueChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $revenueChart[$month] = (float) ($monthlyRevenue[$month] ?? 0);
        }

        // Monthly expenses trend (last 6 months)
        $monthlyExpenses = SupplierBill::whereIn('status', ['posted', 'partial', 'paid'])
            ->where('bill_date', '>=', now()->subMonths(5)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(bill_date, '%Y-%m') as month"),
                DB::raw('SUM(total) as expense')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('expense', 'month')
            ->toArray();

        $expenseChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $expenseChart[$month] = (float) ($monthlyExpenses[$month] ?? 0);
        }

        // Pending Approvals for current user
        $pendingApprovals = Approval::pending()
            ->whereHas('logs', function ($query) use ($user) {
                $roleIds = $user->getApprovalRoleIds();
                if (!empty($roleIds)) {
                    $query->whereIn('approval_role_id', $roleIds)
                        ->where('action', 'pending')
                        ->whereColumn('step_order', 'approvals.current_step');
                }
            })
            ->with(['flow', 'requester'])
            ->latest()
            ->take(5)
            ->get();

        // Active Projects
        $activeProjects = Project::whereIn('status', ['active', 'on_hold'])
            ->with('client')
            ->latest()
            ->take(5)
            ->get();

        $projectStats = [
            'active' => Project::where('status', 'active')->count(),
            'on_hold' => Project::where('status', 'on_hold')->count(),
            'completed_this_month' => Project::where('status', 'completed')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
        ];

        // Manufacturing in progress
        $activeManufacturing = ManufacturingOrder::whereIn('status', ['confirmed', 'in_progress'])
            ->with(['product', 'bom'])
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->take(5)
            ->get();

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

        // Recent Activity
        $recentActivity = ActivityLog::with('user')
            ->latest()
            ->take(8)
            ->get();

        return view('dashboard.index', compact(
            'stats',
            'salesStats',
            'arStats',
            'apStats',
            'revenueChart',
            'expenseChart',
            'pendingApprovals',
            'activeProjects',
            'projectStats',
            'activeManufacturing',
            'recentSales',
            'recentPurchases',
            'lowStockItems',
            'recentActivity'
        ));
    }
}
