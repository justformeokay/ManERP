@extends('layouts.app')

@section('title', __('messages.cost_simulator_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('manufacturing.costing.index') }}" class="hover:text-gray-700">{{ __('messages.hpp_dashboard_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.cost_simulator_title') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.cost_simulator_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.cost_simulator_subtitle') }}</p>
    </div>
@endsection

@section('content')
    {{-- Simulation Form --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 mb-6">
        <form action="{{ route('manufacturing.costing.simulate') }}" method="POST" class="flex flex-wrap items-end gap-4">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label for="bom_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.select_bom_label') }}</label>
                <select id="bom_id" name="bom_id" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="">{{ __('messages.select_bom_placeholder') }}</option>
                    @foreach($boms as $b)
                        <option value="{{ $b->id }}" @selected(($bom->id ?? null) == $b->id)>
                            {{ $b->name }} — {{ $b->product->name ?? '' }} (v{{ $b->version }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.quantity_header') }}</label>
                <input type="number" id="quantity" name="quantity" step="0.01" min="0.01" required
                    value="{{ $quantity ?? 1 }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                {{ __('messages.calculate_btn') }}
            </button>
        </form>
    </div>

    @if($costBreakdown)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Cost Summary --}}
            <div class="lg:col-span-1">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.simulation_result') }}</h3>
                    <p class="text-sm text-gray-500 mb-4">{{ $bom->name }} — {{ number_format($quantity, 2) }} {{ $bom->product->unit ?? 'unit' }}</p>
                    <dl class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ __('messages.material_cost_label') }}</dt>
                            <dd class="font-medium text-blue-600">{{ format_currency($costBreakdown['material_cost']) }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ __('messages.labor_cost_label') }}</dt>
                            <dd class="font-medium text-green-600">{{ format_currency($laborTotal) }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ __('messages.overhead_cost_label') }}</dt>
                            <dd class="font-medium text-amber-600">{{ format_currency($overheadTotal) }}</dd>
                        </div>
                        <div class="border-t border-gray-100 pt-3 flex justify-between">
                            <dt class="font-semibold text-gray-900">{{ __('messages.total_hpp') }}</dt>
                            <dd class="font-bold text-lg text-gray-900">{{ format_currency($grandTotal) }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ __('messages.cost_per_unit_label') }}</dt>
                            <dd class="font-semibold text-primary-600">{{ format_currency($quantity > 0 ? $grandTotal / $quantity : 0) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Material Breakdown Table --}}
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
                                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-500">{{ __('messages.bom_level') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.quantity_header') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.unit_cost_label') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.line_cost_label') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($costBreakdown['materials'] as $i => $mat)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                                        <td class="px-6 py-3 text-sm">
                                            <span style="padding-left: {{ $mat['level'] * 16 }}px">
                                                @if($mat['level'] > 0) <span class="text-gray-300 mr-1">↳</span> @endif
                                                <span class="font-medium text-gray-900">{{ $mat['product_name'] }}</span>
                                            </span>
                                            <span class="text-xs text-gray-400 ml-1">{{ $mat['sku'] }}</span>
                                        </td>
                                        <td class="px-6 py-3 text-sm text-center text-gray-500">L{{ $mat['level'] }}</td>
                                        <td class="px-6 py-3 text-sm text-right text-gray-600">{{ number_format($mat['quantity'], 4) }}</td>
                                        <td class="px-6 py-3 text-sm text-right text-gray-600">{{ format_currency($mat['unit_cost']) }}</td>
                                        <td class="px-6 py-3 text-sm text-right font-semibold text-gray-900">{{ format_currency($mat['line_cost']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50/60">
                                <tr>
                                    <td colspan="5" class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ __('messages.total_material_cost') }}</td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">{{ format_currency($costBreakdown['material_cost']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
