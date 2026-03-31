@extends('layouts.app')

@section('title', __('messages.system_settings'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.settings') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.system_settings') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.settings_description') }}</p>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf

        {{-- Company Information --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.company_information') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.company_name') }}</label>
                    <input type="text" id="company_name" name="company_name"
                        value="{{ old('company_name', $settings['company_name']) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('company_name') border-red-300 @enderror"
                        placeholder="e.g. PT Manufaktur Indonesia">
                    @error('company_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company_email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.email') }}</label>
                    <input type="email" id="company_email" name="company_email"
                        value="{{ old('company_email', $settings['company_email']) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('company_email') border-red-300 @enderror"
                        placeholder="info@company.com">
                    @error('company_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company_phone" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.phone') }}</label>
                    <input type="text" id="company_phone" name="company_phone"
                        value="{{ old('company_phone', $settings['company_phone']) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('company_phone') border-red-300 @enderror"
                        placeholder="+62 812 3456 7890">
                    @error('company_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="company_address" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.address') }}</label>
                    <textarea id="company_address" name="company_address" rows="3"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('company_address') border-red-300 @enderror"
                        placeholder="Full company address...">{{ old('company_address', $settings['company_address']) }}</textarea>
                    @error('company_address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- System Preferences --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.system_preferences') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="default_currency" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.default_currency') }} <span class="text-red-500">*</span></label>
                    <select id="default_currency" name="default_currency" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        @foreach($currencies as $code => $label)
                            <option value="{{ $code }}" @selected(old('default_currency', $settings['default_currency']) === $code)>{{ $code }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.timezone') }} <span class="text-red-500">*</span></label>
                    <select id="timezone" name="timezone" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        @foreach($timezones as $tz)
                            <option value="{{ $tz }}" @selected(old('timezone', $settings['timezone']) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', [
                'label' => __('messages.save_settings'),
                'type' => 'primary',
                'buttonType' => 'submit',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            ])
        </div>
    </form>
@endsection
