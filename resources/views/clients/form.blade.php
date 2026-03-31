@extends('layouts.app')

@php
    $isEdit = $client->exists;
    $pageTitle = $isEdit ? __('messages.edit_client') : __('messages.add_client');
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('clients.index') }}" class="hover:text-gray-700">{{ __('messages.clients_heading') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $isEdit ? __('messages.update_client_information') : __('messages.fill_in_details_to_create_new_client') }}
        </p>
    </div>
@endsection

@section('content')
    <form
        method="POST"
        action="{{ $isEdit ? route('clients.update', $client) : route('clients.store') }}"
        class="space-y-6"
    >
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Basic Info --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.basic_information') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.name') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $client->name) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('name') border-red-300 @enderror"
                        placeholder="{{ __('messages.full_name') }}">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Company --}}
                <div>
                    <label for="company" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.company') }}</label>
                    <input type="text" id="company" name="company" value="{{ old('company', $client->company) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="{{ __('messages.company_name') }}">
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.email') }}</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $client->email) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('email') border-red-300 @enderror"
                        placeholder="email@example.com">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Phone --}}
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.phone') }}</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $client->phone) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="+62 812 3456 7890">
                </div>

                {{-- Tax ID --}}
                <div>
                    <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.tax_id') }}</label>
                    <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id', $client->tax_id) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="{{ __('messages.tax_identification_number') }}">
                </div>

                {{-- Type --}}
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.type') }} <span class="text-red-500">*</span></label>
                    <select id="type" name="type"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        <option value="customer" @selected(old('type', $client->type) === 'customer')>{{ __('messages.customer') }}</option>
                        <option value="lead" @selected(old('type', $client->type) === 'lead')>{{ __('messages.lead') }}</option>
                        <option value="prospect" @selected(old('type', $client->type) === 'prospect')>{{ __('messages.prospect') }}</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Address --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.address_label') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.address_label') }}</label>
                    <textarea id="address" name="address" rows="3"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="{{ __('messages.street_address') }}">{{ old('address', $client->address) }}</textarea>
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.city') }}</label>
                    <input type="text" id="city" name="city" value="{{ old('city', $client->city) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="{{ __('messages.city') }}">
                </div>
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.country') }}</label>
                    <input type="text" id="country" name="country" value="{{ old('country', $client->country) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="{{ __('messages.country') }}">
                </div>
            </div>
        </div>

        {{-- Status & Notes --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.status_notes') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.status') }} <span class="text-red-500">*</span></label>
                    <select id="status" name="status"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        <option value="active" @selected(old('status', $client->status) === 'active')>{{ __('messages.active') }}</option>
                        <option value="inactive" @selected(old('status', $client->status) === 'inactive')>{{ __('messages.inactive') }}</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.notes') }}</label>
                    <textarea id="notes" name="notes" rows="3"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="{{ __('messages.internal_notes') }}">{{ old('notes', $client->notes) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => __('messages.cancel'), 'type' => 'secondary', 'href' => route('clients.index')])
            @include('components.button', [
                'label' => $isEdit ? __('messages.update_client') : __('messages.create_client'),
                'type' => 'primary',
                'buttonType' => 'submit',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            ])
        </div>
    </form>
@endsection
