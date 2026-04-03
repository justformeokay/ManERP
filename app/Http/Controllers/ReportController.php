<?php

namespace App\Http\Controllers;

use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\ManufacturingOrder;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $range = $this->resolveDateRange($request);

        // Summary cards
        $totalSales = SalesOrder::whereBetween('order_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->sum('total');

        $totalPurchases = PurchaseOrder::whereBetween('order_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->sum('total');

        $totalProducts = Product::count();

        $totalOrders = SalesOrder::whereBetween('order_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->count()
            + PurchaseOrder::whereBetween('order_date', $range)
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->count();

        // Sales last 7 days
        $salesTrend = SalesOrder::select(
                DB::raw('DATE(order_date) as date'),
                DB::raw('SUM(total) as total')
            )
            ->where('order_date', '>=', now()->subDays(6)->startOfDay())
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $salesDays = collect();
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $salesDays[$d] = (float) ($salesTrend[$d] ?? 0);
        }

        // Purchases last 6 months
        $purchaseTrend = PurchaseOrder::select(
                DB::raw("DATE_FORMAT(order_date, '%Y-%m') as month"),
                DB::raw('SUM(total) as total')
            )
            ->where('order_date', '>=', now()->subMonths(5)->startOfMonth())
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $purchaseMonths = collect();
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $purchaseMonths[$m] = (float) ($purchaseTrend[$m] ?? 0);
        }

        // Top selling products
        $topProducts = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('products', 'products.id', '=', 'sales_order_items.product_id')
            ->whereNotIn('sales_orders.status', ['draft', 'cancelled'])
            ->whereBetween('sales_orders.order_date', $range)
            ->select(
                'products.name',
                'products.sku',
                DB::raw('SUM(sales_order_items.quantity) as total_qty'),
                DB::raw('SUM(sales_order_items.total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        // Low stock items
        $lowStockItems = DB::table('inventory_stocks')
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'inventory_stocks.warehouse_id')
            ->select(
                'products.name',
                'products.sku',
                'products.min_stock',
                'warehouses.name as warehouse',
                'inventory_stocks.quantity'
            )
            ->whereColumn('inventory_stocks.quantity', '<=', 'products.min_stock')
            ->where('products.is_active', true)
            ->orderBy('inventory_stocks.quantity')
            ->limit(10)
            ->get();

        return view('reports.index', compact(
            'totalSales', 'totalPurchases', 'totalProducts', 'totalOrders',
            'salesDays', 'purchaseMonths', 'topProducts', 'lowStockItems'
        ));
    }

    public function salesReport(Request $request)
    {
        $range = $this->resolveDateRange($request);

        $summary = SalesOrder::whereBetween('order_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->selectRaw('COUNT(*) as count, SUM(total) as total, AVG(total) as avg_total')
            ->first();

        $byStatus = SalesOrder::whereBetween('order_date', $range)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $dailySales = SalesOrder::select(
                DB::raw('DATE(order_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
            ->whereBetween('order_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topProducts = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('products', 'products.id', '=', 'sales_order_items.product_id')
            ->whereNotIn('sales_orders.status', ['draft', 'cancelled'])
            ->whereBetween('sales_orders.order_date', $range)
            ->select(
                'products.name',
                'products.sku',
                DB::raw('SUM(sales_order_items.quantity) as total_qty'),
                DB::raw('SUM(sales_order_items.total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        $topClients = DB::table('sales_orders')
            ->join('clients', 'clients.id', '=', 'sales_orders.client_id')
            ->whereNotIn('sales_orders.status', ['draft', 'cancelled'])
            ->whereBetween('sales_orders.order_date', $range)
            ->select(
                'clients.name',
                'clients.company',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(sales_orders.total) as total_revenue')
            )
            ->groupBy('clients.id', 'clients.name', 'clients.company')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        return view('reports.sales', compact(
            'summary', 'byStatus', 'dailySales', 'topProducts', 'topClients'
        ));
    }

    public function purchasingReport(Request $request)
    {
        $range = $this->resolveDateRange($request);

        $summary = PurchaseOrder::whereBetween('order_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->selectRaw('COUNT(*) as count, SUM(total) as total, AVG(total) as avg_total')
            ->first();

        $byStatus = PurchaseOrder::whereBetween('order_date', $range)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $monthlySeries = PurchaseOrder::select(
                DB::raw("DATE_FORMAT(order_date, '%Y-%m') as month"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
            ->whereBetween('order_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $topSuppliers = DB::table('purchase_orders')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->whereNotIn('purchase_orders.status', ['draft', 'cancelled'])
            ->whereBetween('purchase_orders.order_date', $range)
            ->select(
                'suppliers.name',
                'suppliers.company',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(purchase_orders.total) as total_spent')
            )
            ->groupBy('suppliers.id', 'suppliers.name', 'suppliers.company')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        return view('reports.purchasing', compact(
            'summary', 'byStatus', 'monthlySeries', 'topSuppliers'
        ));
    }

    public function inventoryReport()
    {
        $totalProducts = Product::where('is_active', true)->count();
        $totalStock = InventoryStock::sum('quantity');

        $byType = Product::where('is_active', true)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get();

        $lowStockItems = DB::table('inventory_stocks')
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'inventory_stocks.warehouse_id')
            ->select(
                'products.name',
                'products.sku',
                'products.min_stock',
                'warehouses.name as warehouse',
                'inventory_stocks.quantity'
            )
            ->whereColumn('inventory_stocks.quantity', '<=', 'products.min_stock')
            ->where('products.is_active', true)
            ->orderBy('inventory_stocks.quantity')
            ->get();

        $mostStocked = DB::table('inventory_stocks')
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->select(
                'products.name',
                'products.sku',
                DB::raw('SUM(inventory_stocks.quantity) as total_quantity')
            )
            ->where('products.is_active', true)
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        return view('reports.inventory', compact(
            'totalProducts', 'totalStock', 'byType', 'lowStockItems', 'mostStocked'
        ));
    }

    public function manufacturingReport(Request $request)
    {
        $range = $this->resolveDateRange($request);

        $totalOrders = ManufacturingOrder::whereBetween('created_at', $range)->count();

        $byStatus = ManufacturingOrder::whereBetween('created_at', $range)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $productionByProduct = DB::table('manufacturing_orders')
            ->join('products', 'products.id', '=', 'manufacturing_orders.product_id')
            ->whereBetween('manufacturing_orders.created_at', $range)
            ->where('manufacturing_orders.status', '!=', 'cancelled')
            ->select(
                'products.name',
                'products.sku',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(manufacturing_orders.planned_quantity) as planned'),
                DB::raw('SUM(manufacturing_orders.produced_quantity) as produced')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('produced')
            ->limit(10)
            ->get();

        return view('reports.manufacturing', compact(
            'totalOrders', 'byStatus', 'productionByProduct'
        ));
    }

    public function financeReport(Request $request)
    {
        $range = $this->resolveDateRange($request);

        $totalRevenue = Invoice::whereBetween('invoice_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->sum('total_amount');

        $totalPaid = Invoice::whereBetween('invoice_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->sum('paid_amount');

        $totalOutstanding = $totalRevenue - $totalPaid;

        $invoiceCount = Invoice::whereBetween('invoice_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->count();

        $byStatus = Invoice::whereBetween('invoice_date', $range)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $recentPayments = Payment::with(['invoice.client'])
            ->whereBetween('payment_date', $range)
            ->orderByDesc('payment_date')
            ->limit(15)
            ->get();

        $topClients = DB::table('invoices')
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->whereNotIn('invoices.status', ['draft', 'cancelled'])
            ->whereBetween('invoices.invoice_date', $range)
            ->whereNull('invoices.deleted_at')
            ->select(
                'clients.name',
                'clients.company',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(invoices.total_amount) as total_billed'),
                DB::raw('SUM(invoices.paid_amount) as total_paid')
            )
            ->groupBy('clients.id', 'clients.name', 'clients.company')
            ->orderByDesc('total_billed')
            ->limit(10)
            ->get();

        return view('reports.finance', compact(
            'totalRevenue', 'totalPaid', 'totalOutstanding', 'invoiceCount',
            'byStatus', 'recentPayments', 'topClients'
        ));
    }

    public function export(Request $request)
    {
        $type = $request->input('type', 'sales');
        $range = $this->resolveDateRange($request);

        AuditLogService::log(
            'reports',
            'export',
            "Report exported: {$type}",
            null,
            ['type' => $type, 'date_from' => $range[0], 'date_to' => $range[1]]
        );

        return match ($type) {
            'sales' => $this->exportSales($range),
            'purchasing' => $this->exportPurchasing($range),
            'inventory' => $this->exportInventory(),
            'manufacturing' => $this->exportManufacturing($range),
            default => back(),
        };
    }

    private function exportSales(array $range): StreamedResponse
    {
        $orders = SalesOrder::with('items.product')
            ->whereBetween('order_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->orderBy('order_date')
            ->get();

        return $this->streamCsv('sales_report.csv', ['Order #', 'Date', 'Status', 'Product', 'Qty', 'Unit Price', 'Line Total', 'Order Total'], function () use ($orders) {
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    yield [
                        $order->number,
                        $order->order_date->format('Y-m-d'),
                        $order->status,
                        $item->product->name ?? '—',
                        $item->quantity,
                        $item->unit_price,
                        $item->total,
                        $order->total,
                    ];
                }
            }
        });
    }

    private function exportPurchasing(array $range): StreamedResponse
    {
        $orders = PurchaseOrder::with('items.product')
            ->whereBetween('order_date', $range)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->orderBy('order_date')
            ->get();

        return $this->streamCsv('purchasing_report.csv', ['PO #', 'Date', 'Status', 'Product', 'Qty', 'Received', 'Unit Price', 'Line Total', 'PO Total'], function () use ($orders) {
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    yield [
                        $order->number,
                        $order->order_date->format('Y-m-d'),
                        $order->status,
                        $item->product->name ?? '—',
                        $item->quantity,
                        $item->received_quantity,
                        $item->unit_price,
                        $item->total,
                        $order->total,
                    ];
                }
            }
        });
    }

    private function exportInventory(): StreamedResponse
    {
        $stocks = DB::table('inventory_stocks')
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'inventory_stocks.warehouse_id')
            ->select('products.sku', 'products.name', 'warehouses.name as warehouse', 'inventory_stocks.quantity', 'products.min_stock')
            ->orderBy('products.name')
            ->get();

        return $this->streamCsv('inventory_report.csv', ['SKU', 'Product', 'Warehouse', 'Quantity', 'Min Stock', 'Status'], function () use ($stocks) {
            foreach ($stocks as $row) {
                yield [
                    $row->sku,
                    $row->name,
                    $row->warehouse,
                    $row->quantity,
                    $row->min_stock,
                    $row->quantity <= $row->min_stock ? 'LOW' : 'OK',
                ];
            }
        });
    }

    private function streamCsv(string $filename, array $headers, callable $rowGenerator): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rowGenerator) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rowGenerator() as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function exportManufacturing(array $range): StreamedResponse
    {
        $orders = ManufacturingOrder::with('product')
            ->whereBetween('created_at', $range)
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at')
            ->get();

        return $this->streamCsv('manufacturing_report.csv', ['Order #', 'Product', 'Status', 'Planned Qty', 'Produced Qty', 'Created'], function () use ($orders) {
            foreach ($orders as $order) {
                yield [
                    $order->number,
                    $order->product->name ?? '—',
                    $order->status,
                    $order->planned_quantity,
                    $order->produced_quantity,
                    $order->created_at->format('Y-m-d'),
                ];
            }
        });
    }

    private function resolveDateRange(Request $request): array
    {
        $period = $request->input('period', '30');

        if ($period === 'custom') {
            return [
                Carbon::parse($request->input('from', now()->subDays(30)))->startOfDay(),
                Carbon::parse($request->input('to', now()))->endOfDay(),
            ];
        }

        return [
            now()->subDays((int) $period)->startOfDay(),
            now()->endOfDay(),
        ];
    }
}
