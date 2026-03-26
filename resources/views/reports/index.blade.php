@extends('layouts.app')

@section('title', 'Reports Dashboard')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Reports</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reports Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Business overview and key metrics</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Date filter --}}
            <form method="GET" class="flex items-center gap-2" id="filterForm">
                <select name="period" onchange="toggleCustomDate(this); document.getElementById('filterForm').submit();"
                    class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="7" {{ request('period', '30') == '7' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30" {{ request('period', '30') == '30' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90" {{ request('period') == '90' ? 'selected' : '' }}>Last 90 Days</option>
                    <option value="365" {{ request('period') == '365' ? 'selected' : '' }}>Last 1 Year</option>
                    <option value="custom" {{ request('period') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
                <div id="customDateFields" class="{{ request('period') == 'custom' ? 'flex' : 'hidden' }} items-center gap-2">
                    <input type="date" name="from" value="{{ request('from', now()->subDays(30)->format('Y-m-d')) }}"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <span class="text-gray-400">–</span>
                    <input type="date" name="to" value="{{ request('to', now()->format('Y-m-d')) }}"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <button type="submit" class="rounded-xl bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-700 transition-colors">Apply</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('content')
    {{-- Quick Navigation --}}
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('reports.sales', request()->query()) }}" class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-100 hover:bg-gray-50 transition-colors">Sales Report</a>
        <a href="{{ route('reports.purchasing', request()->query()) }}" class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-100 hover:bg-gray-50 transition-colors">Purchasing Report</a>
        <a href="{{ route('reports.inventory') }}" class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-100 hover:bg-gray-50 transition-colors">Inventory Report</a>
        <a href="{{ route('reports.manufacturing', request()->query()) }}" class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-100 hover:bg-gray-50 transition-colors">Manufacturing Report</a>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @include('components.stat-card', [
            'title' => 'Total Sales',
            'value' => number_format($totalSales, 0),
            'iconBg' => 'bg-green-50 text-green-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Total Purchases',
            'value' => number_format($totalPurchases, 0),
            'iconBg' => 'bg-blue-50 text-blue-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Total Products',
            'value' => number_format($totalProducts),
            'iconBg' => 'bg-amber-50 text-amber-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Total Orders',
            'value' => number_format($totalOrders),
            'iconBg' => 'bg-purple-50 text-purple-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>',
        ])
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Sales Trend --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Sales Trend (Last 7 Days)</h3>
                <a href="{{ route('reports.export', ['type' => 'sales', 'period' => request('period', '30')]) }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">Export CSV ↓</a>
            </div>
            <div class="h-64">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        {{-- Purchase Trend --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Purchase Trend (Last 6 Months)</h3>
                <a href="{{ route('reports.export', ['type' => 'purchasing', 'period' => request('period', '30')]) }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">Export CSV ↓</a>
            </div>
            <div class="h-64">
                <canvas id="purchaseChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Tables --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Products --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Top Selling Products</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Qty Sold</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($topProducts as $i => $product)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-6 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $product->sku }}</p>
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-700">{{ number_format($product->total_qty, 0) }}</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($product->total_revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">No sales data for this period</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Low Stock Alerts</h3>
                <a href="{{ route('reports.export', ['type' => 'inventory']) }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">Export CSV ↓</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Warehouse</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Stock</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Min</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($lowStockItems as $item)
                            <tr class="hover:bg-red-50/30 transition-colors">
                                <td class="px-6 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $item->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $item->sku }}</p>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $item->warehouse }}</td>
                                <td class="px-6 py-3 text-right">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700">{{ number_format($item->quantity, 0) }}</span>
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-500">{{ $item->min_stock }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">
                                    <span class="text-green-600 font-medium">All stock levels are healthy</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
    function toggleCustomDate(el) {
        document.getElementById('customDateFields').classList.toggle('hidden', el.value !== 'custom');
        document.getElementById('customDateFields').classList.toggle('flex', el.value === 'custom');
    }

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 } } }
        }
    };

    // Sales Chart
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: @json(collect($salesDays->keys())->map(fn($d) => \Carbon\Carbon::parse($d)->format('D, M d'))->values()),
            datasets: [{
                label: 'Sales',
                data: @json($salesDays->values()),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#10b981',
                borderWidth: 2,
            }]
        },
        options: chartDefaults
    });

    // Purchase Chart
    new Chart(document.getElementById('purchaseChart'), {
        type: 'bar',
        data: {
            labels: @json(collect($purchaseMonths->keys())->map(fn($m) => \Carbon\Carbon::parse($m . '-01')->format('M Y'))->values()),
            datasets: [{
                label: 'Purchases',
                data: @json($purchaseMonths->values()),
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: chartDefaults
    });
</script>
@endpush
