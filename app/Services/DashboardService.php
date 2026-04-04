<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\Client;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\QcInspection;
use App\Models\SalesOrder;
use App\Models\SupplierBill;
use App\Models\User;
use App\Services\CashFlowService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const CACHE_TTL = 900; // 15 minutes

    public function __construct(
        private CashFlowService $cashFlowService,
    ) {}

    /**
     * Get all dashboard data. Cached for 15 minutes.
     */
    public function getData(User $user): array
    {
        // Cache only scalar/aggregate data (safe for database serialization)
        $shared = Cache::remember('dashboard:shared', self::CACHE_TTL, function () {
            return [
                'stats'              => $this->getStats(),
                'salesStats'         => $this->getSalesStats(),
                'arStats'            => $this->getARStats(),
                'apStats'            => $this->getAPStats(),
                'cashOnHand'         => $this->getCashOnHand(),
                'arTrend'            => $this->getARTrend(),
                'apTrend'            => $this->getAPTrend(),
                'profitLossChart'    => $this->getProfitLossChart('year'),
                'inventoryValuation' => $this->getInventoryValuation(),
                'qcRejectionRate'    => $this->getQcRejectionRate(),
                'projectStats'       => $this->getProjectStats(),
                'pendingBadges'      => $this->getPendingBadges(),
            ];
        });

        // Eloquent collections — fetched fresh to avoid database-cache
        // serialization corruption (models with relationships don't
        // survive serialize/unserialize in the database cache driver).
        $shared['activeProjects']      = $this->getActiveProjects();
        $shared['activeManufacturing'] = $this->getActiveManufacturing();
        $shared['recentSales']         = $this->getRecentSales();
        $shared['recentPurchases']     = $this->getRecentPurchases();
        $shared['lowStockItems']       = $this->getLowStockItems();
        $shared['recentActivity']      = $this->getRecentActivity();
        $shared['pendingApprovals']    = $this->getPendingApprovals($user);

        return $shared;
    }

    /**
     * Get P&L chart data for a specific period filter.
     */
    public function getProfitLossChartData(string $period): array
    {
        return Cache::remember("dashboard:pl_chart:{$period}", self::CACHE_TTL, function () use ($period) {
            return $this->getProfitLossChart($period);
        });
    }

    /**
     * Invalidate all dashboard caches.
     */
    public static function clearCache(): void
    {
        Cache::forget('dashboard:shared');
        Cache::forget('dashboard:pl_chart:month');
        Cache::forget('dashboard:pl_chart:quarter');
        Cache::forget('dashboard:pl_chart:year');
    }

    // ══════════════════════════════════════════════════════════
    //  Stats
    // ══════════════════════════════════════════════════════════

    private function getStats(): array
    {
        return [
            'total_clients'        => Client::count(),
            'total_products'       => Product::count(),
            'sales_orders'         => SalesOrder::count(),
            'purchase_orders'      => PurchaseOrder::count(),
            'pending_manufacturing' => ManufacturingOrder::whereIn('status', ['draft', 'confirmed', 'in_progress'])->count(),
            'low_stock_items'      => InventoryStock::join('products', 'inventory_stocks.product_id', '=', 'products.id')
                ->whereRaw('inventory_stocks.quantity <= products.min_stock')
                ->where('products.min_stock', '>', 0)
                ->count(),
        ];
    }

    private function getSalesStats(): array
    {
        return [
            'total_revenue'  => SalesOrder::where('status', 'completed')->sum('total') ?? 0,
            'pending_orders' => SalesOrder::whereIn('status', ['draft', 'confirmed'])->count(),
            'this_month'     => SalesOrder::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total') ?? 0,
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  AR / AP
    // ══════════════════════════════════════════════════════════

    private function getARStats(): array
    {
        return [
            'total_outstanding' => Invoice::whereIn('status', ['unpaid', 'partial'])->sum(DB::raw('total_amount - paid_amount')),
            'overdue_count'     => Invoice::whereIn('status', ['unpaid', 'partial'])->where('due_date', '<', now())->count(),
            'overdue_amount'    => Invoice::whereIn('status', ['unpaid', 'partial'])->where('due_date', '<', now())->sum(DB::raw('total_amount - paid_amount')),
            'due_this_week'     => Invoice::whereIn('status', ['unpaid', 'partial'])->whereBetween('due_date', [now(), now()->addDays(7)])->count(),
        ];
    }

    private function getAPStats(): array
    {
        return [
            'total_outstanding' => SupplierBill::whereIn('status', ['posted', 'partial'])->sum(DB::raw('total - paid_amount')),
            'overdue_count'     => SupplierBill::whereIn('status', ['posted', 'partial'])->where('due_date', '<', now())->count(),
            'overdue_amount'    => SupplierBill::whereIn('status', ['posted', 'partial'])->where('due_date', '<', now())->sum(DB::raw('total - paid_amount')),
            'due_this_week'     => SupplierBill::whereIn('status', ['posted', 'partial'])->whereBetween('due_date', [now(), now()->addDays(7)])->count(),
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  TUGAS 1: P&L Chart (Revenue, COGS, Net Income)
    // ══════════════════════════════════════════════════════════

    private function getProfitLossChart(string $period): array
    {
        // Determine month range based on period
        $monthsBack = match ($period) {
            'month'   => 0,   // Current month only — show weeks
            'quarter' => 2,   // Last 3 months
            default   => 11,  // Year — last 12 months
        };

        if ($period === 'month') {
            return $this->getProfitLossWeekly();
        }

        $labels = [];
        $revenue = [];
        $cogs = [];
        $netIncome = [];

        for ($i = $monthsBack; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth()->toDateString();
            $end   = now()->subMonths($i)->endOfMonth()->toDateString();
            $label = now()->subMonths($i)->translatedFormat('M Y');

            $labels[] = $label;

            $pl = $this->getMonthlyPL($start, $end);
            $revenue[]   = $pl['revenue'];
            $cogs[]      = $pl['cogs'];
            $netIncome[] = $pl['net_income'];
        }

        return compact('labels', 'revenue', 'cogs', 'netIncome');
    }

    private function getProfitLossWeekly(): array
    {
        $labels = [];
        $revenue = [];
        $cogs = [];
        $netIncome = [];

        $monthStart = now()->startOfMonth();
        $weekStart = $monthStart->copy();

        $week = 1;
        while ($weekStart->lte(now())) {
            $weekEnd = $weekStart->copy()->addDays(6);
            if ($weekEnd->gt(now())) $weekEnd = now()->copy();

            $labels[] = "W{$week}";
            $pl = $this->getMonthlyPL($weekStart->toDateString(), $weekEnd->toDateString());
            $revenue[]   = $pl['revenue'];
            $cogs[]      = $pl['cogs'];
            $netIncome[] = $pl['net_income'];

            $weekStart = $weekEnd->copy()->addDay();
            $week++;
        }

        return compact('labels', 'revenue', 'cogs', 'netIncome');
    }

    private function getMonthlyPL(string $start, string $end): array
    {
        $rows = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->whereBetween('journal_entries.date', [$start, $end])
            ->whereIn('chart_of_accounts.type', ['revenue', 'expense'])
            ->select(
                'chart_of_accounts.type',
                'chart_of_accounts.code',
                DB::raw('SUM(journal_items.debit)  as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->groupBy('chart_of_accounts.type', 'chart_of_accounts.code')
            ->get();

        $revenueTotal = 0;
        $cogsTotal = 0;
        $expenseTotal = 0;

        foreach ($rows as $row) {
            if ($row->type === 'revenue') {
                $revenueTotal += (float) $row->total_credit - (float) $row->total_debit;
            } elseif ($row->type === 'expense') {
                $amt = (float) $row->total_debit - (float) $row->total_credit;
                // COGS accounts start with 5xxx
                if (str_starts_with($row->code, '5')) {
                    $cogsTotal += $amt;
                } else {
                    $expenseTotal += $amt;
                }
            }
        }

        return [
            'revenue'    => round($revenueTotal, 2),
            'cogs'       => round($cogsTotal, 2),
            'net_income' => round($revenueTotal - $cogsTotal - $expenseTotal, 2),
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  TUGAS 2: Cash Monitoring
    // ══════════════════════════════════════════════════════════

    private function getCashOnHand(): float
    {
        $row = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->where(function ($q) {
                $q->where('chart_of_accounts.cash_flow_category', 'cash')
                  ->orWhere(function ($q2) {
                      foreach (CashFlowService::CASH_CODES as $code) {
                          $q2->orWhere('chart_of_accounts.code', $code);
                      }
                  });
            })
            ->select(
                DB::raw('SUM(journal_items.debit)  as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->first();

        if (!$row) return 0;
        return round((float) $row->total_debit - (float) $row->total_credit, 2);
    }

    /**
     * AR outstanding trend over last 6 months (end-of-month snapshots).
     */
    private function getARTrend(): array
    {
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $endOfMonth = now()->subMonths($i)->endOfMonth()->toDateString();
            $outstanding = Invoice::whereIn('status', ['unpaid', 'partial'])
                ->where('created_at', '<=', $endOfMonth)
                ->sum(DB::raw('total_amount - paid_amount'));
            $trend[] = round((float) $outstanding, 2);
        }
        return $trend;
    }

    /**
     * AP outstanding trend over last 6 months.
     */
    private function getAPTrend(): array
    {
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $endOfMonth = now()->subMonths($i)->endOfMonth()->toDateString();
            $outstanding = SupplierBill::whereIn('status', ['posted', 'partial'])
                ->where('bill_date', '<=', $endOfMonth)
                ->sum(DB::raw('total - paid_amount'));
            $trend[] = round((float) $outstanding, 2);
        }
        return $trend;
    }

    // ══════════════════════════════════════════════════════════
    //  TUGAS 3: Inventory Valuation & QC
    // ══════════════════════════════════════════════════════════

    private function getInventoryValuation(): array
    {
        $rows = DB::table('inventory_stocks')
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->where('inventory_stocks.quantity', '>', 0)
            ->select(
                'products.type',
                DB::raw('SUM(inventory_stocks.quantity * products.avg_cost) as total_value')
            )
            ->groupBy('products.type')
            ->pluck('total_value', 'type')
            ->toArray();

        return [
            'raw_material'  => round((float) ($rows['raw_material'] ?? 0), 2),
            'finished_good' => round((float) ($rows['finished_good'] ?? 0), 2),
            'consumable'    => round((float) ($rows['consumable'] ?? 0 + ($rows['semi_finished'] ?? 0)), 2),
        ];
    }

    private function getQcRejectionRate(): array
    {
        $stats = QcInspection::where('status', 'completed')
            ->where('inspected_at', '>=', now()->startOfMonth())
            ->select(
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw('SUM(CASE WHEN result = \'failed\' THEN 1 ELSE 0 END) as failed_count'),
                DB::raw('SUM(inspected_quantity) as total_qty'),
                DB::raw('SUM(failed_quantity) as total_failed_qty')
            )
            ->first();

        $totalInspections = (int) ($stats->total_inspections ?? 0);
        $failedCount = (int) ($stats->failed_count ?? 0);
        $totalQty = (float) ($stats->total_qty ?? 0);
        $failedQty = (float) ($stats->total_failed_qty ?? 0);

        $rate = $totalQty > 0 ? round(($failedQty / $totalQty) * 100, 1) : 0;

        return [
            'rate'              => $rate,
            'total_inspections' => $totalInspections,
            'failed_count'      => $failedCount,
            'total_qty'         => round($totalQty, 0),
            'failed_qty'        => round($failedQty, 0),
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  TUGAS 4: Pending Badges
    // ══════════════════════════════════════════════════════════

    private function getPendingBadges(): array
    {
        return [
            'po_pending'  => PurchaseOrder::where('status', 'draft')->count(),
            'mo_pending'  => ManufacturingOrder::where('status', 'draft')->count(),
            'total'       => Approval::pending()->count(),
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  Existing widgets (moved from controller)
    // ══════════════════════════════════════════════════════════

    private function getPendingApprovals(User $user): \Illuminate\Support\Collection
    {
        return Approval::pending()
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
    }

    private function getActiveProjects(): \Illuminate\Support\Collection
    {
        return Project::whereIn('status', ['active', 'on_hold'])
            ->with('client')
            ->latest()
            ->take(5)
            ->get();
    }

    private function getProjectStats(): array
    {
        return [
            'active'               => Project::where('status', 'active')->count(),
            'on_hold'              => Project::where('status', 'on_hold')->count(),
            'completed_this_month' => Project::where('status', 'completed')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
        ];
    }

    private function getActiveManufacturing(): \Illuminate\Support\Collection
    {
        return ManufacturingOrder::whereIn('status', ['confirmed', 'in_progress'])
            ->with(['product', 'bom'])
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->take(5)
            ->get();
    }

    private function getRecentSales(): \Illuminate\Support\Collection
    {
        return SalesOrder::with('client')->latest()->take(5)->get();
    }

    private function getRecentPurchases(): \Illuminate\Support\Collection
    {
        return PurchaseOrder::with('supplier')->latest()->take(5)->get();
    }

    private function getLowStockItems(): \Illuminate\Support\Collection
    {
        return InventoryStock::with('product')
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->whereRaw('inventory_stocks.quantity <= products.min_stock')
            ->where('products.min_stock', '>', 0)
            ->select('inventory_stocks.*')
            ->take(5)
            ->get();
    }

    private function getRecentActivity(): \Illuminate\Support\Collection
    {
        return ActivityLog::with('user')->latest()->take(8)->get();
    }
}
