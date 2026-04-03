@extends('layouts.app')

@section('title', 'Valuation History — ' . $product->name)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('inventory.valuation.index') }}" class="hover:text-gray-700">Stock Valuation</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $product->name }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                SKU: {{ $product->sku }} — Current Avg Cost: <strong>Rp {{ number_format($product->avg_cost, 4, ',', '.') }}</strong>
            </p>
        </div>
        <a href="{{ route('inventory.valuation.index') }}"
           class="inline-flex items-center rounded-xl bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition">
            ← Back to Report
        </a>
    </div>
@endsection

@section('content')
    {{-- Date Filter --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" action="{{ route('inventory.valuation.show', $product) }}" class="flex flex-col sm:flex-row gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="from" value="{{ $from }}"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="to" value="{{ $to }}"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
            </div>
            <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 transition">
                Filter
            </button>
            @if($from || $to)
                <a href="{{ route('inventory.valuation.show', $product) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
    </div>

    {{-- Layers Table --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Direction</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Warehouse</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Unit Cost</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total Value</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Avg Cost After</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($layers as $layer)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-sm text-gray-600">{{ \Carbon\Carbon::parse($layer['created_at'])->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    {{ $layer['direction'] === 'in' ? 'bg-green-50 text-green-700 ring-1 ring-green-600/20' : 'bg-red-50 text-red-700 ring-1 ring-red-600/20' }}">
                                    {{ strtoupper($layer['direction']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $layer['warehouse']['name'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($layer['quantity'], 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-gray-700">Rp {{ number_format($layer['unit_cost'], 4, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-gray-700">Rp {{ number_format($layer['total_value'], 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono font-semibold text-gray-900">Rp {{ number_format($layer['avg_cost_after'], 4, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $layer['reference_type'] ? $layer['reference_type'] . ' #' . $layer['reference_id'] : '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $layer['description'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-400">
                                No valuation layers found for this product.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
