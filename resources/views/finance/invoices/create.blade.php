@extends('layouts.app')

@section('title', 'Create Invoice')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('finance.invoices.index') }}" class="hover:text-gray-700">Invoices</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Create Invoice</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Invoice</h1>
            <p class="mt-1 text-sm text-gray-500">Generate an invoice from a sales order</p>
        </div>
        @include('components.button', ['label' => '← Back', 'type' => 'ghost', 'href' => route('finance.invoices.index')])
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        <form method="POST" action="{{ route('finance.invoices.store') }}" class="space-y-6">
            @csrf

            {{-- Sales Order Selection --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Sales Order</h3>

                <div class="space-y-4">
                    <div>
                        <label for="sales_order_id" class="block text-sm font-medium text-gray-700 mb-1">Select Sales Order <span class="text-red-500">*</span></label>
                        <select name="sales_order_id" id="sales_order_id"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                            required>
                            <option value="">Choose a sales order...</option>
                            @foreach($salesOrders as $order)
                                <option value="{{ $order->id }}"
                                    @selected(old('sales_order_id', $salesOrder?->id) == $order->id)
                                    data-client="{{ $order->client->name ?? '' }}"
                                    data-total="{{ number_format($order->total, 2) }}">
                                    {{ $order->number }} — {{ $order->client->name ?? 'N/A' }} — {{ number_format($order->total, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('sales_order_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date <span class="text-red-500">*</span></label>
                        <input type="date" name="due_date" id="due_date"
                            value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                            required>
                        @error('due_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Optional invoice notes..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">{{ old('notes') }}</textarea>
                        @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                @include('components.button', ['label' => 'Cancel', 'type' => 'ghost', 'href' => route('finance.invoices.index')])
                @include('components.button', ['label' => 'Generate Invoice', 'type' => 'primary', 'buttonType' => 'submit'])
            </div>
        </form>
    </div>
@endsection
