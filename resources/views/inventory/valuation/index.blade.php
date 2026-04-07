@extends('layouts.app')

@section('title', 'Stock Valuation Report')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('inventory.stocks.index') }}" class="hover:text-gray-700">{{ __('messages.inventory') ?? 'Inventory' }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Stock Valuation</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Stock Valuation Report</h1>
            <p class="mt-1 text-sm text-gray-500">Weighted Average Cost (WAC) per PSAK 14 — Generated: {{ $generatedAt }}</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Card --}}
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-primary-50 to-blue-50 p-6 shadow-sm ring-1 ring-primary-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Inventory Value</p>
                <p class="text-3xl font-bold text-gray-900">{{ format_currency($grandTotal) }}</p>
            </div>
            <div class="text-sm text-gray-500">
                {{ count($report) }} product(s) in stock
            </div>
        </div>
    </div>

    {{-- Valuation Table --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">SKU</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Qty On Hand</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Avg Cost</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total Value</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($report as $row)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm font-mono text-gray-700">{{ $row['sku'] }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['product_name'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $row['category'] }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    {{ $row['type'] === 'raw_material' ? 'bg-amber-50 text-amber-700' :
                                       ($row['type'] === 'finished_good' ? 'bg-green-50 text-green-700' :
                                       ($row['type'] === 'semi_finished' ? 'bg-blue-50 text-blue-700' : 'bg-gray-50 text-gray-700')) }}">
                                    {{ str_replace('_', ' ', ucfirst($row['type'])) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-gray-700">{{ number_format($row['total_qty'], 2) }} {{ $row['unit'] }}</td>
                            <td class="px-6 py-4 text-sm text-right font-mono text-gray-700">{{ format_currency($row['avg_cost']) }}</td>
                            <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900">{{ format_currency($row['total_value']) }}</td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('inventory.valuation.show', $row['product_id']) }}"
                                   class="text-primary-600 hover:text-primary-800 text-sm font-medium">
                                    Layers →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-400">
                                No products with stock on hand.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($report) > 0)
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-sm font-bold text-gray-700 text-right">Grand Total:</td>
                            <td class="px-6 py-4 text-sm font-bold text-right text-gray-900">{{ format_currency($grandTotal) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
