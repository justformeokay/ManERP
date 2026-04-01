@extends('layouts.app')

@section('title', __('messages.new_stock_transfer'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('inventory.transfers.index') }}" class="hover:text-gray-700">{{ __('messages.stock_transfers_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.new_transfer') }}</span>
@endsection

@section('page-header')
    <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.new_stock_transfer') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('messages.move_inventory_subtitle') }}</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.transfers.store') }}" class="space-y-6" x-data="transferForm()">
        @csrf

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-5">{{ __('messages.transfer_details') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Product --}}
                <div class="md:col-span-2">
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.product_label') }} <span class="text-red-500">*</span></label>
                    <select name="product_id" id="product_id" required x-model="productId" @change="updateStock()"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('product_id') border-red-300 ring-1 ring-red-200 @enderror">
                        <option value="">{{ __('messages.select_product_placeholder') }}</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)
                                data-stocks="{{ $product->inventoryStocks->keyBy('warehouse_id')->map->quantity->toJson() }}">
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- From Warehouse --}}
                <div>
                    <label for="from_warehouse_id" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.from_warehouse_label') }} <span class="text-red-500">*</span></label>
                    <select name="from_warehouse_id" id="from_warehouse_id" required x-model="fromWarehouseId" @change="updateStock()"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('from_warehouse_id') border-red-300 ring-1 ring-red-200 @enderror">
                        <option value="">{{ __('messages.select_source_placeholder') }}</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('from_warehouse_id') == $wh->id)>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs" :class="availableStock !== null ? (availableStock > 0 ? 'text-green-600' : 'text-red-600') : 'text-gray-400'">
                        <span x-show="availableStock !== null">{{ __('messages.available_stock_label') }}: <span x-text="Number(availableStock).toLocaleString()"></span></span>
                        <span x-show="availableStock === null">{{ __('messages.select_to_check_stock') }}</span>
                    </p>
                    @error('from_warehouse_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- To Warehouse --}}
                <div>
                    <label for="to_warehouse_id" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.to_warehouse_label') }} <span class="text-red-500">*</span></label>
                    <select name="to_warehouse_id" id="to_warehouse_id" required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('to_warehouse_id') border-red-300 ring-1 ring-red-200 @enderror">
                        <option value="">{{ __('messages.select_destination_placeholder') }}</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('to_warehouse_id') == $wh->id)>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                    @error('to_warehouse_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Quantity --}}
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.transfer_quantity_label') }} <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" id="quantity" required min="0.01" step="0.01"
                           value="{{ old('quantity') }}"
                           :max="availableStock ?? ''"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('quantity') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="0" />
                    @error('quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.transfer_notes_label') }}</label>
                    <input type="text" name="notes" id="notes"
                           value="{{ old('notes') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                           placeholder="{{ __('messages.transfer_notes_placeholder') }}" />
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => __('messages.cancel'), 'type' => 'secondary', 'href' => route('inventory.transfers.index')])
            <button type="submit" name="execute" value="0"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
                {{ __('messages.save_as_pending_btn') }}
            </button>
            <button type="submit" name="execute" value="1"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition-colors">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                {{ __('messages.transfer_now_btn') }}
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function transferForm() {
        return {
            productId: '{{ old('product_id', '') }}',
            fromWarehouseId: '{{ old('from_warehouse_id', '') }}',
            availableStock: null,
            updateStock() {
                if (!this.productId || !this.fromWarehouseId) {
                    this.availableStock = null;
                    return;
                }
                const option = document.querySelector(`#product_id option[value="${this.productId}"]`);
                if (!option) return;
                const stocks = JSON.parse(option.dataset.stocks || '{}');
                this.availableStock = stocks[this.fromWarehouseId] ?? 0;
            }
        };
    }
</script>
@endpush
