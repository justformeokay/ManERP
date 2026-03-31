@extends('layouts.app')

@php
    $isEdit = $bill->exists;
    $pageTitle = $isEdit ? 'Edit ' . $bill->bill_number : 'New Supplier Bill';
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('ap.bills.index') }}" class="hover:text-gray-700">Supplier Bills</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $isEdit ? 'Update bill details and items.' : 'Record a new supplier invoice or bill.' }}
        </p>
    </div>
@endsection

@section('content')
    @php
        $initialItems = [];
        if ($isEdit && $bill->items) {
            foreach ($bill->items as $item) {
                $initialItems[] = [
                    'product_id'  => $item->product_id,
                    'description' => $item->description,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->unit_price,
                ];
            }
        }

        $productData = $products->map(function ($p) {
            return [
                'id'         => $p->id,
                'name'       => $p->name,
                'sku'        => $p->sku,
                'cost_price' => (float) $p->cost_price,
                'unit'       => $p->unit,
            ];
        })->values()->toArray();
    @endphp

    <form method="POST"
          action="{{ $isEdit ? route('ap.bills.update', $bill) : route('ap.bills.store') }}"
          class="space-y-6"
          x-data="billForm()">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Bill Details --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Bill Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Supplier <span class="text-red-500">*</span></label>
                    <select name="supplier_id" id="supplier_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">Select supplier...</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id', $bill->supplier_id) == $supplier->id)>
                                {{ $supplier->name }} {{ $supplier->company ? '— ' . $supplier->company : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="purchase_order_id" class="block text-sm font-medium text-gray-700 mb-1">Purchase Order</label>
                    <select name="purchase_order_id" id="purchase_order_id"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">— None —</option>
                        @foreach($purchaseOrders as $po)
                            <option value="{{ $po->id }}" @selected(old('purchase_order_id', $bill->purchase_order_id) == $po->id)>
                                {{ $po->number }} — {{ $po->supplier->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('purchase_order_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="supplier_invoice_number" class="block text-sm font-medium text-gray-700 mb-1">Supplier Invoice #</label>
                    <input type="text" name="supplier_invoice_number" id="supplier_invoice_number"
                        value="{{ old('supplier_invoice_number', $bill->supplier_invoice_number) }}"
                        placeholder="External invoice number"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('supplier_invoice_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="bill_date" class="block text-sm font-medium text-gray-700 mb-1">Bill Date <span class="text-red-500">*</span></label>
                    <input type="date" name="bill_date" id="bill_date" required
                        value="{{ old('bill_date', $bill->bill_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('bill_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date <span class="text-red-500">*</span></label>
                    <input type="date" name="due_date" id="due_date" required
                        value="{{ old('due_date', $bill->due_date?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('due_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="expense_account_code" class="block text-sm font-medium text-gray-700 mb-1">Expense Account</label>
                    <select name="expense_account_code" id="expense_account_code"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">— Auto (Expense/Inventory) —</option>
                        @foreach($expenseAccounts as $acc)
                            <option value="{{ $acc->code }}" @selected(old('expense_account_code', $bill->expense_account_code) == $acc->code)>
                                {{ $acc->code }} — {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('expense_account_code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Bill Items --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Bill Items</h3>
                <button type="button" @click="addItem()"
                    class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Add Item
                </button>
            </div>

            @error('items') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4">
                        <div class="flex flex-col sm:flex-row gap-3 items-start">
                            <div class="flex-1 min-w-0">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Product</label>
                                <select :name="`items[${index}][product_id]`" x-model="item.product_id"
                                    @change="onProductChange(index)"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                                    <option value="">Select product (optional)</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 min-w-0">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Description *</label>
                                <input type="text" :name="`items[${index}][description]`" x-model="item.description" required
                                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                                    placeholder="Item description">
                            </div>
                            <div class="w-24">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Qty *</label>
                                <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity" step="0.01" min="0.01" required
                                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                                    placeholder="0">
                            </div>
                            <div class="w-28">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Unit Price *</label>
                                <input type="number" :name="`items[${index}][unit_price]`" x-model="item.unit_price" step="0.01" min="0" required
                                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                                    placeholder="0.00">
                            </div>
                            <div class="pt-5">
                                <button type="button" @click="removeItem(index)"
                                    class="rounded-lg p-2 text-red-500 hover:bg-red-50 transition" title="Remove">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-end mt-2 px-1">
                            <span class="text-xs font-medium text-gray-600">
                                Line total: <span x-text="formatNumber(lineTotal(index))"></span>
                            </span>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="items.length === 0" class="text-center py-8 text-sm text-gray-400">
                No items added yet. Click "Add Item" to start.
            </div>
        </div>

        {{-- Totals & Notes --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Notes</h3>
                <textarea name="notes" rows="4"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                    placeholder="Optional notes...">{{ old('notes', $bill->notes) }}</textarea>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Summary</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd class="font-medium text-gray-900" x-text="formatNumber(subtotal())"></dd>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-gray-100">
                        <dt class="text-gray-900 font-semibold">Total</dt>
                        <dd class="text-lg font-bold text-gray-900" x-text="formatNumber(subtotal())"></dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pt-4">
            @include('components.button', ['label' => 'Cancel', 'type' => 'ghost', 'href' => route('ap.bills.index')])
            @include('components.button', [
                'label' => $isEdit ? 'Update Bill' : 'Create Bill',
                'type' => 'primary',
                'buttonType' => 'submit',
            ])
        </div>
    </form>

    <script>
        function billForm() {
            return {
                items: @json($initialItems ?: []),
                products: @json($productData),

                addItem() {
                    this.items.push({
                        product_id: '',
                        description: '',
                        quantity: 1,
                        unit_price: 0
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                onProductChange(index) {
                    const productId = parseInt(this.items[index].product_id);
                    const product = this.products.find(p => p.id === productId);
                    if (product) {
                        this.items[index].description = product.name;
                        this.items[index].unit_price = product.cost_price;
                    }
                },

                lineTotal(index) {
                    const qty = parseFloat(this.items[index].quantity) || 0;
                    const price = parseFloat(this.items[index].unit_price) || 0;
                    return qty * price;
                },

                subtotal() {
                    return this.items.reduce((sum, item) => {
                        const qty = parseFloat(item.quantity) || 0;
                        const price = parseFloat(item.unit_price) || 0;
                        return sum + (qty * price);
                    }, 0);
                },

                formatNumber(num) {
                    return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            };
        }
    </script>
@endsection
