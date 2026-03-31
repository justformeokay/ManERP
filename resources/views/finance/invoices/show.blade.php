@extends('layouts.app')

@section('title', $invoice->invoice_number)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('finance.invoices.index') }}" class="hover:text-gray-700">Invoices</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $invoice->invoice_number }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">{{ $invoice->invoice_number }}</h1>
            @php $statusColors = \App\Models\Invoice::statusColors(); @endphp
            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColors[$invoice->status] ?? '' }}">
                {{ ucfirst($invoice->status) }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            @if(!in_array($invoice->status, ['paid', 'cancelled']))
                <form method="POST" action="{{ route('finance.invoices.cancel', $invoice) }}"
                      onsubmit="return confirm('Cancel invoice {{ $invoice->invoice_number }}? All payments will be voided.')">
                    @csrf
                    @include('components.button', ['label' => 'Cancel Invoice', 'type' => 'danger', 'buttonType' => 'submit'])
                </form>
            @endif
            @include('components.button', ['label' => '← Back', 'type' => 'ghost', 'href' => route('finance.invoices.index')])
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left column: Invoice info --}}
        <div class="space-y-6">
            {{-- Invoice Info --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Invoice Information</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Invoice Date</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $invoice->invoice_date->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Due Date</dt>
                        <dd class="text-sm font-medium {{ $invoice->due_date->isPast() && $invoice->status !== 'paid' ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $invoice->due_date->format('M d, Y') }}
                            @if($invoice->due_date->isPast() && !in_array($invoice->status, ['paid', 'cancelled']))
                                <span class="text-xs text-red-500">(Overdue)</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Sales Order</dt>
                        <dd class="text-sm font-medium">
                            <a href="{{ route('sales.show', $invoice->salesOrder) }}" class="text-primary-600 hover:text-primary-700">
                                {{ $invoice->salesOrder->number }}
                            </a>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Created By</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $invoice->creator->name ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Client Info --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Client</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Name</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $invoice->client->name }}</dd>
                    </div>
                    @if($invoice->client->company)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Company</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $invoice->client->company }}</dd>
                        </div>
                    @endif
                    @if($invoice->client->email)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Email</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $invoice->client->email }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Summary --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Summary</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Subtotal</dt>
                        <dd class="text-sm text-gray-900">{{ number_format($invoice->subtotal, 2) }}</dd>
                    </div>
                    @if($invoice->tax_amount > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Tax</dt>
                            <dd class="text-sm text-gray-900">{{ number_format($invoice->tax_amount, 2) }}</dd>
                        </div>
                    @endif
                    @if($invoice->discount > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Discount</dt>
                            <dd class="text-sm text-red-600">-{{ number_format($invoice->discount, 2) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-3">
                        <dt class="text-sm font-semibold text-gray-900">Total</dt>
                        <dd class="text-sm font-bold text-gray-900">{{ number_format($invoice->total_amount, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Paid</dt>
                        <dd class="text-sm font-semibold text-green-600">{{ number_format($invoice->paid_amount, 2) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3">
                        <dt class="text-sm font-semibold text-gray-900">Remaining Balance</dt>
                        <dd class="text-sm font-bold {{ $invoice->remaining_balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format($invoice->remaining_balance, 2) }}
                        </dd>
                    </div>
                </dl>
            </div>

            @if($invoice->notes)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Notes</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $invoice->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Right column: Items + Payments --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Invoice Items --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Invoice Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Qty</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Unit Price</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Discount</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($invoice->items as $i => $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-6 py-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $item->product->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-500">{{ $item->product->sku ?? '' }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700">{{ number_format($item->quantity, 0) }}</td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700">{{ number_format($item->discount, 2) }}</td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Payment History --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Payment History</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Reference</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($invoice->payments as $i => $payment)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-700">{{ $payment->payment_date->format('M d, Y') }}</td>
                                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $payment->reference_number ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-green-600">{{ number_format($payment->amount, 2) }}</td>
                                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-500">{{ $payment->creator->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">No payments recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Record Payment Form --}}
            @if(!in_array($invoice->status, ['paid', 'cancelled']))
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Record Payment</h3>
                    <form method="POST" action="{{ route('finance.payments.store') }}">
                        @csrf
                        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
                                <input type="number" name="amount" id="amount" step="0.01" min="0.01"
                                    max="{{ $invoice->remaining_balance }}"
                                    value="{{ old('amount', $invoice->remaining_balance) }}"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    required>
                                @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-1">Payment Date <span class="text-red-500">*</span></label>
                                <input type="date" name="payment_date" id="payment_date"
                                    value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    required>
                                @error('payment_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                                <select name="payment_method" id="payment_method"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    required>
                                    <option value="">Select method</option>
                                    @foreach(\App\Models\Payment::methodOptions() as $method)
                                        <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                                <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number') }}"
                                    placeholder="e.g. Check #, Transfer ID"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                                @error('reference_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="payment_notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" id="payment_notes" rows="2" placeholder="Optional payment notes..."
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            @include('components.button', ['label' => 'Record Payment', 'type' => 'primary', 'buttonType' => 'submit'])
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
