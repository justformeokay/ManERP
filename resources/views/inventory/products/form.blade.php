@extends('layouts.app')

@php
    $isEdit = $product->exists;
    $pageTitle = $isEdit ? __('messages.edit_product') : __('messages.new_product');
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('inventory.products.index') }}" class="hover:text-gray-700">{{ __('messages.products') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $isEdit ? __('messages.update_product_details') : __('messages.add_new_product_to_catalog') }}
        </p>
    </div>
@endsection

@section('content')
    <form method="POST"
          action="{{ $isEdit ? route('inventory.products.update', $product) : route('inventory.products.store') }}"
          class="space-y-6">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Product Details --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.product_details') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.name') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('name') border-red-300 @enderror"
                        placeholder="e.g. Steel Beam 200mm">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.sku') }}</label>
                    <input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('sku') border-red-300 @enderror"
                        placeholder="{{ __('messages.auto_generated_if_empty') }}">
                    @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.categories') }}</label>
                    <select id="category_id" name="category_id"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        <option value="">{{ __('messages.no_category') }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.type') }} <span class="text-red-500">*</span></label>
                    <select id="type" name="type" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        @foreach(\App\Models\Product::typeOptions() as $type)
                            <option value="{{ $type }}" @selected(old('type', $product->type) === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.unit') }} <span class="text-red-500">*</span></label>
                    <select id="unit" name="unit" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        @foreach(\App\Models\Product::unitOptions() as $unit)
                            <option value="{{ $unit }}" @selected(old('unit', $product->unit) === $unit)>{{ strtoupper($unit) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                    <textarea id="description" name="description" rows="3"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="{{ __('messages.product_description_placeholder') }}">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Pricing & Stock --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.pricing_stock') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="cost_price" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.cost_price') }}</label>
                    <input type="text" id="cost_price" name="cost_price" x-currency
                        value="{{ old('cost_price', $product->cost_price) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="0.00">
                </div>

                <div>
                    <label for="sell_price" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.sell_price') }}</label>
                    <input type="text" id="sell_price" name="sell_price" x-currency
                        value="{{ old('sell_price', $product->sell_price) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="0.00">
                </div>

                <div>
                    <label for="min_stock" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.min_stock_alert') }}</label>
                    <input type="number" id="min_stock" name="min_stock" min="0"
                        value="{{ old('min_stock', $product->min_stock) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="0">
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    @checked(old('is_active', $product->is_active))>
                <label for="is_active" class="text-sm font-medium text-gray-700">{{ __('messages.active_product') }}</label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => __('messages.cancel'), 'type' => 'secondary', 'href' => route('inventory.products.index')])
            @include('components.button', [
                'label' => $isEdit ? __('messages.update_product') : __('messages.create_product'),
                'type' => 'primary',
                'buttonType' => 'submit',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            ])
        </div>
    </form>
@endsection
