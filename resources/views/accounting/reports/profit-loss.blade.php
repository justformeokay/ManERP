@extends('layouts.app')

@section('title', 'Profit & Loss')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Profit & Loss</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Profit & Loss</h1>
        <p class="mt-1 text-sm text-gray-500">Income Statement</p>
    </div>
@endsection

@section('content')
    {{-- Date Range Filter --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="from" class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="from" id="from" value="{{ $data['start_date'] }}"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div>
                <label for="to" class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="to" id="to" value="{{ $data['end_date'] }}"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div class="flex gap-2">
                @include('components.button', ['label' => 'Generate', 'type' => 'primary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['from', 'to']))
                    @include('components.button', ['label' => 'YTD', 'type' => 'ghost', 'href' => route('accounting.profit-loss')])
                @endif
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Revenue</p>
            <p class="mt-1 text-2xl font-bold text-green-700">{{ format_currency($data['total_revenue']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Expenses</p>
            <p class="mt-1 text-2xl font-bold text-red-600">{{ format_currency($data['total_expense']) }}</p>
        </div>
        <div class="rounded-2xl p-5 shadow-sm ring-1 {{ $data['net_profit'] >= 0 ? 'bg-green-50 ring-green-200' : 'bg-red-50 ring-red-200' }}">
            <p class="text-xs font-medium uppercase tracking-wider {{ $data['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $data['net_profit'] >= 0 ? 'Net Profit' : 'Net Loss' }}
            </p>
            <p class="mt-1 text-2xl font-bold {{ $data['net_profit'] >= 0 ? 'text-green-800' : 'text-red-800' }}">
                {{ format_currency(abs($data['net_profit'])) }}
            </p>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Revenue --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-green-50/50">
                <h3 class="text-base font-semibold text-green-900">Revenue</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Account</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($data['revenue'] as $account)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-3 text-sm font-mono font-semibold text-gray-900">{{ $account->code }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $account->name }}</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-green-700">{{ format_currency($account->balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-400">No revenue recorded in this period.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-green-50/50">
                        <tr>
                            <td colspan="2" class="px-6 py-3 text-right text-sm font-bold text-green-900">Total Revenue</td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-green-900">{{ format_currency($data['total_revenue']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Expenses --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-red-50/50">
                <h3 class="text-base font-semibold text-red-900">Expenses</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Account</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($data['expenses'] as $account)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-3 text-sm font-mono font-semibold text-gray-900">{{ $account->code }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $account->name }}</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-red-600">{{ format_currency($account->balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-400">No expenses recorded in this period.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-red-50/50">
                        <tr>
                            <td colspan="2" class="px-6 py-3 text-right text-sm font-bold text-red-900">Total Expenses</td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-red-900">{{ format_currency($data['total_expense']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Net Result --}}
        <div class="rounded-2xl shadow-sm ring-1 overflow-hidden {{ $data['net_profit'] >= 0 ? 'bg-green-50 ring-green-200' : 'bg-red-50 ring-red-200' }}">
            <table class="min-w-full">
                <tbody>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-700">Total Revenue</td>
                        <td class="px-6 py-3 text-right text-sm font-semibold text-green-700">{{ format_currency($data['total_revenue']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-700">Total Expenses</td>
                        <td class="px-6 py-3 text-right text-sm font-semibold text-red-600">({{ format_currency($data['total_expense']) }})</td>
                    </tr>
                    <tr class="border-t-2 {{ $data['net_profit'] >= 0 ? 'border-green-300' : 'border-red-300' }}">
                        <td class="px-6 py-4 text-base font-bold {{ $data['net_profit'] >= 0 ? 'text-green-900' : 'text-red-900' }}">
                            {{ $data['net_profit'] >= 0 ? 'Net Profit' : 'Net Loss' }}
                        </td>
                        <td class="px-6 py-4 text-right text-base font-bold {{ $data['net_profit'] >= 0 ? 'text-green-900' : 'text-red-900' }}">
                            {{ format_currency(abs($data['net_profit'])) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-4 text-xs text-gray-400 text-right">
        Period: {{ \Carbon\Carbon::parse($data['start_date'])->format('M d, Y') }} — {{ \Carbon\Carbon::parse($data['end_date'])->format('M d, Y') }}
    </p>
@endsection
