@extends('layouts.app')

@section('title', $client->name)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('clients.index') }}" class="hover:text-gray-700">{{ __('messages.clients_heading') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $client->name }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $client->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $client->company ?? $client->code }}</p>
        </div>
        <div class="flex gap-2">
            @include('components.button', [
                'label' => __('messages.edit'),
                'type' => 'secondary',
                'href' => route('clients.edit', $client),
            ])
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Credit Status Card --}}
        <div class="lg:col-span-2 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.credit_status') }}</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                {{-- Credit Limit --}}
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">{{ __('messages.credit_limit') }}</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">
                        @if((float) $client->credit_limit > 0)
                            {{ format_currency((float) $client->credit_limit) }}
                        @else
                            {{ __('messages.unlimited') }}
                        @endif
                    </p>
                </div>

                {{-- Current Exposure --}}
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">{{ __('messages.current_exposure') }}</p>
                    <p class="mt-1 text-xl font-bold {{ (float) $exposure > 0 ? 'text-amber-600' : 'text-gray-900' }}">
                        {{ format_currency((float) $exposure) }}
                    </p>
                </div>

                {{-- Available Credit --}}
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">{{ __('messages.available_credit') }}</p>
                    @if((float) $client->credit_limit > 0)
                        @php $available = bcsub((string) $client->credit_limit, $exposure, 2); @endphp
                        <p class="mt-1 text-xl font-bold {{ (float) $available < 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ format_currency((float) $available) }}
                        </p>
                    @else
                        <p class="mt-1 text-xl font-bold text-green-600">{{ __('messages.unlimited') }}</p>
                    @endif
                </div>
            </div>

            {{-- Usage Progress Bar --}}
            @if((float) $client->credit_limit > 0)
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>{{ __('messages.credit_usage') }}</span>
                        <span>{{ $usagePercent }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        @php
                            $barColor = $usagePercent >= 90 ? 'bg-red-500' : ($usagePercent >= 70 ? 'bg-amber-500' : 'bg-green-500');
                        @endphp
                        <div class="{{ $barColor }} h-3 rounded-full transition-all duration-500" style="width: {{ min($usagePercent, 100) }}%"></div>
                    </div>
                </div>
            @endif

            {{-- Alerts --}}
            @if($client->is_credit_blocked)
                <div class="mt-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 flex items-start gap-3">
                    <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <div>
                        <p class="text-sm font-medium text-red-800">{{ __('messages.credit_manually_blocked') }}</p>
                        <p class="text-xs text-red-600 mt-0.5">{{ __('messages.credit_blocked_desc') }}</p>
                    </div>
                </div>
            @endif

            @if($overdue['blocked'])
                <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 flex items-start gap-3">
                    <svg class="h-5 w-5 text-amber-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <div>
                        <p class="text-sm font-medium text-amber-800">{{ __('messages.overdue_invoices_warning', ['count' => $overdue['count'], 'days' => $overdue['worst_days']]) }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Client Info Card --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.client_info') }}</h3>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-gray-500">{{ __('messages.code') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $client->code }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('messages.email') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $client->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('messages.phone') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $client->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('messages.type') }}</dt>
                    <dd class="font-medium text-gray-900">{{ ucfirst($client->type) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('messages.payment_terms') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $client->payment_terms }} {{ __('messages.days') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('messages.status') }}</dt>
                    <dd>
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1
                            {{ $client->status === 'active'
                                ? 'bg-green-50 text-green-700 ring-green-600/20'
                                : 'bg-gray-100 text-gray-600 ring-gray-500/20' }}">
                            {{ $client->status === 'active' ? __('messages.active') : __('messages.inactive') }}
                        </span>
                    </dd>
                </div>
                @if($client->address)
                <div>
                    <dt class="text-gray-500">{{ __('messages.address_label') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $client->address }}{{ $client->city ? ', ' . $client->city : '' }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
@endsection
