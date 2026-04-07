@extends('layouts.app')

@section('title', __('messages.ppn_calculator_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.tax.spt-masa-ppn') }}" class="hover:text-gray-700">{{ __('messages.spt_masa_ppn_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.ppn_calculator_title') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.ppn_calculator_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.ppn_calculator_subtitle') }}</p>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl" x-data="ppnCalculator()">
        {{-- Mode Selector --}}
        <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
            <label class="block text-xs font-medium text-gray-500 mb-2">{{ __('messages.calculation_mode') }}</label>
            <div class="flex gap-2">
                <button type="button" @click="mode = 'add'" :class="mode === 'add' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="flex-1 rounded-xl px-4 py-2.5 text-sm font-medium transition">
                    {{ __('messages.add_ppn_to_dpp') }}
                </button>
                <button type="button" @click="mode = 'extract'" :class="mode === 'extract' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="flex-1 rounded-xl px-4 py-2.5 text-sm font-medium transition">
                    {{ __('messages.extract_ppn_from_total') }}
                </button>
            </div>
        </div>

        {{-- Calculator --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="space-y-4">
                {{-- PPN Rate --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.ppn_rate') }} (%)</label>
                    <input type="number" x-model.number="rate" step="0.5" min="0" max="100" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                </div>

                {{-- Input Amount --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        <span x-show="mode === 'add'">DPP ({{ __('messages.before_tax') }})</span>
                        <span x-show="mode === 'extract'">{{ __('messages.total_inclusive') }}</span>
                    </label>
                    <input type="number" x-model.number="amount" step="1" min="0" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-lg font-medium text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" placeholder="0">
                </div>
            </div>

            {{-- Results --}}
            <div class="mt-6 space-y-3">
                <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3">
                    <span class="text-sm text-gray-600">DPP</span>
                    <span class="text-lg font-semibold text-gray-900" x-text="formatCurrency(dpp)"></span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-red-50 px-4 py-3">
                    <span class="text-sm text-red-700">PPN (<span x-text="rate"></span>%)</span>
                    <span class="text-lg font-semibold text-red-700" x-text="formatCurrency(ppn)"></span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-primary-50 px-4 py-3 ring-1 ring-primary-200">
                    <span class="text-sm font-medium text-primary-800">{{ __('messages.total_amount') }}</span>
                    <span class="text-xl font-bold text-primary-800" x-text="formatCurrency(total)"></span>
                </div>
            </div>
        </div>

        {{-- Quick Reference --}}
        <div class="mt-6 rounded-2xl bg-amber-50 p-4 shadow-sm ring-1 ring-amber-200">
            <h4 class="text-sm font-semibold text-amber-800 mb-2">{{ __('messages.ppn_quick_ref') }}</h4>
            <ul class="text-xs text-amber-700 space-y-1">
                <li>&bull; {{ __('messages.ppn_rate_info') }}</li>
                <li>&bull; DPP = Dasar Pengenaan Pajak ({{ __('messages.tax_base') }})</li>
                <li>&bull; PPN = Pajak Pertambahan Nilai ({{ __('messages.value_added_tax') }})</li>
                <li>&bull; {{ __('messages.formula_ppn') }}: PPN = DPP &times; Rate%</li>
            </ul>
        </div>
    </div>

    @push('scripts')
    <script>
        function ppnCalculator() {
            return {
                mode: 'add',
                rate: 11,
                amount: 0,
                get dpp() {
                    if (this.mode === 'add') return this.amount || 0;
                    return Math.round((this.amount || 0) / (1 + this.rate / 100));
                },
                get ppn() {
                    return Math.round(this.dpp * this.rate / 100);
                },
                get total() {
                    return this.dpp + this.ppn;
                },
                formatCurrency(val) {
                    return ManERP.formatCurrency(val);
                }
            }
        }
    </script>
    @endpush
@endsection
