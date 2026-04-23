@extends('layouts.app')

@section('title', __('messages.create_debit_note'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.debit-notes.index') }}" class="hover:text-gray-700">{{ __('messages.debit_notes_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.create_debit_note') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.create_debit_note') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.create_debit_note_subtitle') }}</p>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('accounting.debit-notes.store') }}" class="max-w-4xl" x-data="noteForm()">
        @csrf
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-5">
            {{-- Bill & Warehouse --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="supplier_bill_id" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('messages.supplier_bill') }}
                        @include('components.academy-tooltip', ['slug' => 'debit-note'])
                    </label>
                    <select name="supplier_bill_id" id="supplier_bill_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                        <option value="">-- {{ __('messages.select_bill') }} --</option>
                        @foreach($bills as $bill)
                            <option value="{{ $bill->id }}" {{ old('supplier_bill_id') == $bill->id ? 'selected' : '' }}>
                                {{ $bill->bill_number }} - {{ $bill->supplier?->name }} ({{ format_currency($bill->total) }})
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_bill_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.note_warehouse') }}</label>
                    <select name="warehouse_id" id="warehouse_id"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                        <option value="">-- {{ __('messages.note_no_warehouse') }} --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">{{ __('messages.note_warehouse_hint') }}</p>
                </div>
            </div>

            {{-- Date, Amount, Tax --}}
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.date') }}</label>
                    <input type="date" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.amount') }}</label>
                    <input type="text" name="amount" id="amount" value="{{ old('amount') }}" x-currency required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tax_amount" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.tax_amount') }}</label>
                    <input type="text" name="tax_amount" id="tax_amount" value="{{ old('tax_amount', 0) }}" x-currency
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
            </div>

            {{-- Reason & Notes --}}
            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.reason') }}</label>
                <textarea name="reason" id="reason" rows="2" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">{{ old('reason') }}</textarea>
                @error('reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.notes') }}</label>
                <textarea name="notes" id="notes" rows="2"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">{{ old('notes') }}</textarea>
            </div>

            {{-- Line Items --}}
            <div class="border-t border-gray-100 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('messages.note_return_items') }}</h3>
                    <button type="button" @click="addItem()"
                        class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('messages.note_add_item') }}
                    </button>
                </div>
                <p class="text-xs text-gray-400 mb-3">{{ __('messages.note_items_hint') }}</p>

                <template x-for="(item, idx) in items" :key="idx">
                    <div class="grid grid-cols-12 gap-3 mb-2 items-end">
                        <div class="col-span-5">
                            <label class="block text-xs text-gray-500 mb-1" x-show="idx === 0">{{ __('messages.product') }}</label>
                            <select :name="`items[${idx}][product_id]`" x-model="item.product_id" required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                                <option value="">-- {{ __('messages.select_product') }} --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 mb-1" x-show="idx === 0">{{ __('messages.quantity') }}</label>
                            <input type="number" :name="`items[${idx}][quantity]`" x-model="item.quantity" step="0.01" min="0.01" required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-500 mb-1" x-show="idx === 0">{{ __('messages.unit_price') }}</label>
                            <input type="number" :name="`items[${idx}][unit_price]`" x-model="item.unit_price" step="0.01" min="0" required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-2 flex items-end gap-2">
                            <span class="text-sm font-medium text-gray-700 pb-2" x-text="formatCurrency(item.quantity * item.unit_price)"></span>
                            <button type="button" @click="removeItem(idx)" class="pb-2 text-red-400 hover:text-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('accounting.debit-notes.index')])
                @include('components.button', ['label' => __('messages.save'), 'type' => 'primary', 'buttonType' => 'submit'])
            </div>
        </div>
    </form>

    <script>
        function noteForm() {
            return {
                items: [],
                addItem() { this.items.push({ product_id: '', quantity: 1, unit_price: 0 }); },
                removeItem(idx) { this.items.splice(idx, 1); },
                formatCurrency(val) { return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(val || 0); },
            };
        }
    </script>
@endsection
