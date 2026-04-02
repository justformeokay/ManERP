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
    {{-- Date Range Filter --}}
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
        </form>
    </div>

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
                                <tr>
                                    <td class="py-2 pl-4 text-sm text-gray-600">{{ __('messages.cf_' . $item['label']) }}</td>
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
                            <tr>
                                <td class="py-2 text-sm text-gray-600">{{ __('messages.cf_' . $item['label']) }}</td>
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
                            <tr>
                                <td class="py-2 text-sm text-gray-600">{{ __('messages.cf_' . $item['label']) }}</td>
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
