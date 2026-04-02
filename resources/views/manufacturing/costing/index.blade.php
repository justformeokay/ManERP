@extends('layouts.app')

@section('title', __('messages.hpp_dashboard_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.hpp_dashboard_title') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.hpp_dashboard_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.hpp_dashboard_subtitle') }}</p>
    </div>
    <a href="{{ route('manufacturing.costing.simulate-form') }}"
       class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
        {{ __('messages.cost_simulator_btn') }}
    </a>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.total_production_cost') }}</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ format_currency($summary['total_production_cost']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.total_material_cost') }}</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ format_currency($summary['total_material_cost']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.total_labor_cost') }}</p>
            <p class="mt-2 text-2xl font-bold text-green-600">{{ format_currency($summary['total_labor_cost']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.total_overhead_cost') }}</p>
            <p class="mt-2 text-2xl font-bold text-amber-600">{{ format_currency($summary['total_overhead_cost']) }}</p>
        </div>
    </div>

    {{-- Cost History Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900">{{ __('messages.production_cost_history') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/60">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.mo_number') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.product_label') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.qty_label') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.material_cost_label') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.labor_cost_label') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.overhead_cost_label') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.total_cost_label') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.cost_per_unit_label') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.actions_table_header') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($costs as $cost)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-3 text-sm font-medium text-primary-600">
                                <a href="{{ route('manufacturing.costing.show', $cost->manufacturing_order_id) }}">
                                    {{ $cost->manufacturingOrder->number ?? '—' }}
                                </a>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-900">{{ $cost->product->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-600">{{ number_format($cost->produced_quantity) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-600">{{ format_currency($cost->material_cost) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-600">{{ format_currency($cost->labor_cost) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-600">{{ format_currency($cost->overhead_cost) }}</td>
                            <td class="px-6 py-3 text-sm text-right font-semibold text-gray-900">{{ format_currency($cost->total_cost) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-600">{{ format_currency($cost->cost_per_unit) }}</td>
                            <td class="px-6 py-3 text-center">
                                <a href="{{ route('manufacturing.costing.show', $cost->manufacturing_order_id) }}"
                                   class="text-xs font-medium text-primary-600 hover:text-primary-800">{{ __('messages.view_details_btn') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('messages.no_production_costs') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($costs->hasPages())
            <div class="px-6 py-3 border-t border-gray-100">{{ $costs->links() }}</div>
        @endif
    </div>
@endsection
