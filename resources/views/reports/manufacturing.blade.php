@extends('layouts.app')

@section('title', 'Manufacturing Report')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('reports.index') }}" class="hover:text-gray-700">Reports</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Manufacturing</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manufacturing Report</h1>
            <p class="mt-1 text-sm text-gray-500">Work orders, production output, and progress</p>
        </div>
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
    </div>
@endsection

@section('content')
    {{-- Summary --}}
    @php
        $completed = $byStatus->get('done');
        $inProgress = $byStatus->get('in_progress');
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 mb-8">
        @include('components.stat-card', [
            'title' => 'Total Work Orders',
            'value' => number_format($totalOrders),
            'iconBg' => 'bg-blue-50 text-blue-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Completed',
            'value' => number_format($completed->count ?? 0),
            'iconBg' => 'bg-green-50 text-green-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'In Progress',
            'value' => number_format($inProgress->count ?? 0),
            'iconBg' => 'bg-amber-50 text-amber-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Completion Rate',
            'value' => $totalOrders > 0 ? round((($completed->count ?? 0) / $totalOrders) * 100) . '%' : '—',
            'iconBg' => 'bg-purple-50 text-purple-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>',
        ])
    </div>

    {{-- Status Breakdown + Chart --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Orders by Status</h3>
            @php
                $moStatusColors = [
                    'draft' => 'bg-gray-100 text-gray-700',
                    'confirmed' => 'bg-blue-100 text-blue-700',
                    'in_progress' => 'bg-amber-100 text-amber-700',
                    'done' => 'bg-green-100 text-green-700',
                    'cancelled' => 'bg-red-100 text-red-700',
                ];
            @endphp
            <div class="space-y-3">
                @forelse($byStatus as $status => $row)
                    <div class="flex items-center justify-between">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $moStatusColors[$status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $row->count }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No data</p>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-2 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Status Distribution</h3>
            <div class="h-64">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Production by Product --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900">Production by Product</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Orders</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Planned</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Produced</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Progress</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($productionByProduct as $i => $row)
                        @php $pct = $row->planned > 0 ? round(($row->produced / $row->planned) * 100) : 0; @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-6 py-3">
                                <p class="text-sm font-medium text-gray-900">{{ $row->name }}</p>
                                <p class="text-xs text-gray-500">{{ $row->sku }}</p>
                            </td>
                            <td class="px-6 py-3 text-right text-sm text-gray-700">{{ $row->order_count }}</td>
                            <td class="px-6 py-3 text-right text-sm text-gray-700">{{ number_format($row->planned, 0) }}</td>
                            <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($row->produced, 0) }}</td>
                            <td class="px-6 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-20 h-2 rounded-full bg-gray-100 overflow-hidden">
                                        <div class="h-full rounded-full {{ $pct >= 100 ? 'bg-green-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-red-400') }}" style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium {{ $pct >= 100 ? 'text-green-600' : 'text-gray-500' }}">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">No manufacturing data</td>
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

    @php
        $statusData = $byStatus->map(fn($row) => $row->count);
        $chartLabels = $statusData->keys()->map(fn($s) => ucfirst(str_replace('_', ' ', $s)))->values();
        $chartValues = $statusData->values();
        $chartColors = $byStatus->keys()->map(fn($s) => match($s) {
            'draft' => '#9ca3af',
            'confirmed' => '#3b82f6',
            'in_progress' => '#f59e0b',
            'done' => '#10b981',
            'cancelled' => '#ef4444',
            default => '#6b7280',
        })->values();
    @endphp

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                data: @json($chartValues),
                backgroundColor: @json($chartColors),
                borderWidth: 0,
                spacing: 2,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: { padding: 16, font: { size: 12 }, usePointStyle: true, pointStyleWidth: 10 }
                }
            }
        }
    });
</script>
@endpush
