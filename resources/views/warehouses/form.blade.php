@extends('layouts.app')

@php
    $isEdit = $warehouse->exists;
    $pageTitle = $isEdit ? __('messages.edit_warehouse') : __('messages.add_warehouse_title');
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('warehouses.index') }}" class="hover:text-gray-700">{{ __('messages.warehouses_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $isEdit ? __('messages.update_warehouse_details') : __('messages.create_warehouse_details') }}
        </p>
    </div>
@endsection

@section('content')
    <form method="POST"
          action="{{ $isEdit ? route('warehouses.update', $warehouse) : route('warehouses.store') }}"
          class="space-y-6 max-w-2xl">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.warehouse_info') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Name --}}
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.warehouse_name_label') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $warehouse->name) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('name') border-red-300 @enderror"
                        placeholder="e.g. Main Warehouse">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Code --}}
                @if($isEdit)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.warehouse_code_label') }}</label>
                    <input type="text" value="{{ $warehouse->code }}" disabled
                        class="w-full rounded-xl border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500">
                </div>
                @endif

                {{-- Address --}}
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.warehouse_address_label') }}</label>
                    <textarea id="address" name="address" rows="3"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                        placeholder="{{ __('messages.warehouse_address_placeholder') }}">{{ old('address', $warehouse->address) }}</textarea>
                </div>

                {{-- Default --}}
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" value="1"
                            {{ old('is_default', $warehouse->is_default) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">{{ __('messages.set_as_default_warehouse') }}</span>
                    </label>
                </div>

                {{-- Active --}}
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">{{ __('messages.warehouse_active_label') }}</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => __('messages.cancel'), 'type' => 'secondary', 'href' => route('warehouses.index')])
            @include('components.button', [
                'label' => $isEdit ? __('messages.update_warehouse_btn') : __('messages.create_warehouse_btn'),
                'type' => 'primary',
                'buttonType' => 'submit',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            ])
        </div>
    </form>
@endsection
