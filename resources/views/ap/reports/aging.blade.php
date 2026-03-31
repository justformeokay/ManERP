@extends('layouts.app')

@section('title', 'AP Aging Report')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('ap.bills.index') }}" class="hover:text-gray-700">Supplier Bills</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Aging Report</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Accounts Payable Aging Report</h1>
            <p class="mt-1 text-sm text-gray-500">Outstanding payables grouped by aging bucket</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('ap.bills.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                Back to Bills
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm font-medium text-gray-500">Total Outstanding</p>
            <p class="mt-1 text-xl font-bold text-gray-900">{{ format_currency($totals['total']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-green-200 border-l-4 border-green-500">
            <p class="text-sm font-medium text-gray-500">Current</p>
            <p class="mt-1 text-xl font-bold text-green-600">{{ format_currency($totals['current']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-yellow-200 border-l-4 border-yellow-500">
            <p class="text-sm font-medium text-gray-500">1-30 Days</p>
            <p class="mt-1 text-xl font-bold text-yellow-600">{{ format_currency($totals['1-30']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-orange-200 border-l-4 border-orange-500">
            <p class="text-sm font-medium text-gray-500">31-60 Days</p>
            <p class="mt-1 text-xl font-bold text-orange-600">{{ format_currency($totals['31-60']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-red-200 border-l-4 border-red-500">
            <p class="text-sm font-medium text-gray-500">61-90 Days</p>
            <p class="mt-1 text-xl font-bold text-red-500">{{ format_currency($totals['61-90']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-red-300 border-l-4 border-red-700">
            <p class="text-sm font-medium text-gray-500">Over 90 Days</p>
            <p class="mt-1 text-xl font-bold text-red-700">{{ format_currency($totals['90+']) }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <select name="supplier_id" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">All Suppliers</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                @include('components.button', ['label' => 'Filter', 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request('supplier_id'))
                    @include('components.button', ['label' => 'Clear', 'type' => 'ghost', 'href' => route('ap.aging')])
                @endif
            </div>
        </form>
    </div>

    {{-- Aging Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Supplier</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-green-600">Current</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-yellow-600">1-30</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-orange-600">31-60</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-red-500">61-90</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-red-700">90+</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-900">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($agingReport as $row)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900">{{ $row['supplier_name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $row['bill_count'] }} bill(s)</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm {{ $row['current'] > 0 ? 'text-green-600 font-medium' : 'text-gray-300' }}">
                                {{ format_currency($row['current']) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm {{ $row['1-30'] > 0 ? 'text-yellow-600 font-medium' : 'text-gray-300' }}">
                                {{ format_currency($row['1-30']) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm {{ $row['31-60'] > 0 ? 'text-orange-600 font-medium' : 'text-gray-300' }}">
                                {{ format_currency($row['31-60']) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm {{ $row['61-90'] > 0 ? 'text-red-500 font-medium' : 'text-gray-300' }}">
                                {{ format_currency($row['61-90']) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm {{ $row['90+'] > 0 ? 'text-red-700 font-semibold' : 'text-gray-300' }}">
                                {{ format_currency($row['90+']) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-gray-900">
                                {{ format_currency($row['total']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-10 w-10 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <p class="mt-2 text-sm text-gray-500">No outstanding payables.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($agingReport) > 0)
                    <tfoot class="bg-gray-100">
                        <tr>
                            <td class="px-6 py-4 font-bold text-gray-900">Total</td>
                            <td class="px-6 py-4 text-right font-bold text-green-600">{{ format_currency($totals['current']) }}</td>
                            <td class="px-6 py-4 text-right font-bold text-yellow-600">{{ format_currency($totals['1-30']) }}</td>
                            <td class="px-6 py-4 text-right font-bold text-orange-600">{{ format_currency($totals['31-60']) }}</td>
                            <td class="px-6 py-4 text-right font-bold text-red-500">{{ format_currency($totals['61-90']) }}</td>
                            <td class="px-6 py-4 text-right font-bold text-red-700">{{ format_currency($totals['90+']) }}</td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900">{{ format_currency($totals['total']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Detail Bills --}}
    @if(count($agingReport) > 0)
        <div class="mt-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Overdue Bills Detail</h3>
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Bill #</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Days Overdue</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Aging</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Outstanding</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($overdueBills as $bill)
                                @php
                                    $bucket = $bill->aging_bucket;
                                    $bucketColors = [
                                        'current'  => 'bg-green-100 text-green-700',
                                        '1-30'     => 'bg-yellow-100 text-yellow-700',
                                        '31-60'    => 'bg-orange-100 text-orange-700',
                                        '61-90'    => 'bg-red-100 text-red-700',
                                        '90+'      => 'bg-red-200 text-red-800',
                                    ];
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('ap.bills.show', $bill) }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800">
                                            {{ $bill->bill_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $bill->supplier->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ $bill->due_date->format('M d, Y') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-red-600">
                                        {{ abs($bill->days_until_due) }} days
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $bucketColors[$bucket] ?? '' }}">
                                            @if($bucket === '90+')
                                                Over 90 Days
                                            @else
                                                {{ $bucket }} Days
                                            @endif
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-red-600">
                                        {{ format_currency($bill->outstanding) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        @if($bill->canPay())
                                            <a href="{{ route('ap.bills.pay', $bill) }}"
                                               class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 transition">
                                                Pay Now
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
