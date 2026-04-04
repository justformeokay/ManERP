<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {}

    public function index()
    {
        $data = $this->dashboardService->getData(auth()->user());

        return view('dashboard.index', $data);
    }

    /**
     * JSON endpoint for Alpine.js polling + period filter.
     */
    public function apiData(Request $request)
    {
        $period = $request->query('period', 'year');

        if ($request->query('chart_only')) {
            return response()->json([
                'profitLossChart' => $this->dashboardService->getProfitLossChartData($period),
            ]);
        }

        $data = $this->dashboardService->getData(auth()->user());
        $data['profitLossChart'] = $this->dashboardService->getProfitLossChartData($period);

        // Convert collections to arrays for JSON
        $data['pendingApprovals']    = $data['pendingApprovals']->toArray();
        $data['activeProjects']      = $data['activeProjects']->toArray();
        $data['activeManufacturing'] = $data['activeManufacturing']->toArray();
        $data['recentSales']         = $data['recentSales']->toArray();
        $data['recentPurchases']     = $data['recentPurchases']->toArray();
        $data['lowStockItems']       = $data['lowStockItems']->toArray();
        $data['recentActivity']      = $data['recentActivity']->toArray();

        return response()->json($data);
    }
}
