@extends('layouts.app')

@section('title', 'Inventory Report')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('reports.index') }}" class="hover:text-gray-700">Reports</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Inventory</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Inventory Report</h1>
            <p class="mt-1 text-sm text-gray-500">Stock levels, product distribution, and alerts</p>
        </div>
        <a href="{{ route('reports.export', ['type' => 'inventory']) }}"
            class="rounded-xl bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50 transition-colors inline-flex items-center gap-1 self-start">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            Export CSV
        </a>
    </div>
@endsection

@section('content')
    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        @include('components.stat-card', [
            'title' => 'Active Products',
            'value' => number_format($totalProducts),
            'iconBg' => 'bg-blue-50 text-blue-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Total Stock Quantity',
            'value' => number_format($totalStock, 0),
            'iconBg' => 'bg-green-50 text-green-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Low Stock Items',
            'value' => number_format($lowStockItems->count()),
            'iconBg' => $lowStockItems->count() > 0 ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>',
        ])
    </div>

    {{-- Product Type + Most Stocked --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- By Type --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Products by Type</h3>
            <div class="space-y-3">
                @php
                    $typeLabels = ['raw_material' => 'Raw Material', 'semi_finished' => 'Semi Finished', 'finished_good' => 'Finished Good', 'consumable' => 'Consumable'];
                    $typeColors = ['raw_material' => 'bg-amber-100 text-amber-700', 'semi_finished' => 'bg-blue-100 text-blue-700', 'finished_good' => 'bg-green-100 text-green-700', 'consumable' => 'bg-gray-100 text-gray-700'];
                @endphp
                @forelse($byType as $row)
                    <div class="flex items-center justify-between">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeColors[$row->type] ?? 'bg-gray-100 text-gray-700' }}">{{ $typeLabels[$row->type] ?? ucfirst($row->type) }}</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $row->count }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No products</p>
                @endforelse
            </div>
        </div>

        {{-- Most Stocked --}}
        <div class="lg:col-span-2 rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Most Stocked Products</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($mostStocked as $i => $p)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-6 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $p->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $p->sku }}</p>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-700">{{ number_format($p->total_quantity, 0) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-400">No stock data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Low Stock --}}
    @if($lowStockItems->count() > 0)
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-red-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-red-100 bg-red-50/50 flex items-center gap-2">
                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <h3 class="text-base font-semibold text-red-900">Low Stock Items</h3>
                <span class="ml-auto text-xs text-red-600 font-medium">{{ $lowStockItems->count() }} item(s) below minimum</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Warehouse</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Current Stock</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Minimum</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Deficit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($lowStockItems as $item)
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
                                <td class="px-6 py-3 text-right text-sm font-semibold text-red-600">-{{ $item->min_stock - $item->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
