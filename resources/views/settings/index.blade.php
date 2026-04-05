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
<div class="flex flex-col lg:flex-row gap-6">

    {{-- ══════════════════════════════════════════════════════════════
         SIDEBAR NAVIGATION
    ══════════════════════════════════════════════════════════════ --}}
    <nav class="lg:w-56 shrink-0">
        <div class="rounded-2xl bg-white p-2 shadow-sm ring-1 ring-gray-100 lg:sticky lg:top-24 space-y-1">
            @php
                $tabMeta = [
                    'company'      => ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => __('messages.stab_company')],
                    'financial'    => ['icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'label' => __('messages.stab_financial')],
                    'payroll'      => ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'label' => __('messages.stab_payroll')],
                    'security'     => ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'label' => __('messages.stab_security')],
                    'localization' => ['icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => __('messages.stab_localization')],
                ];
            @endphp
            @foreach($tabMeta as $key => $meta)
                <a href="{{ route('settings.index', ['tab' => $key]) }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                       {{ $currentTab === $key ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="h-4.5 w-4.5 shrink-0 {{ $currentTab === $key ? 'text-indigo-500' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}"/></svg>
                    {{ $meta['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    {{-- ══════════════════════════════════════════════════════════════
         MAIN CONTENT
    ══════════════════════════════════════════════════════════════ --}}
    <div class="flex-1 min-w-0 space-y-6">

        {{-- Flash --}}
        @if(session('success'))
            <div class="flex items-center gap-3 rounded-xl bg-green-50 p-4 ring-1 ring-green-200" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
                <svg class="h-5 w-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium text-green-800">{{ session('success') }}</span>
            </div>
        @endif

        {{-- ── TAB: Company Profile ─────────────────────────────── --}}
        @if($currentTab === 'company')
        <form method="POST" action="{{ route('settings.update.company') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('messages.stab_company') }}</h3>
                <p class="text-xs text-gray-500 mb-5">{{ __('messages.stab_company_desc') }}</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Logo --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.company_logo') }}</label>
                        <div class="flex items-center gap-4">
                            @if($company->logo)
                                <img src="{{ $company->logo_url }}" alt="Logo" class="h-16 w-16 rounded-xl object-contain ring-1 ring-gray-200 bg-white p-1">
                            @else
                                <div class="h-16 w-16 rounded-xl bg-gray-100 flex items-center justify-center">
                                    <svg class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                            <input type="file" name="company_logo" accept="image/png,image/jpeg,image/svg+xml"
                                class="text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                        <p class="mt-1 text-[10px] text-gray-400">PNG, JPG, SVG. {{ __('messages.stab_max_1mb') }}</p>
                        @error('company_logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        @include('settings._field', ['name' => 'company_name', 'label' => __('messages.company_name'), 'value' => $company->name, 'tooltip' => __('messages.tip_company_name')])
                    </div>
                    <div>
                        @include('settings._field', ['name' => 'company_email', 'label' => __('messages.email'), 'type' => 'email', 'value' => $company->email, 'tooltip' => __('messages.tip_company_email')])
                    </div>
                    <div>
                        @include('settings._field', ['name' => 'company_phone', 'label' => __('messages.phone'), 'value' => $company->phone, 'tooltip' => __('messages.tip_company_phone')])
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.address') }}
                            <span class="relative group">
                                <svg class="h-3.5 w-3.5 text-gray-400 cursor-help" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-48 rounded-lg bg-gray-900 px-3 py-2 text-[10px] text-white shadow-lg z-50">{{ __('messages.tip_company_address') }}</span>
                            </span>
                        </label>
                        <textarea name="company_address" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">{{ old('company_address', $company->address) }}</textarea>
                    </div>
                </div>
            </div>

            @include('settings._save_button')
        </form>
        @endif

        {{-- ── TAB: Accounting & Financial ──────────────────────── --}}
        @if($currentTab === 'financial')
        <form method="POST" action="{{ route('settings.update.financial') }}" class="space-y-6">
            @csrf

            {{-- Fiscal Year --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('messages.stab_fiscal_year') }}</h3>
                <p class="text-xs text-gray-500 mb-5">{{ __('messages.stab_fiscal_year_desc') }}</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.stab_fiscal_start') }}
                            @include('settings._tooltip', ['text' => __('messages.tip_fiscal_start')])
                        </label>
                        <select name="fiscal_year_start_month" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected(old('fiscal_year_start_month', $settings['fiscal_year_start_month']) == $m)>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.stab_fiscal_closing') }}
                            @include('settings._tooltip', ['text' => __('messages.tip_fiscal_closing')])
                        </label>
                        <select name="fiscal_closing_month" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected(old('fiscal_closing_month', $settings['fiscal_closing_month']) == $m)>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>

            {{-- System Account Lock --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">{{ __('messages.stab_account_lock') }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.stab_account_lock_desc') }}</p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="hidden" name="system_account_lock" value="0">
                        <input type="checkbox" name="system_account_lock" value="1" class="peer sr-only" @checked(old('system_account_lock', $settings['system_account_lock']))>
                        <div class="h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-indigo-600 peer-focus:ring-2 peer-focus:ring-indigo-300 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all peer-checked:after:translate-x-5"></div>
                    </label>
                </div>
            </div>

            {{-- Opening Balance --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('messages.stab_opening_balance') }}</h3>
                <p class="text-xs text-gray-500 mb-5">{{ __('messages.stab_opening_balance_desc') }}</p>
                @include('settings._field', ['name' => 'opening_balance_date', 'label' => __('messages.stab_opening_date'), 'type' => 'date', 'value' => $settings['opening_balance_date'], 'tooltip' => __('messages.tip_opening_date')])
            </div>

            {{-- Tax & Terms --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('messages.business_defaults') }}</h3>
                <p class="text-xs text-gray-500 mb-5">{{ __('messages.business_defaults_desc') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        @include('settings._field', ['name' => 'default_tax_rate', 'label' => __('messages.default_tax_rate'), 'type' => 'number', 'value' => $settings['default_tax_rate'], 'suffix' => '%', 'step' => '0.01', 'tooltip' => __('messages.tip_tax_rate')])
                    </div>
                    <div>
                        @include('settings._field', ['name' => 'default_payment_terms', 'label' => __('messages.default_payment_terms'), 'type' => 'number', 'value' => $settings['default_payment_terms'], 'suffix' => __('messages.days'), 'tooltip' => __('messages.tip_payment_terms')])
                    </div>
                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">{{ __('messages.default_currency') }}</label>
                        <select name="default_currency" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            @foreach($currencies as $code => $label)
                                <option value="{{ $code }}" @selected(old('default_currency', $settings['default_currency']) === $code)>{{ $code }} — {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">{{ __('messages.timezone') }}</label>
                        <select name="timezone" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            @foreach($timezones as $tz)
                                <option value="{{ $tz }}" @selected(old('timezone', $settings['timezone']) === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @include('settings._save_button')
        </form>
        @endif

        {{-- ── TAB: HR & Payroll ────────────────────────────────── --}}
        @if($currentTab === 'payroll')
        <form method="POST" action="{{ route('settings.update.payroll') }}" class="space-y-6">
            @csrf

            {{-- BPJS Ketenagakerjaan --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-1">BPJS Ketenagakerjaan</h3>
                <p class="text-xs text-gray-500 mb-5">{{ __('messages.stab_bpjs_tk_desc') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @include('settings._field', ['name' => 'bpjs_jht_company', 'label' => __('messages.stab_jht_company'), 'type' => 'number', 'value' => $settings['bpjs_jht_company'], 'suffix' => '%', 'step' => '0.01', 'tooltip' => __('messages.tip_jht_company')])
                    @include('settings._field', ['name' => 'bpjs_jht_employee', 'label' => __('messages.stab_jht_employee'), 'type' => 'number', 'value' => $settings['bpjs_jht_employee'], 'suffix' => '%', 'step' => '0.01', 'tooltip' => __('messages.tip_jht_employee')])
                    @include('settings._field', ['name' => 'bpjs_jkk_rate', 'label' => __('messages.stab_jkk'), 'type' => 'number', 'value' => $settings['bpjs_jkk_rate'], 'suffix' => '%', 'step' => '0.01', 'tooltip' => __('messages.tip_jkk')])
                    @include('settings._field', ['name' => 'bpjs_jkm_rate', 'label' => __('messages.stab_jkm'), 'type' => 'number', 'value' => $settings['bpjs_jkm_rate'], 'suffix' => '%', 'step' => '0.01', 'tooltip' => __('messages.tip_jkm')])
                    @include('settings._field', ['name' => 'bpjs_jp_company', 'label' => __('messages.stab_jp_company'), 'type' => 'number', 'value' => $settings['bpjs_jp_company'], 'suffix' => '%', 'step' => '0.01', 'tooltip' => __('messages.tip_jp_company')])
                    @include('settings._field', ['name' => 'bpjs_jp_employee', 'label' => __('messages.stab_jp_employee'), 'type' => 'number', 'value' => $settings['bpjs_jp_employee'], 'suffix' => '%', 'step' => '0.01', 'tooltip' => __('messages.tip_jp_employee')])
                </div>
                <div class="mt-4">
                    @include('settings._field', ['name' => 'bpjs_jp_max_salary', 'label' => __('messages.stab_jp_max_salary'), 'type' => 'number', 'value' => $settings['bpjs_jp_max_salary'], 'tooltip' => __('messages.tip_jp_max')])
                </div>
            </div>

            {{-- BPJS Kesehatan --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-1">BPJS Kesehatan</h3>
                <p class="text-xs text-gray-500 mb-5">{{ __('messages.stab_bpjs_kes_desc') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @include('settings._field', ['name' => 'bpjs_kes_company', 'label' => __('messages.stab_kes_company'), 'type' => 'number', 'value' => $settings['bpjs_kes_company'], 'suffix' => '%', 'step' => '0.01', 'tooltip' => __('messages.tip_kes_company')])
                    @include('settings._field', ['name' => 'bpjs_kes_employee', 'label' => __('messages.stab_kes_employee'), 'type' => 'number', 'value' => $settings['bpjs_kes_employee'], 'suffix' => '%', 'step' => '0.01', 'tooltip' => __('messages.tip_kes_employee')])
                    @include('settings._field', ['name' => 'bpjs_kes_min_salary', 'label' => __('messages.stab_kes_min'), 'type' => 'number', 'value' => $settings['bpjs_kes_min_salary'], 'tooltip' => __('messages.tip_kes_min')])
                    @include('settings._field', ['name' => 'bpjs_kes_max_salary', 'label' => __('messages.stab_kes_max'), 'type' => 'number', 'value' => $settings['bpjs_kes_max_salary'], 'tooltip' => __('messages.tip_kes_max')])
                </div>
            </div>

            {{-- Work Hours --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('messages.stab_work_hours') }}</h3>
                <p class="text-xs text-gray-500 mb-5">{{ __('messages.stab_work_hours_desc') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @include('settings._field', ['name' => 'standard_work_hours', 'label' => __('messages.stab_standard_hours'), 'type' => 'number', 'value' => $settings['standard_work_hours'], 'suffix' => __('messages.stab_hours'), 'tooltip' => __('messages.tip_work_hours')])
                    @include('settings._field', ['name' => 'late_tolerance_minutes', 'label' => __('messages.stab_late_tolerance'), 'type' => 'number', 'value' => $settings['late_tolerance_minutes'], 'suffix' => __('messages.stab_minutes'), 'tooltip' => __('messages.tip_late_tolerance')])
                </div>
            </div>

            @include('settings._save_button')
        </form>
        @endif

        {{-- ── TAB: System & Security ───────────────────────────── --}}
        @if($currentTab === 'security')
        <form method="POST" action="{{ route('settings.update.security') }}" class="space-y-6">
            @csrf

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-6">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('messages.stab_security') }}</h3>
                    <p class="text-xs text-gray-500 mb-5">{{ __('messages.stab_security_desc') }}</p>
                </div>

                {{-- Session Lifetime --}}
                <div>
                    @include('settings._field', ['name' => 'session_lifetime_minutes', 'label' => __('messages.stab_session_lifetime'), 'type' => 'number', 'value' => $settings['session_lifetime_minutes'], 'suffix' => __('messages.stab_minutes'), 'tooltip' => __('messages.tip_session_lifetime')])
                </div>

                {{-- API Rate Limit --}}
                <div>
                    @include('settings._field', ['name' => 'api_rate_limit_per_minute', 'label' => __('messages.stab_api_rate_limit'), 'type' => 'number', 'value' => $settings['api_rate_limit_per_minute'], 'suffix' => 'req/min', 'tooltip' => __('messages.tip_api_rate')])
                </div>

                {{-- Mandatory 2FA Toggle --}}
                <div class="flex items-center justify-between rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ __('messages.stab_mandatory_2fa') }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.stab_mandatory_2fa_desc') }}</p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="hidden" name="mandatory_2fa_admin" value="0">
                        <input type="checkbox" name="mandatory_2fa_admin" value="1" class="peer sr-only" @checked(old('mandatory_2fa_admin', $settings['mandatory_2fa_admin']))>
                        <div class="h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-amber-500 peer-focus:ring-2 peer-focus:ring-amber-300 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all peer-checked:after:translate-x-5"></div>
                    </label>
                </div>
            </div>

            {{-- Application Defaults --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('messages.stab_app_defaults') }}</h3>
                <p class="text-xs text-gray-500 mb-5">{{ __('messages.stab_app_defaults_desc') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @include('settings._field', ['name' => 'low_stock_threshold', 'label' => __('messages.low_stock_threshold'), 'type' => 'number', 'value' => $settings['low_stock_threshold'], 'tooltip' => __('messages.tip_low_stock')])
                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">{{ __('messages.items_per_page') }}</label>
                        <select name="items_per_page" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            @foreach([10, 15, 25, 50, 100] as $pp)
                                <option value="{{ $pp }}" @selected(old('items_per_page', $settings['items_per_page']) == $pp)>{{ $pp }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @include('settings._save_button')
        </form>
        @endif

        {{-- ── TAB: Localization ────────────────────────────────── --}}
        @if($currentTab === 'localization')
        <form method="POST" action="{{ route('settings.update.localization') }}" class="space-y-6">
            @csrf

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('messages.stab_localization') }}</h3>
                <p class="text-xs text-gray-500 mb-5">{{ __('messages.stab_localization_desc') }}</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @include('settings._field', ['name' => 'currency_symbol', 'label' => __('messages.stab_currency_symbol'), 'value' => $settings['currency_symbol'], 'tooltip' => __('messages.tip_currency_symbol')])

                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.stab_thousand_sep') }}
                            @include('settings._tooltip', ['text' => __('messages.tip_thousand_sep')])
                        </label>
                        <select name="thousand_separator" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <option value="." @selected(old('thousand_separator', $settings['thousand_separator']) === '.')>. (dot)</option>
                            <option value="," @selected(old('thousand_separator', $settings['thousand_separator']) === ',')>, (comma)</option>
                            <option value=" " @selected(old('thousand_separator', $settings['thousand_separator']) === ' ')>(space)</option>
                        </select>
                    </div>

                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.stab_decimal_sep') }}
                            @include('settings._tooltip', ['text' => __('messages.tip_decimal_sep')])
                        </label>
                        <select name="decimal_separator" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <option value="," @selected(old('decimal_separator', $settings['decimal_separator']) === ',')>, (comma)</option>
                            <option value="." @selected(old('decimal_separator', $settings['decimal_separator']) === '.')>. (dot)</option>
                        </select>
                    </div>

                    @include('settings._field', ['name' => 'decimal_places', 'label' => __('messages.stab_decimal_places'), 'type' => 'number', 'value' => $settings['decimal_places'], 'min' => 0, 'max' => 4, 'tooltip' => __('messages.tip_decimal_places')])

                    <div class="md:col-span-2">
                        <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.stab_default_locale') }}
                            @include('settings._tooltip', ['text' => __('messages.tip_default_locale')])
                        </label>
                        <select name="default_locale" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <option value="en" @selected(old('default_locale', $settings['default_locale']) === 'en')>English</option>
                            <option value="id" @selected(old('default_locale', $settings['default_locale']) === 'id')>Bahasa Indonesia</option>
                            <option value="ko" @selected(old('default_locale', $settings['default_locale']) === 'ko')>한국어 (Korean)</option>
                            <option value="zh" @selected(old('default_locale', $settings['default_locale']) === 'zh')>中文 (Chinese)</option>
                        </select>
                    </div>
                </div>

                {{-- Live Preview --}}
                <div class="mt-5 pt-4 border-t border-gray-100">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2">{{ __('messages.stab_format_preview') }}</p>
                    <div class="rounded-xl bg-gray-50 p-3 ring-1 ring-gray-100 text-sm text-gray-700 font-mono">
                        {{ $settings['currency_symbol'] }} 1{{ $settings['thousand_separator'] }}234{{ $settings['thousand_separator'] }}567{{ $settings['decimal_places'] > 0 ? $settings['decimal_separator'] . str_repeat('0', (int)$settings['decimal_places']) : '' }}
                    </div>
                </div>
            </div>

            @include('settings._save_button')
        </form>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             CONFIGURATION VERSION HISTORY
        ══════════════════════════════════════════════════════════ --}}
        @if($configHistory->isNotEmpty())
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-2 mb-4">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="text-base font-semibold text-gray-900">{{ __('messages.stab_config_history') }}</h3>
            </div>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach($configHistory as $log)
                <div class="flex items-center gap-3 rounded-lg bg-gray-50 p-2.5 text-xs ring-1 ring-gray-100">
                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                        {{ $log->action }}
                    </span>
                    <span class="text-gray-600 truncate flex-1">{{ $log->description }}</span>
                    <span class="text-gray-400 shrink-0">{{ $log->user?->name ?? 'System' }}</span>
                    <span class="text-gray-400 shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
