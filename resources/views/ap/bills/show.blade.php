@extends('layouts.app')

@section('title', $bill->bill_number)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('ap.bills.index') }}" class="hover:text-gray-700">Supplier Bills</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $bill->bill_number }}</span>
@endsection

@php
    $statusColors = [
        'draft'     => 'bg-gray-100 text-gray-700 ring-gray-500/20',
        'posted'    => 'bg-blue-100 text-blue-700 ring-blue-500/20',
        'partial'   => 'bg-amber-100 text-amber-700 ring-amber-500/20',
        'paid'      => 'bg-green-100 text-green-700 ring-green-500/20',
        'cancelled' => 'bg-red-100 text-red-700 ring-red-500/20',
    ];
@endphp

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">{{ $bill->bill_number }}</h1>
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColors[$bill->status] ?? '' }}">
                    {{ ucfirst($bill->status) }}
                </span>
                @if($bill->isOverdue())
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-700 ring-1 ring-red-500/20">
                        Overdue
                    </span>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500">
                {{ $bill->supplier->name ?? 'Unknown Supplier' }}
                @if($bill->supplier_invoice_number)
                    — Ref: {{ $bill->supplier_invoice_number }}
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($bill->canEdit())
                @include('components.button', [
                    'label' => 'Edit',
                    'type' => 'secondary',
                    'href' => route('ap.bills.edit', $bill),
                    'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
                ])
            @endif

            @if($bill->canPost())
                <form method="POST" action="{{ route('ap.bills.post', $bill) }}" class="inline" onsubmit="return confirm('Post this bill and create journal entry?');">
                    @csrf
                    @include('components.button', [
                        'label' => 'Post Bill',
                        'type' => 'primary',
                        'buttonType' => 'submit',
                        'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                    ])
                </form>
            @endif

            @if($bill->canPay())
                @include('components.button', [
                    'label' => 'Record Payment',
                    'type' => 'success',
                    'href' => route('ap.bills.pay', $bill),
                    'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
                ])
            @endif

            @if($bill->status === 'posted' || $bill->status === 'partial')
                <form method="POST" action="{{ route('ap.bills.cancel', $bill) }}" class="inline" onsubmit="return confirm('Cancel this bill? This will create a reversing journal entry.');">
                    @csrf
                    @include('components.button', [
                        'label' => 'Cancel',
                        'type' => 'danger',
                        'buttonType' => 'submit',
                    ])
                </form>
            @endif

            @if($bill->status === 'draft')
                <form method="POST" action="{{ route('ap.bills.destroy', $bill) }}" class="inline" onsubmit="return confirm('Delete this draft bill?');">
                    @csrf
                    @method('DELETE')
                    @include('components.button', [
                        'label' => 'Delete',
                        'type' => 'danger',
                        'buttonType' => 'submit',
                    ])
                </form>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Bill Details --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Bill Details</h3>
                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Supplier</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $bill->supplier->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Purchase Order</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            @if($bill->purchaseOrder)
                                <a href="{{ route('purchasing.show', $bill->purchaseOrder) }}" class="text-primary-600 hover:text-primary-700">
                                    {{ $bill->purchaseOrder->number }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Supplier Invoice #</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $bill->supplier_invoice_number ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Bill Date</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $bill->bill_date->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Due Date</dt>
                        <dd class="mt-1 font-medium {{ $bill->isOverdue() ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $bill->due_date->format('M d, Y') }}
                            @if($bill->isOverdue())
                                <span class="text-xs">({{ abs($bill->days_until_due) }} days overdue)</span>
                            @elseif($bill->days_until_due <= 7 && $bill->days_until_due >= 0 && !in_array($bill->status, ['paid', 'cancelled']))
                                <span class="text-xs text-amber-500">(Due in {{ $bill->days_until_due }} days)</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Expense Account</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $bill->expense_account_code ?: 'Auto' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Items --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Bill Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Qty</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Unit Price</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($bill->items as $index => $item)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        @if($item->product)
                                            {{ $item->product->name }}
                                            <span class="text-xs text-gray-400">({{ $item->product->sku }})</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $item->description }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900">{{ format_currency($item->unit_price) }}</td>
                                    <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">{{ format_currency($item->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50/50">
                            <tr>
                                <td colspan="5" class="px-6 py-3 text-right font-semibold text-gray-900">Subtotal</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ format_currency($bill->subtotal) }}</td>
                            </tr>
                            @if($bill->tax > 0)
                            <tr>
                                <td colspan="5" class="px-6 py-2 text-right text-sm text-gray-500">Tax</td>
                                <td class="px-6 py-2 text-right text-sm text-gray-900">{{ format_currency($bill->tax) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="5" class="px-6 py-3 text-right text-base font-bold text-gray-900">Total</td>
                                <td class="px-6 py-3 text-right text-base font-bold text-gray-900">{{ format_currency($bill->total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Payments History --}}
            @if($bill->payments->count() > 0)
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">Payment History</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Payment #</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Reference</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($bill->payments as $payment)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="px-6 py-4 text-sm font-medium text-primary-700">{{ $payment->payment_number }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ $payment->payment_date->format('M d, Y') }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ $payment->reference_number ?: '—' }}</td>
                                        <td class="px-6 py-4 text-sm text-right font-medium text-green-600">{{ format_currency($payment->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50/50">
                                <tr>
                                    <td colspan="4" class="px-6 py-3 text-right font-semibold text-gray-900">Total Paid</td>
                                    <td class="px-6 py-3 text-right font-semibold text-green-600">{{ format_currency($bill->paid_amount) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Notes --}}
            @if($bill->notes)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Notes</h3>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $bill->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Summary Card --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Summary</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Bill Total</dt>
                        <dd class="font-semibold text-gray-900">{{ format_currency($bill->total) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Paid Amount</dt>
                        <dd class="font-semibold text-green-600">{{ format_currency($bill->paid_amount) }}</dd>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-gray-100">
                        <dt class="text-gray-900 font-semibold">Outstanding</dt>
                        <dd class="text-lg font-bold {{ $bill->outstanding > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ format_currency($bill->outstanding) }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Aging --}}
            @if($bill->outstanding > 0 && !in_array($bill->status, ['cancelled', 'paid']))
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">Aging</h3>
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
                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium {{ $bucketColors[$bucket] ?? '' }}">
                        @if($bucket === 'current')
                            Current (Not Due)
                        @elseif($bucket === '90+')
                            Over 90 Days
                        @else
                            {{ $bucket }} Days Overdue
                        @endif
                    </span>
                    @if($bill->isOverdue())
                        <p class="mt-2 text-xs text-red-500">
                            This bill is {{ abs($bill->days_until_due) }} days past due.
                        </p>
                    @endif
                </div>
            @endif

            {{-- Journal Entry --}}
            @if($bill->journalEntry)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">Journal Entry</h3>
                    <a href="{{ route('accounting.journal-entries.show', $bill->journalEntry) }}" 
                       class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        {{ $bill->journalEntry->entry_number ?? 'View Entry' }}
                    </a>
                    <p class="mt-1 text-xs text-gray-500">{{ $bill->journalEntry->date?->format('M d, Y') }}</p>
                </div>
            @endif

            {{-- Activity --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Activity</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Created</dt>
                        <dd class="text-gray-700">{{ $bill->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                    @if($bill->posted_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Posted</dt>
                            <dd class="text-gray-700">{{ $bill->posted_at->format('M d, Y H:i') }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Last Updated</dt>
                        <dd class="text-gray-700">{{ $bill->updated_at->format('M d, Y H:i') }}</dd>
                    </div>
                    @if($bill->createdBy)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Created By</dt>
                            <dd class="text-gray-700">{{ $bill->createdBy->name }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
@endsection
