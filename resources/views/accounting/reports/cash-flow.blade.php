@extends('layouts.app')

@section('title', __('messages.cash_flow_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.cash_flow_title') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.cash_flow_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.cash_flow_subtitle') }}</p>
    </div>
@endsection

@section('content')
    {{-- Company Header --}}
    <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 text-center">
        <h2 class="text-xl font-bold text-gray-900">{{ $company->name ?? config('app.name') }}</h2>
        @if($company->address)
            <p class="text-sm text-gray-500 mt-1">{{ $company->address }}@if($company->city), {{ $company->city }}@endif</p>
        @endif
        <p class="text-base font-semibold text-gray-700 mt-2">{{ __('messages.cash_flow_title') }}</p>
        <p class="text-sm text-gray-500">{{ __('messages.cf_indirect_method') }}</p>
        <p class="text-sm text-gray-500">{{ __('messages.cf_period') }}: {{ \Carbon\Carbon::parse($data['start_date'])->format('d M Y') }} — {{ \Carbon\Carbon::parse($data['end_date'])->format('d M Y') }}</p>
    </div>

    {{-- Date Range Filter + Export Buttons --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="from" class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.from') }}</label>
                <input type="date" name="from" id="from" value="{{ $data['start_date'] }}"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div>
                <label for="to" class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.to') }}</label>
                <input type="date" name="to" id="to" value="{{ $data['end_date'] }}"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div class="flex gap-2">
                @include('components.button', ['label' => __('messages.generate'), 'type' => 'primary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['from', 'to']))
                    @include('components.button', ['label' => 'YTD', 'type' => 'ghost', 'href' => route('accounting.cash-flow')])
                @endif
            </div>
            <div class="ml-auto flex gap-2">
                <a href="{{ route('accounting.cash-flow.pdf', request()->only(['from', 'to'])) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 ring-1 ring-red-200 hover:bg-red-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    PDF
                </a>
                <a href="{{ route('accounting.cash-flow.excel', request()->only(['from', 'to'])) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-green-50 px-4 py-2.5 text-sm font-medium text-green-700 ring-1 ring-green-200 hover:bg-green-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Excel
                </a>
            </div>
        </form>
    </div>

    {{-- Discrepancy Warning --}}
    @if($data['has_discrepancy'])
        <div class="mb-6 rounded-2xl bg-amber-50 border border-amber-300 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <div>
                    <p class="font-semibold text-amber-800">{{ __('messages.cf_discrepancy_warning') }}</p>
                    <p class="text-sm text-amber-700 mt-1">
                        {{ __('messages.cf_discrepancy_detail', ['amount' => format_currency(abs($data['discrepancy_amount']))]) }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.beginning_cash') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-700">{{ format_currency($data['beginning_cash']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.net_cash_change') }}</p>
            <p class="mt-1 text-2xl font-bold {{ $data['net_cash_change'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                {{ $data['net_cash_change'] >= 0 ? '+' : '' }}{{ format_currency($data['net_cash_change']) }}
            </p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.ending_cash') }}</p>
            <p class="mt-1 text-2xl font-bold text-blue-700">{{ format_currency($data['ending_cash']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.net_income') }}</p>
            <p class="mt-1 text-2xl font-bold {{ $data['net_income'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                {{ format_currency($data['net_income']) }}
            </p>
        </div>
    </div>

    {{-- Cash Flow Details --}}
    <div class="space-y-6">
        {{-- Operating Activities --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
                <h3 class="text-base font-semibold text-blue-900">{{ __('messages.cf_operating') }}</h3>
            </div>
            <div class="p-6">
                <table class="w-full">
                    <tbody class="divide-y divide-gray-50">
                        <tr>
                            <td class="py-2 text-sm text-gray-600">{{ __('messages.net_income') }}</td>
                            <td class="py-2 text-sm font-medium text-right {{ $data['net_income'] >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                                {{ format_currency($data['net_income']) }}
                            </td>
                        </tr>
                        @if(!empty($data['operating']))
                            <tr><td colspan="2" class="pt-3 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('messages.cf_adjustments') }}</td></tr>
                            @foreach($data['operating'] as $item)
                                @php
                                    $label = __('messages.cf_' . $item['label']);
                                    if ($label === 'messages.cf_' . $item['label']) {
                                        // No translation found — format the raw label
                                        $label = ucwords(str_replace(['_', 'change in '], [' ', ''], $item['label']));
                                    }
                                @endphp
                                <tr>
                                    <td class="py-2 pl-4 text-sm text-gray-600">{{ $label }}</td>
                                    <td class="py-2 text-sm font-medium text-right {{ $item['amount'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                        {{ $item['amount'] >= 0 ? '+' : '' }}{{ format_currency($item['amount']) }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-blue-200">
                            <td class="py-3 font-semibold text-blue-900">{{ __('messages.cf_total_operating') }}</td>
                            <td class="py-3 text-right font-bold text-lg {{ $data['total_operating'] >= 0 ? 'text-blue-700' : 'text-red-600' }}">
                                {{ format_currency($data['total_operating']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Investing Activities --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="bg-amber-50 px-6 py-4 border-b border-amber-100">
                <h3 class="text-base font-semibold text-amber-900">{{ __('messages.cf_investing') }}</h3>
            </div>
            <div class="p-6">
                <table class="w-full">
                    <tbody class="divide-y divide-gray-50">
                        @forelse($data['investing'] as $item)
                            @php
                                $label = __('messages.cf_' . $item['label']);
                                if ($label === 'messages.cf_' . $item['label']) {
                                    $label = ucwords(str_replace(['_', 'change in '], [' ', ''], $item['label']));
                                }
                            @endphp
                            <tr>
                                <td class="py-2 text-sm text-gray-600">{{ $label }}</td>
                                <td class="py-2 text-sm font-medium text-right {{ $item['amount'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                    {{ $item['amount'] >= 0 ? '+' : '' }}{{ format_currency($item['amount']) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td class="py-2 text-sm text-gray-400 italic">{{ __('messages.no_activity') }}</td><td></td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-amber-200">
                            <td class="py-3 font-semibold text-amber-900">{{ __('messages.cf_total_investing') }}</td>
                            <td class="py-3 text-right font-bold text-lg {{ $data['total_investing'] >= 0 ? 'text-amber-700' : 'text-red-600' }}">
                                {{ format_currency($data['total_investing']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Financing Activities --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="bg-purple-50 px-6 py-4 border-b border-purple-100">
                <h3 class="text-base font-semibold text-purple-900">{{ __('messages.cf_financing') }}</h3>
            </div>
            <div class="p-6">
                <table class="w-full">
                    <tbody class="divide-y divide-gray-50">
                        @forelse($data['financing'] as $item)
                            @php
                                $label = __('messages.cf_' . $item['label']);
                                if ($label === 'messages.cf_' . $item['label']) {
                                    $label = ucwords(str_replace(['_', 'change in '], [' ', ''], $item['label']));
                                }
                            @endphp
                            <tr>
                                <td class="py-2 text-sm text-gray-600">{{ $label }}</td>
                                <td class="py-2 text-sm font-medium text-right {{ $item['amount'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                    {{ $item['amount'] >= 0 ? '+' : '' }}{{ format_currency($item['amount']) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td class="py-2 text-sm text-gray-400 italic">{{ __('messages.no_activity') }}</td><td></td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-purple-200">
                            <td class="py-3 font-semibold text-purple-900">{{ __('messages.cf_total_financing') }}</td>
                            <td class="py-3 text-right font-bold text-lg {{ $data['total_financing'] >= 0 ? 'text-purple-700' : 'text-red-600' }}">
                                {{ format_currency($data['total_financing']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Reconciliation Section --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">{{ __('messages.cf_reconciliation') }}</h3>
            </div>
            <div class="p-6">
                <table class="w-full">
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="py-2 text-sm text-gray-600">{{ __('messages.beginning_cash') }}</td>
                            <td class="py-2 text-sm font-medium text-right text-gray-900">{{ format_currency($data['beginning_cash']) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-sm text-gray-600">{{ __('messages.cf_total_operating') }}</td>
                            <td class="py-2 text-sm font-medium text-right {{ $data['total_operating'] >= 0 ? 'text-blue-700' : 'text-red-600' }}">
                                {{ $data['total_operating'] >= 0 ? '+' : '' }}{{ format_currency($data['total_operating']) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-sm text-gray-600">{{ __('messages.cf_total_investing') }}</td>
                            <td class="py-2 text-sm font-medium text-right {{ $data['total_investing'] >= 0 ? 'text-amber-700' : 'text-red-600' }}">
                                {{ $data['total_investing'] >= 0 ? '+' : '' }}{{ format_currency($data['total_investing']) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-sm text-gray-600">{{ __('messages.cf_total_financing') }}</td>
                            <td class="py-2 text-sm font-medium text-right {{ $data['total_financing'] >= 0 ? 'text-purple-700' : 'text-red-600' }}">
                                {{ $data['total_financing'] >= 0 ? '+' : '' }}{{ format_currency($data['total_financing']) }}
                            </td>
                        </tr>
                        <tr class="border-t-2 border-gray-300">
                            <td class="py-2 text-sm font-semibold text-gray-700">{{ __('messages.cf_ending_cash_computed') }}</td>
                            <td class="py-2 text-sm font-bold text-right text-gray-900">{{ format_currency($data['ending_cash']) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-sm font-semibold text-gray-700">{{ __('messages.cf_ending_cash_actual') }}</td>
                            <td class="py-2 text-sm font-bold text-right text-blue-700">{{ format_currency($data['actual_ending_cash']) }}</td>
                        </tr>
                        @if($data['has_discrepancy'])
                            <tr class="bg-amber-50">
                                <td class="py-2 text-sm font-semibold text-amber-800">{{ __('messages.cf_unreconciled_diff') }}</td>
                                <td class="py-2 text-sm font-bold text-right text-amber-800">{{ format_currency($data['discrepancy_amount']) }}</td>
                            </tr>
                        @else
                            <tr class="bg-green-50">
                                <td class="py-2 text-sm font-semibold text-green-800" colspan="2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ __('messages.cf_reconciled') }}
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Summary --}}
        <div class="rounded-2xl bg-gray-900 p-6 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-white">
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-400">{{ __('messages.beginning_cash') }}</p>
                    <p class="mt-1 text-xl font-bold">{{ format_currency($data['beginning_cash']) }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-400">{{ __('messages.net_cash_change') }}</p>
                    <p class="mt-1 text-xl font-bold {{ $data['net_cash_change'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                        {{ $data['net_cash_change'] >= 0 ? '+' : '' }}{{ format_currency($data['net_cash_change']) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-400">{{ __('messages.ending_cash') }}</p>
                    <p class="mt-1 text-xl font-bold text-blue-300">{{ format_currency($data['ending_cash']) }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
