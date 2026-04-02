@extends('layouts.app')

@section('title', __('messages.cost_detail_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('manufacturing.costing.index') }}" class="hover:text-gray-700">{{ __('messages.hpp_dashboard_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $order->number }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.cost_detail_title') }}: {{ $order->number }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $order->product->name ?? '' }}</p>
    </div>
    <form action="{{ route('manufacturing.costing.recalculate', $order) }}" method="POST">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 transition">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            {{ __('messages.recalculate_btn') }}
        </button>
    </form>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Cost Summary --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.cost_summary') }}</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('messages.material_cost_label') }}</dt>
                        <dd class="font-medium text-gray-900">{{ format_currency($productionCost->material_cost) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('messages.labor_cost_label') }}</dt>
                        <dd class="font-medium text-gray-900">{{ format_currency($productionCost->labor_cost) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('messages.overhead_cost_label') }}</dt>
                        <dd class="font-medium text-gray-900">{{ format_currency($productionCost->overhead_cost) }}</dd>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between text-sm">
                        <dt class="font-semibold text-gray-900">{{ __('messages.total_hpp') }}</dt>
                        <dd class="font-bold text-lg text-gray-900">{{ format_currency($productionCost->total_cost) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('messages.produced_qty') }}</dt>
                        <dd class="font-medium text-gray-900">{{ number_format($productionCost->produced_quantity) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('messages.cost_per_unit_label') }}</dt>
                        <dd class="font-semibold text-primary-600">{{ format_currency($productionCost->cost_per_unit) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Variance Analysis --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.variance_analysis') }}</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('messages.standard_cost_total') }}</dt>
                        <dd class="font-medium text-gray-900">{{ format_currency($variance['standard_cost']) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('messages.actual_cost_total') }}</dt>
                        <dd class="font-medium text-gray-900">{{ format_currency($variance['actual_cost']) }}</dd>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between text-sm">
                        <dt class="font-semibold text-gray-900">{{ __('messages.variance_label') }}</dt>
                        <dd class="font-bold {{ $variance['variance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $variance['variance'] > 0 ? '+' : '' }}{{ format_currency($variance['variance']) }}
                            ({{ $variance['variance_pct'] > 0 ? '+' : '' }}{{ $variance['variance_pct'] }}%)
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Material Breakdown --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('messages.material_breakdown') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/60">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.material_header') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.quantity_header') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.unit_cost_label') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.line_cost_label') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($productionCost->cost_breakdown ?? [] as $i => $mat)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-6 py-3 text-sm">
                                        <p class="font-medium text-gray-900">{{ $mat['product_name'] }}</p>
                                        <p class="text-xs text-gray-400">{{ $mat['sku'] ?? '' }}</p>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right text-gray-600">{{ number_format($mat['quantity'], 4) }}</td>
                                    <td class="px-6 py-3 text-sm text-right text-gray-600">{{ format_currency($mat['unit_cost']) }}</td>
                                    <td class="px-6 py-3 text-sm text-right font-semibold text-gray-900">{{ format_currency($mat['line_cost']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">{{ __('messages.no_breakdown_data') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($productionCost->cost_breakdown))
                            <tfoot class="bg-gray-50/60">
                                <tr>
                                    <td colspan="4" class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ __('messages.total_material_cost') }}</td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">{{ format_currency($productionCost->material_cost) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
