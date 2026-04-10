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
        x-data="{ clientType: '{{ old('type', $client->type ?? 'customer') }}' }"
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
                <div x-data="{ showTooltip: false }">
                    <div class="flex items-center gap-1.5 mb-1">
                        <label for="type" class="block text-sm font-medium text-gray-700">{{ __('messages.type') }} <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <button type="button" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false" @focus="showTooltip = true" @blur="showTooltip = false"
                                class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-gray-200 text-gray-500 hover:bg-gray-300 transition text-xs font-bold leading-none" aria-label="Info">
                                i
                            </button>
                            <div x-show="showTooltip" x-transition:enter="transition ease-out duration-150" x-transition:leave="transition ease-in duration-100"
                                 class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-72 rounded-xl bg-gray-900 p-3 text-xs text-white shadow-lg" x-cloak>
                                <div class="space-y-2">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-0.5 inline-block h-2 w-2 rounded-full bg-amber-400 shrink-0"></span>
                                        <div><span class="font-semibold text-amber-300">Lead:</span> {{ __('messages.tooltip_lead') }}</div>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="mt-0.5 inline-block h-2 w-2 rounded-full bg-purple-400 shrink-0"></span>
                                        <div><span class="font-semibold text-purple-300">{{ __('messages.prospect') }}:</span> {{ __('messages.tooltip_prospect') }}</div>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="mt-0.5 inline-block h-2 w-2 rounded-full bg-primary-400 shrink-0"></span>
                                        <div><span class="font-semibold text-primary-300">{{ __('messages.customer') }}:</span> {{ __('messages.tooltip_customer') }}</div>
                                    </div>
                                </div>
                                <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-px border-4 border-transparent border-t-gray-900"></div>
                            </div>
                        </div>
                    </div>
                    <select id="type" name="type" x-model="clientType"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        <option value="lead" @selected(old('type', $client->type) === 'lead') class="text-amber-700 bg-amber-50">🟡 {{ __('messages.lead') }}</option>
                        <option value="prospect" @selected(old('type', $client->type) === 'prospect') class="text-purple-700 bg-purple-50">🟣 {{ __('messages.prospect') }}</option>
                        <option value="customer" @selected(old('type', $client->type) === 'customer') class="text-primary-700 bg-primary-50">🔵 {{ __('messages.customer') }}</option>
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

        {{-- Credit & Risk Management (hidden for Lead type) --}}
        <div x-show="clientType !== 'lead'" x-transition:enter="transition ease-out duration-200" x-transition:leave="transition ease-in duration-150" x-cloak
             class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.credit_risk_management') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="credit_limit" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.credit_limit') }}</label>
                    <input type="text" id="credit_limit" name="credit_limit" x-currency
                        value="{{ old('credit_limit', $client->credit_limit ?? 0) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="0 = {{ __('messages.unlimited') }}">
                    <p class="mt-1 text-xs text-gray-400">{{ __('messages.credit_limit_hint') }}</p>
                    @error('credit_limit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="payment_terms" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.payment_terms') }}</label>
                    <div class="relative">
                        <input type="number" id="payment_terms" name="payment_terms" min="0" max="365"
                            value="{{ old('payment_terms', $client->payment_terms ?? 30) }}"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        <span class="absolute right-4 top-2.5 text-sm text-gray-400">{{ __('messages.days') }}</span>
                    </div>
                    @error('payment_terms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="is_credit_blocked" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.credit_block_status') }}</label>
                    <div class="flex items-center gap-3 mt-2">
                        <input type="hidden" name="is_credit_blocked" value="0">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_credit_blocked" value="1"
                                class="sr-only peer"
                                @checked(old('is_credit_blocked', $client->is_credit_blocked))>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-500"></div>
                            <span class="ml-3 text-sm text-gray-600">{{ __('messages.block_credit') }}</span>
                        </label>
                    </div>
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
