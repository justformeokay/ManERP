@extends('layouts.app')

@section('title', __('messages.license_management'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.license_management') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.license_management') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.license_subtitle') }}</p>
    </div>
    <a href="{{ route('license.activate') }}"
       class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
        </svg>
        {{ __('messages.license_activate') }}
    </a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- License Status Card --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.license_status') }}</h3>

        @if($license)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Plan --}}
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('messages.license_plan') }}</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">{{ $license->plan_name }}</p>
                    <span class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                        {{ $license->license_type === 'lifetime' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ $license->license_type === 'lifetime' ? __('messages.license_lifetime') : __('messages.license_subscription') }}
                    </span>
                </div>

                {{-- Status --}}
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('messages.status') }}</p>
                    @if($license->isValid() && !$license->isExpired())
                        <p class="mt-1 text-lg font-bold text-green-600">{{ __('messages.license_active') }}</p>
                    @elseif($license->isInGracePeriod())
                        <p class="mt-1 text-lg font-bold text-amber-600">{{ __('messages.license_grace_period') }}</p>
                    @else
                        <p class="mt-1 text-lg font-bold text-red-600">{{ __('messages.license_expired') }}</p>
                    @endif
                </div>

                {{-- Expiry --}}
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('messages.license_expires') }}</p>
                    @if($license->license_type === 'lifetime')
                        <p class="mt-1 text-lg font-bold text-gray-900">{{ __('messages.license_never') }}</p>
                    @elseif($license->expires_at)
                        <p class="mt-1 text-lg font-bold text-gray-900">{{ $license->expires_at->format('M d, Y') }}</p>
                        @if($license->daysUntilExpiry() !== null && $license->daysUntilExpiry() > 0)
                            <p class="text-xs text-gray-500">{{ __('messages.license_days_remaining', ['days' => $license->daysUntilExpiry()]) }}</p>
                        @endif
                    @else
                        <p class="mt-1 text-lg font-bold text-gray-400">—</p>
                    @endif
                </div>

                {{-- Activated --}}
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('messages.license_activated_at') }}</p>
                    @if($license->activated_at)
                        <p class="mt-1 text-lg font-bold text-gray-900">{{ $license->activated_at->format('M d, Y') }}</p>
                    @else
                        <p class="mt-1 text-lg font-bold text-gray-400">{{ __('messages.license_not_activated') }}</p>
                    @endif
                </div>
            </div>

            {{-- Company Info --}}
            @if($license->company_name || $license->domain)
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($license->company_name)
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('messages.company_name') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $license->company_name }}</p>
                </div>
                @endif
                @if($license->domain)
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('messages.license_domain') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $license->domain }}</p>
                </div>
                @endif
            </div>
            @endif

        @else
            <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-center">
                <svg class="mx-auto h-8 w-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <p class="mt-2 text-sm font-medium text-amber-800">{{ __('messages.license_no_license') }}</p>
                <a href="{{ route('license.activate') }}" class="mt-3 inline-flex items-center rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition">
                    {{ __('messages.license_activate') }}
                </a>
            </div>
        @endif
    </div>

    {{-- Usage Tracker --}}
    @if($license)
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.license_usage') }}</h3>

        <div class="space-y-4">
            {{-- User Limit --}}
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-600">{{ __('messages.license_active_users') }}</span>
                    <span class="font-semibold text-gray-900">{{ $activeUsers }} / {{ $license->user_limit }}</span>
                </div>
                @php
                    $usagePercent = $license->user_limit > 0 ? min(100, round(($activeUsers / $license->user_limit) * 100)) : 0;
                    $barColor = $usagePercent >= 90 ? 'bg-red-500' : ($usagePercent >= 70 ? 'bg-amber-500' : 'bg-green-500');
                @endphp
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="{{ $barColor }} h-3 rounded-full transition-all duration-300" style="width: {{ $usagePercent }}%"></div>
                </div>
                @if($usagePercent >= 90)
                    <p class="mt-1 text-xs text-red-600">{{ __('messages.license_user_limit_warning') }}</p>
                @endif
            </div>

            {{-- Features --}}
            @if($license->features_enabled && count($license->features_enabled) > 0)
            <div>
                <p class="text-sm font-medium text-gray-600 mb-2">{{ __('messages.license_features') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($license->features_enabled as $feature)
                        <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/10">
                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ ucfirst(str_replace('_', ' ', $feature)) }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Renewal CTA --}}
    @if($license && $license->license_type === 'subscription' && $license->daysUntilExpiry() !== null && $license->daysUntilExpiry() <= 30)
    <div class="rounded-2xl bg-gradient-to-r from-primary-600 to-primary-700 p-6 shadow-sm text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold">{{ __('messages.license_renew_title') }}</h3>
                <p class="mt-1 text-sm text-primary-100">{{ __('messages.license_renew_description') }}</p>
            </div>
            <a href="mailto:support@manerp.com" class="inline-flex items-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-primary-700 hover:bg-primary-50 transition">
                {{ __('messages.license_renew_now') }}
            </a>
        </div>
    </div>
    @endif

</div>
@endsection
