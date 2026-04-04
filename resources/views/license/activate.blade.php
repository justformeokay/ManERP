@extends('layouts.app')

@section('title', __('messages.license_activate'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('license.index') }}" class="hover:text-gray-700">{{ __('messages.license_management') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.license_activate') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.license_activate') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.license_activate_subtitle') }}</p>
    </div>
@endsection

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('license.processActivation') }}" class="space-y-6">
        @csrf

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.license_activation_details') }}</h3>

            <div class="space-y-4">
                {{-- Company Name --}}
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.company_name') }}</label>
                    <input type="text" id="company_name" name="company_name"
                        value="{{ old('company_name') }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('company_name') border-red-300 @enderror"
                        placeholder="{{ __('messages.license_company_placeholder') }}" required>
                    @error('company_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Domain --}}
                <div>
                    <label for="domain" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.license_domain') }}</label>
                    <input type="text" id="domain" name="domain"
                        value="{{ old('domain') }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('domain') border-red-300 @enderror"
                        placeholder="erp.yourcompany.com" required>
                    @error('domain') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Serial Number --}}
                <div>
                    <label for="serial_number" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.license_serial_number') }}</label>
                    <input type="text" id="serial_number" name="serial_number"
                        value="{{ old('serial_number') }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 font-mono tracking-wide focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('serial_number') border-red-300 @enderror"
                        placeholder="e.g. a1b2c3d4e5f6..." maxlength="64" required>
                    @error('serial_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-400">{{ __('messages.license_serial_hint') }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ __('messages.license_activate') }}
            </button>
            <a href="{{ route('license.index') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">
                {{ __('messages.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
