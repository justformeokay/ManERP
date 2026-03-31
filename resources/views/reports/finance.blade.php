@extends('layouts.app')

@section('title', 'Finance Report')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('reports.index') }}" class="hover:text-gray-700">Reports</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Finance Report</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Finance Report</h1>
            <p class="mt-1 text-sm text-gray-500">Invoice and payment overview</p>
        </div>
        <div class="flex items-center gap-2">
            @include('components.button', ['label' => '← Back to Reports', 'type' => 'ghost', 'href' => route('reports.index')])
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @include('components.stat-card', [
            'title' => 'Total Revenue',
            'value' => format_currency($totalRevenue),
            'iconBg' => 'bg-green-50 text-green-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Total Paid',
            'value' => format_currency($totalPaid),
            'iconBg' => 'bg-blue-50 text-blue-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Outstanding',
            'value' => format_currency($totalOutstanding),
            'iconBg' => 'bg-amber-50 text-amber-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        ])
        @include('components.stat-card', [
            'title' => 'Invoices',
            'value' => number_format($invoiceCount),
            'iconBg' => 'bg-purple-50 text-purple-600',
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>',
        ])
    </div>

    {{-- Invoice Status Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Invoices by Status</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Count</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @php $statusColors = \App\Models\Invoice::statusColors(); @endphp
                        @forelse($byStatus as $row)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColors[$row->status] ?? '' }}">
                                        {{ ucfirst($row->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-700">{{ $row->count }}</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ format_currency($row->total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-400">No invoice data for this period</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Clients --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Top Clients by Revenue</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Client</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Invoices</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Billed</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Paid</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($topClients as $client)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $client->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $client->company }}</p>
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-700">{{ $client->invoice_count }}</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ format_currency($client->total_billed) }}</td>
                                <td class="px-6 py-3 text-right text-sm text-green-600">{{ format_currency($client->total_paid) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">No client data for this period</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Payments --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900">Recent Payments</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Reference</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($recentPayments as $payment)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-700">{{ $payment->payment_date->format('M d, Y') }}</td>
                            <td class="px-6 py-3">
                                <a href="{{ route('finance.invoices.show', $payment->invoice) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                                    {{ $payment->invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">{{ $payment->invoice->client->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td class="px-6 py-3 text-sm text-gray-500">{{ $payment->reference_number ?? '—' }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-green-600">{{ format_currency($payment->amount) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">No payments recorded in this period</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
