@extends('layouts.app')

@section('title', 'Sales Report')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('reports.index') }}" class="hover:text-gray-700">Reports</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Sales</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Sales Report</h1>
            <p class="mt-1 text-sm text-gray-500">Revenue, orders, and product performance</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="GET" class="flex items-center gap-2" id="filterForm">
                <select name="period" onchange="toggleCustomDate(this); document.getElementById('filterForm').submit();"
                    class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="7" {{ request('period', '30') == '7' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30" {{ request('period', '30') == '30' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90" {{ request('period') == '90' ? 'selected' : '' }}>Last 90 Days</option>
                    <option value="365" {{ request('period') == '365' ? 'selected' : '' }}>Last 1 Year</option>
                    <option value="custom" {{ request('period') == 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
                <div id="customDateFields" class="{{ request('period') == 'custom' ? 'flex' : 'hidden' }} items-center gap-2">
                    <input type="date" name="from" value="{{ request('from', now()->subDays(30)->format('Y-m-d')) }}"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                    <span class="text-gray-400">–</span>
                    <input type="date" name="to" value="{{ request('to', now()->format('Y-m-d')) }}"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                    <button type="submit" class="rounded-xl bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-700 transition-colors">Apply</button>
                </div>
            </form>
            <a href="{{ route('reports.export', array_merge(['type' => 'sales'], request()->query())) }}"
                class="rounded-xl bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50 transition-colors">Export CSV</a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        @include('components.stat-card', [
            'title' => 'Total Revenue',
            'value' => number_format($summary->total ?? 0, 0),
            'iconBg' => 'bg-green-50 text-green-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Number of Orders',
            'value' => number_format($summary->count ?? 0),
            'iconBg' => 'bg-blue-50 text-blue-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Average Order Value',
            'value' => number_format($summary->avg_total ?? 0, 0),
            'iconBg' => 'bg-purple-50 text-purple-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>',
        ])
    </div>

    {{-- Status Breakdown + Daily Chart --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Status Breakdown --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">By Status</h3>
            @php $statusColors = \App\Models\SalesOrder::statusColors(); @endphp
            <div class="space-y-3">
                @forelse($byStatus as $row)
                    <div class="flex items-center justify-between">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColors[$row->status] ?? '' }}">{{ ucfirst($row->status) }}</span>
                        <div class="text-right">
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($row->total ?? 0, 0) }}</span>
                            <span class="text-xs text-gray-400 ml-1">({{ $row->count }})</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No data</p>
                @endforelse
            </div>
        </div>

        {{-- Sales Chart --}}
        <div class="lg:col-span-2 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Daily Sales</h3>
            <div class="h-64">
                <canvas id="dailySalesChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Products + Top Clients --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Top Products by Revenue</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($topProducts as $i => $p)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-6 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $p->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $p->sku }}</p>
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-700">{{ number_format($p->total_qty, 0) }}</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($p->total_revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">No data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Top Clients by Revenue</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Client</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Orders</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($topClients as $i => $c)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-6 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $c->name }}</p>
                                    @if($c->company)<p class="text-xs text-gray-500">{{ $c->company }}</p>@endif
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-700">{{ $c->order_count }}</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($c->total_revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">No data</td>
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

    new Chart(document.getElementById('dailySalesChart'), {
        type: 'line',
        data: {
            labels: @json($dailySales->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))),
            datasets: [{
                label: 'Revenue',
                data: @json($dailySales->pluck('total')),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                borderWidth: 2,
            }, {
                label: 'Orders',
                data: @json($dailySales->pluck('count')),
                borderColor: '#6366f1',
                borderDash: [5, 5],
                tension: 0.3,
                pointRadius: 3,
                borderWidth: 2,
                yAxisID: 'y1',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: '#f3f4f6' }, position: 'left' },
                y1: { beginAtZero: true, grid: { display: false }, position: 'right' }
            }
        }
    });
</script>
@endpush
