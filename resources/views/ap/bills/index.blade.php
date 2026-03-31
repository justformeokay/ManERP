@extends('layouts.app')

@section('title', 'Supplier Bills')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Supplier Bills</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Supplier Bills</h1>
            <p class="mt-1 text-sm text-gray-500">Manage accounts payable and supplier invoices</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('ap.aging') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                Aging Report
            </a>
            @include('components.button', [
                'label' => 'New Bill',
                'type' => 'primary',
                'href' => route('ap.bills.create'),
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
            ])
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm font-medium text-gray-500">Total Outstanding</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ format_currency($summary['total_outstanding']) }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ $summary['total_bills'] }} unpaid bills</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm font-medium text-gray-500">Overdue Amount</p>
            <p class="mt-1 text-2xl font-bold text-red-600">{{ format_currency($summary['overdue_amount']) }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ $summary['overdue_count'] }} overdue bills</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm font-medium text-gray-500">Draft Bills</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ $summary['draft_count'] }}</p>
            <p class="mt-1 text-xs text-gray-400">Pending approval</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm font-medium text-gray-500">This Month Payments</p>
            <p class="mt-1 text-2xl font-bold text-green-600">{{ format_currency(\App\Models\SupplierPayment::whereMonth('payment_date', now()->month)->sum('amount')) }}</p>
            <p class="mt-1 text-xs text-gray-400">Total paid</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search bill number or supplier..."
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <select name="supplier_id" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">All Suppliers</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">All Status</option>
                @foreach(\App\Models\SupplierBill::statusOptions() as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="overdue" value="1" @checked(request('overdue')) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                Overdue Only
            </label>
            <div class="flex gap-2">
                @include('components.button', ['label' => 'Filter', 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search', 'supplier_id', 'status', 'overdue']))
                    @include('components.button', ['label' => 'Clear', 'type' => 'ghost', 'href' => route('ap.bills.index')])
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Bill #</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">PO #</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Bill Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Outstanding</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($bills as $bill)
                        @php
                            $statusColors = [
                                'draft'     => 'bg-gray-100 text-gray-700 ring-gray-500/20',
                                'posted'    => 'bg-blue-100 text-blue-700 ring-blue-500/20',
                                'partial'   => 'bg-amber-100 text-amber-700 ring-amber-500/20',
                                'paid'      => 'bg-green-100 text-green-700 ring-green-500/20',
                                'cancelled' => 'bg-red-100 text-red-700 ring-red-500/20',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors {{ $bill->isOverdue() ? 'bg-red-50/30' : '' }}">
                            <td class="px-6 py-4">
                                <a href="{{ route('ap.bills.show', $bill) }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800">
                                    {{ $bill->bill_number }}
                                </a>
                                @if($bill->isOverdue())
                                    <span class="ml-1 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-red-100 text-red-700">
                                        Overdue
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $bill->supplier->name ?? '—' }}</p>
                                <p class="text-xs text-gray-500">{{ $bill->supplier->company ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                @if($bill->purchaseOrder)
                                    <a href="{{ route('purchasing.show', $bill->purchaseOrder) }}" class="text-primary-600 hover:text-primary-700">
                                        {{ $bill->purchaseOrder->number ?? '—' }}
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ $bill->bill_date->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm {{ $bill->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-700' }}">
                                {{ $bill->due_date->format('M d, Y') }}
                                @if($bill->days_until_due < 0 && !in_array($bill->status, ['paid', 'cancelled']))
                                    <span class="block text-xs text-red-500">{{ abs($bill->days_until_due) }} days overdue</span>
                                @elseif($bill->days_until_due <= 7 && $bill->days_until_due >= 0 && !in_array($bill->status, ['paid', 'cancelled']))
                                    <span class="block text-xs text-amber-500">Due in {{ $bill->days_until_due }} days</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColors[$bill->status] ?? '' }}">
                                    {{ ucfirst($bill->status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-gray-900">
                                {{ format_currency($bill->total) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm {{ $bill->outstanding > 0 ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
                                {{ format_currency($bill->outstanding) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('ap.bills.show', $bill) }}"
                                       class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 transition">
                                        View
                                    </a>
                                    @if($bill->canPay())
                                        <a href="{{ route('ap.bills.pay', $bill) }}"
                                           class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 transition">
                                            Pay
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-10 w-10 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <p class="mt-2 text-sm text-gray-500">No supplier bills found.</p>
                                <div class="mt-4">
                                    @include('components.button', ['label' => 'Create First Bill', 'type' => 'primary', 'href' => route('ap.bills.create')])
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($bills->hasPages())
        <div class="mt-6">{{ $bills->links() }}</div>
    @endif
@endsection
