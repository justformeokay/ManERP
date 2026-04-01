@extends('layouts.app')

@section('title', $supplier->exists ? __('messages.edit_supplier_title') : __('messages.create_supplier_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('suppliers.index') }}" class="hover:text-gray-700">{{ __('messages.suppliers_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $supplier->exists ? __('messages.edit_btn') : __('messages.create_btn') }}</span>
@endsection

@section('page-header')
    <h1 class="text-2xl font-bold text-gray-900">{{ $supplier->exists ? __('messages.edit_supplier_title') : __('messages.create_supplier_title') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ $supplier->exists ? __('messages.edit_supplier_info') : __('messages.add_supplier_info') }}</p>
@endsection

@section('content')
    <form method="POST"
          action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
          class="space-y-6">
        @csrf
        @if($supplier->exists)
            @method('PUT')
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-5">{{ __('messages.supplier_info_section') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.supplier_name_label') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required
                           value="{{ old('name', $supplier->name ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('name') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="{{ __('messages.supplier_name') }}" />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Company --}}
                <div>
                    <label for="company" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.supplier_company_label') }}</label>
                    <input type="text" name="company" id="company"
                           value="{{ old('company', $supplier->company ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('company') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="{{ __('messages.supplier_company_label') }}" />
                    @error('company') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.email_label_supplier') }}</label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email', $supplier->email ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('email') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="supplier@example.com" />
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Phone --}}
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.phone_label_supplier') }}</label>
                    <input type="text" name="phone" id="phone"
                           value="{{ old('phone', $supplier->phone ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('phone') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="+62 812 3456 7890" />
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Tax ID --}}
                <div>
                    <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.tax_id_label') }}</label>
                    <input type="text" name="tax_id" id="tax_id"
                           value="{{ old('tax_id', $supplier->tax_id ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('tax_id') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="00.000.000.0-000.000" />
                    @error('tax_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.status_label') }}</label>
                    <select name="status" id="status"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('status') border-red-300 ring-1 ring-red-200 @enderror">
                        <option value="active" @selected(old('status', $supplier->status ?? 'active') === 'active')>{{ __('messages.supplier_status_active') }}</option>
                        <option value="inactive" @selected(old('status', $supplier->status ?? 'active') === 'inactive')>{{ __('messages.supplier_status_inactive') }}</option>
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-5">{{ __('messages.supplier_address_section') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Address --}}
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.supplier_address_label') }}</label>
                    <textarea name="address" id="address" rows="3"
                              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('address') border-red-300 ring-1 ring-red-200 @enderror"
                              placeholder="{{ __('messages.full_street_address') }}">{{ old('address', $supplier->address ?? '') }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- City --}}
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.supplier_city_label') }}</label>
                    <input type="text" name="city" id="city"
                           value="{{ old('city', $supplier->city ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('city') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="{{ __('messages.supplier_city_label') }}" />
                    @error('city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Country --}}
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.supplier_country_label') }}</label>
                    <input type="text" name="country" id="country"
                           value="{{ old('country', $supplier->country ?? 'Indonesia') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('country') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="{{ __('messages.supplier_country_label') }}" />
                    @error('country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-5">{{ __('messages.notes_section') }}</h3>
            <textarea name="notes" id="notes" rows="3"
                      class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('notes') border-red-300 ring-1 ring-red-200 @enderror"
                      placeholder="{{ __('messages.supplier_notes_label') }}">{{ old('notes', $supplier->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => __('messages.cancel_btn'), 'type' => 'secondary', 'href' => route('suppliers.index')])
            @include('components.button', ['label' => $supplier->exists ? __('messages.update_supplier_btn') : __('messages.create_supplier_btn'), 'type' => 'primary', 'buttonType' => 'submit'])
        </div>
    </form>
@endsection
