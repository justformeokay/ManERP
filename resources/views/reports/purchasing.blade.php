@extends('layouts.app')

@section('title', 'Purchasing Report')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('reports.index') }}" class="hover:text-gray-700">Reports</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Purchasing</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Purchasing Report</h1>
            <p class="mt-1 text-sm text-gray-500">Spending, orders, and supplier performance</p>
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
            <a href="{{ route('reports.export', array_merge(['type' => 'purchasing'], request()->query())) }}"
                class="rounded-xl bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50 transition-colors">Export CSV</a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        @include('components.stat-card', [
            'title' => 'Total Spent',
            'value' => format_currency($summary->total ?? 0),
            'iconBg' => 'bg-blue-50 text-blue-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Purchase Orders',
            'value' => number_format($summary->count ?? 0),
            'iconBg' => 'bg-amber-50 text-amber-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Average PO Value',
            'value' => format_currency($summary->avg_total ?? 0),
            'iconBg' => 'bg-purple-50 text-purple-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>',
        ])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Status Breakdown --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">By Status</h3>
            @php $statusColors = \App\Models\PurchaseOrder::statusColors(); @endphp
            <div class="space-y-3">
                @forelse($byStatus as $row)
                    <div class="flex items-center justify-between">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColors[$row->status] ?? '' }}">{{ ucfirst($row->status) }}</span>
                        <div class="text-right">
                            <span class="text-sm font-semibold text-gray-900">{{ format_currency($row->total ?? 0) }}</span>
                            <span class="text-xs text-gray-400 ml-1">({{ $row->count }})</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No data</p>
                @endforelse
            </div>
        </div>

        {{-- Monthly Chart --}}
        <div class="lg:col-span-2 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Monthly Purchases</h3>
            <div class="h-64">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Suppliers --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900">Top Suppliers by Spending</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Supplier</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Orders</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total Spent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($topSuppliers as $i => $s)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-6 py-3">
                                <p class="text-sm font-medium text-gray-900">{{ $s->name }}</p>
                                @if($s->company)<p class="text-xs text-gray-500">{{ $s->company }}</p>@endif
                            </td>
                            <td class="px-6 py-3 text-right text-sm text-gray-700">{{ $s->order_count }}</td>
                            <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ format_currency($s->total_spent) }}</td>
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
    function toggleCustomDate(el) {
        document.getElementById('customDateFields').classList.toggle('hidden', el.value !== 'custom');
        document.getElementById('customDateFields').classList.toggle('flex', el.value === 'custom');
    }

    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: @json($monthlySeries->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m . '-01')->format('M Y'))),
            datasets: [{
                label: 'Spending',
                data: @json($monthlySeries->pluck('total')),
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: '#f3f4f6' } }
            }
        }
    });
</script>
@endpush
