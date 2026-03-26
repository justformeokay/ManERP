@extends('layouts.app')

@section('title', 'Record Stock Movement')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('inventory.movements.index') }}" class="hover:text-gray-700">Stock Movements</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">New Movement</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Record Stock Movement</h1>
        <p class="mt-1 text-sm text-gray-500">Record a stock in, out, or adjustment for a product.</p>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.movements.store') }}" class="space-y-6" x-data="{ type: '{{ old('type', 'in') }}' }">
        @csrf

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Movement Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Product --}}
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Product <span class="text-red-500">*</span></label>
                    <select id="product_id" name="product_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('product_id') border-red-300 @enderror">
                        <option value="">Select product...</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Warehouse --}}
                <div>
                    <label for="warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">Warehouse <span class="text-red-500">*</span></label>
                    <select id="warehouse_id" name="warehouse_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('warehouse_id') border-red-300 @enderror">
                        <option value="">Select warehouse...</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>
                                {{ $wh->name }} ({{ $wh->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Type --}}
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Movement Type <span class="text-red-500">*</span></label>
                    <select id="type" name="type" required x-model="type"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        <option value="in">Stock In</option>
                        <option value="out">Stock Out</option>
                        <option value="adjustment">Adjustment</option>
                    </select>
                </div>

                {{-- Quantity --}}
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">
                        <span x-text="type === 'adjustment' ? 'New Quantity' : 'Quantity'">Quantity</span>
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="quantity" name="quantity" step="0.01" min="0" required
                        value="{{ old('quantity') }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('quantity') border-red-300 @enderror"
                        placeholder="0">
                    @error('quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Reference --}}
                <div>
                    <label for="reference_type" class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                    <input type="text" id="reference_type" name="reference_type"
                        value="{{ old('reference_type') }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="e.g. PO-001, SO-002, Manual">
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <input type="text" id="notes" name="notes"
                        value="{{ old('notes') }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="Optional notes...">
                </div>
            </div>

            {{-- Type helper --}}
            <div class="mt-4 rounded-xl p-3 text-sm" x-bind:class="{
                'bg-green-50 text-green-700': type === 'in',
                'bg-red-50 text-red-700': type === 'out',
                'bg-amber-50 text-amber-700': type === 'adjustment'
            }">
                <p x-show="type === 'in'"><strong>Stock In</strong> — Increases inventory quantity. Used for purchasing, returns, or production output.</p>
                <p x-show="type === 'out'"><strong>Stock Out</strong> — Decreases inventory quantity. Used for sales, consumption, or waste.</p>
                <p x-show="type === 'adjustment'"><strong>Adjustment</strong> — Sets the stock to the exact quantity entered. Used for physical inventory counts.</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => 'Cancel', 'type' => 'secondary', 'href' => route('inventory.movements.index')])
            @include('components.button', [
                'label' => 'Record Movement',
                'type' => 'primary',
                'buttonType' => 'submit',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            ])
        </div>
    </form>
@endsection
