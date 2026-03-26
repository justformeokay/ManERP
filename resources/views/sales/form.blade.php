@extends('layouts.app')

@php
    $isEdit = $order->exists;
    $pageTitle = $isEdit ? 'Edit ' . $order->number : 'New Sales Order';
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('sales.index') }}" class="hover:text-gray-700">Sales Orders</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $isEdit ? 'Update order details and items.' : 'Create a new sales order for a client.' }}
        </p>
    </div>
@endsection

@section('content')
    @php
        $initialItems = [];
        if ($isEdit && $order->items) {
            foreach ($order->items as $item) {
                $initialItems[] = [
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount'   => $item->discount,
                ];
            }
        }

        $productData = $products->map(function ($p) {
            return [
                'id'         => $p->id,
                'name'       => $p->name,
                'sku'        => $p->sku,
                'sell_price' => (float) $p->sell_price,
                'unit'       => $p->unit,
                'stock'      => (float) $p->inventoryStocks->sum('quantity'),
            ];
        })->values()->toArray();
    @endphp

    <form method="POST"
          action="{{ $isEdit ? route('sales.update', $order) : route('sales.store') }}"
          class="space-y-6"
          x-data="salesForm()">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Order Details --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Order Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                    <select name="client_id" id="client_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">Select client...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('client_id', $order->client_id) == $client->id)>
                                {{ $client->name }} {{ $client->company ? '— ' . $client->company : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">Warehouse <span class="text-red-500">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">Select warehouse...</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id', $order->warehouse_id) == $wh->id)>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">Project</label>
                    <select name="project_id" id="project_id"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">— None —</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected(old('project_id', $order->project_id) == $project->id)>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="order_date" class="block text-sm font-medium text-gray-700 mb-1">Order Date <span class="text-red-500">*</span></label>
                    <input type="date" name="order_date" id="order_date" required
                        value="{{ old('order_date', $order->order_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('order_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="delivery_date" class="block text-sm font-medium text-gray-700 mb-1">Delivery Date</label>
                    <input type="date" name="delivery_date" id="delivery_date"
                        value="{{ old('delivery_date', $order->delivery_date?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('delivery_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Order Items --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Order Items</h3>
                <button type="button" @click="addItem()"
                    class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Add Item
                </button>
            </div>

            @error('items') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('stock') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4">
                        <div class="flex flex-col sm:flex-row gap-3 items-start">
                            <div class="flex-1 min-w-0">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Product *</label>
                                <select :name="`items[${index}][product_id]`" x-model="item.product_id" required
                                    @change="onProductChange(index)"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                                    <option value="">Select product...</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-28">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Qty *</label>
                                <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity" step="0.01" min="0.01" required
                                    @input="calcItemTotal(index)"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                                    placeholder="0">
                            </div>
                            <div class="w-32">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Unit Price *</label>
                                <input type="number" :name="`items[${index}][unit_price]`" x-model="item.unit_price" step="0.01" min="0" required
                                    @input="calcItemTotal(index)"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                                    placeholder="0.00">
                            </div>
                            <div class="w-28">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Discount</label>
                                <input type="number" :name="`items[${index}][discount]`" x-model="item.discount" step="0.01" min="0"
                                    @input="calcItemTotal(index)"
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
                        {{-- Stock info & line total --}}
                        <div class="flex items-center justify-between mt-2 px-1">
                            <template x-if="item.product_id">
                                <span class="text-xs" x-bind:class="getStock(item.product_id) < item.quantity ? 'text-red-500 font-medium' : 'text-gray-400'">
                                    Stock: <span x-text="getStock(item.product_id)"></span>
                                    <span x-show="getStock(item.product_id) < item.quantity"> — Insufficient!</span>
                                </span>
                            </template>
                            <template x-if="!item.product_id">
                                <span></span>
                            </template>
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
                    placeholder="Optional order notes...">{{ old('notes', $order->notes) }}</textarea>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Summary</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd class="font-medium text-gray-900" x-text="formatNumber(subtotal())"></dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-gray-500">Tax</dt>
                        <dd>
                            <input type="number" name="tax_amount" x-model="taxAmount" step="0.01" min="0"
                                class="w-28 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-right text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                placeholder="0.00">
                        </dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-gray-500">Discount</dt>
                        <dd>
                            <input type="number" name="discount" x-model="orderDiscount" step="0.01" min="0"
                                class="w-28 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-right text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                placeholder="0.00">
                        </dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3">
                        <dt class="font-semibold text-gray-900">Grand Total</dt>
                        <dd class="text-lg font-bold text-gray-900" x-text="formatNumber(grandTotal())"></dd>
                    </div>
                </dl>

                {{-- Stock warning --}}
                <div x-show="hasStockWarning()" class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700 ring-1 ring-red-100">
                    <strong>Warning:</strong> Some items exceed available stock. Order can be saved as draft but cannot be confirmed.
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => 'Cancel', 'type' => 'ghost', 'href' => route('sales.index')])
            @include('components.button', [
                'label' => $isEdit ? 'Update Order' : 'Create Order',
                'type' => 'primary',
                'buttonType' => 'submit',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            ])
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function salesForm() {
        return {
            items: @json(old('items', $initialItems)),
            taxAmount: @json(old('tax_amount', $order->tax_amount ?? 0)),
            orderDiscount: @json(old('discount', $order->discount ?? 0)),
            productData: @json($productData),

            addItem() {
                this.items.push({ product_id: '', quantity: 1, unit_price: 0, discount: 0 });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            onProductChange(index) {
                const product = this.productData.find(p => p.id == this.items[index].product_id);
                if (product) {
                    this.items[index].unit_price = product.sell_price;
                }
            },
            getStock(productId) {
                const product = this.productData.find(p => p.id == productId);
                return product ? product.stock : 0;
            },
            calcItemTotal(index) {
                // triggers reactivity
            },
            lineTotal(index) {
                const item = this.items[index];
                return (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0) - (parseFloat(item.discount) || 0);
            },
            subtotal() {
                return this.items.reduce((sum, item, i) => sum + this.lineTotal(i), 0);
            },
            grandTotal() {
                return this.subtotal() + (parseFloat(this.taxAmount) || 0) - (parseFloat(this.orderDiscount) || 0);
            },
            hasStockWarning() {
                return this.items.some(item => item.product_id && this.getStock(item.product_id) < (parseFloat(item.quantity) || 0));
            },
            formatNumber(val) {
                return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    }
</script>
@endpush
