@extends('layouts.app')

@section('title', 'Invoices')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Invoices</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
            <p class="mt-1 text-sm text-gray-500">Manage invoices and track payments</p>
        </div>
        @include('components.button', ['label' => 'Create Invoice', 'type' => 'primary', 'href' => route('finance.invoices.create')])
    </div>
@endsection

@section('content')
    {{-- Summary Widgets --}}
    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50">
                    <svg class="h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">{{ __('messages.inv_total_receivable') }}</p>
                    <p class="text-lg font-bold text-gray-900">{{ format_currency($summary['total_receivable']) }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50">
                    <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">{{ __('messages.inv_overdue') }}</p>
                    <p class="text-lg font-bold text-red-600">{{ $summary['overdue_count'] }}</p>
                    <p class="text-xs text-red-500">{{ format_currency($summary['overdue_amount']) }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                    <svg class="h-5 w-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">{{ __('messages.inv_unpaid_invoices') }}</p>
                    <p class="text-lg font-bold text-amber-600">{{ $summary['unpaid_count'] }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50">
                    <svg class="h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">{{ __('messages.inv_this_month') }}</p>
                    <p class="text-lg font-bold text-green-600">
                        {{ \App\Models\Invoice::whereMonth('invoice_date', now()->month)->whereYear('invoice_date', now()->year)->whereNotIn('status', ['cancelled'])->count() }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search invoices..."
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <select name="status" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">All Status</option>
                @foreach(\App\Models\Invoice::statusOptions() as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                @include('components.button', ['label' => 'Filter', 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search', 'status']))
                    @include('components.button', ['label' => 'Clear', 'type' => 'ghost', 'href' => route('finance.invoices.index')])
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
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Invoice #</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Sales Order</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Paid</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($invoices as $invoice)
                        @php $statusColors = \App\Models\Invoice::statusColors(); @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('finance.invoices.show', $invoice) }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $invoice->client->name ?? '—' }}</p>
                                <p class="text-xs text-gray-500">{{ $invoice->client->company ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $invoice->salesOrder->number ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ $invoice->invoice_date->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ $invoice->due_date->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColors[$invoice->status] ?? '' }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-gray-900">
                                {{ format_currency($invoice->total_amount) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-700">
                                {{ format_currency($invoice->paid_amount) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <a href="{{ route('finance.invoices.show', $invoice) }}"
                                   class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 transition">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No invoices found.</p>
                                    <a href="{{ route('finance.invoices.create') }}" class="mt-3 text-sm font-medium text-primary-600 hover:text-primary-700">
                                        + Create your first invoice
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
@endsection
