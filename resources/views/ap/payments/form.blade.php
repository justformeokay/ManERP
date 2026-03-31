@extends('layouts.app')

@section('title', 'Record Payment — ' . $bill->bill_number)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('ap.bills.index') }}" class="hover:text-gray-700">Supplier Bills</a>
    <span class="mx-1">/</span>
    <a href="{{ route('ap.bills.show', $bill) }}" class="hover:text-gray-700">{{ $bill->bill_number }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Record Payment</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Record Payment</h1>
        <p class="mt-1 text-sm text-gray-500">
            Record a payment for bill {{ $bill->bill_number }} — {{ $bill->supplier->name ?? 'Unknown Supplier' }}
        </p>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form --}}
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('ap.bills.pay', $bill) }}" class="space-y-6">
                @csrf

                <input type="hidden" name="supplier_bill_id" value="{{ $bill->id }}">

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Payment Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-1">Payment Date <span class="text-red-500">*</span></label>
                            <input type="date" name="payment_date" id="payment_date" required
                                value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                            @error('payment_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" id="amount" step="0.01" min="0.01" max="{{ $bill->outstanding }}" required
                                value="{{ old('amount', $bill->outstanding) }}"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                            <p class="mt-1 text-xs text-gray-500">Maximum: {{ number_format($bill->outstanding, 2) }}</p>
                            @error('amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                            <select name="payment_method" id="payment_method" required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                                @foreach(\App\Models\SupplierPayment::paymentMethodOptions() as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method') === $method)>
                                        {{ ucfirst(str_replace('_', ' ', $method)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_method') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                            <input type="text" name="reference_number" id="reference_number"
                                value="{{ old('reference_number') }}"
                                placeholder="Check #, Transfer ID, etc."
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                            @error('reference_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bank_account_code" class="block text-sm font-medium text-gray-700 mb-1">Bank/Cash Account</label>
                            <select name="bank_account_code" id="bank_account_code"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                                <option value="">— Default (1100 Cash/Bank) —</option>
                                @foreach($bankAccounts as $acc)
                                    <option value="{{ $acc->code }}" @selected(old('bank_account_code') == $acc->code)>
                                        {{ $acc->code }} — {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('bank_account_code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea name="notes" id="notes" rows="3"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                                placeholder="Optional notes...">{{ old('notes') }}</textarea>
                            @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-4">
                    @include('components.button', ['label' => 'Cancel', 'type' => 'ghost', 'href' => route('ap.bills.show', $bill)])
                    @include('components.button', [
                        'label' => 'Record Payment',
                        'type' => 'primary',
                        'buttonType' => 'submit',
                        'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
                    ])
                </div>
            </form>
        </div>

        {{-- Bill Summary --}}
        <div class="space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Bill Summary</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Bill Number</dt>
                        <dd class="font-medium text-gray-900">{{ $bill->bill_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Supplier</dt>
                        <dd class="font-medium text-gray-900">{{ $bill->supplier->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Due Date</dt>
                        <dd class="font-medium {{ $bill->isOverdue() ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $bill->due_date->format('M d, Y') }}
                        </dd>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-gray-100">
                        <dt class="text-gray-500">Bill Total</dt>
                        <dd class="font-semibold text-gray-900">{{ number_format($bill->total, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Previously Paid</dt>
                        <dd class="font-semibold text-green-600">{{ number_format($bill->paid_amount, 2) }}</dd>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-gray-100">
                        <dt class="text-gray-900 font-semibold">Outstanding</dt>
                        <dd class="text-lg font-bold text-red-600">{{ number_format($bill->outstanding, 2) }}</dd>
                    </div>
                </dl>
            </div>

            @if($bill->isOverdue())
                <div class="rounded-2xl bg-red-50 p-4 border border-red-200">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-red-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <div>
                            <h4 class="text-sm font-semibold text-red-800">Bill is Overdue</h4>
                            <p class="mt-1 text-sm text-red-700">
                                This bill is {{ abs($bill->days_until_due) }} days past due.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
