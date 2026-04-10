@extends('layouts.app')

@section('title', __('messages.crm_clients_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.clients_heading') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.clients_heading') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.clients_subtitle') }}</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- View Toggle --}}
            <div x-data class="inline-flex rounded-xl bg-gray-100 p-0.5 ring-1 ring-gray-200">
                <button @click="$dispatch('set-view', 'table')"
                    :class="$store.viewMode.current === 'table' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M3 6h18M3 18h18" /></svg>
                    {{ __('messages.table_view') }}
                </button>
                <button @click="$dispatch('set-view', 'pipeline')"
                    :class="$store.viewMode.current === 'pipeline' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" /></svg>
                    {{ __('messages.pipeline_view') }}
                </button>
            </div>
            @include('components.button', [
                'label' => __('messages.add_client'),
                'type' => 'primary',
                'href' => route('clients.create'),
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
            ])
        </div>
    </div>
@endsection

@section('content')
<div x-data x-init="
    Alpine.store('viewMode', { current: localStorage.getItem('crm_view') || 'table' });
    $watch('$store.viewMode.current', v => localStorage.setItem('crm_view', v));
" @set-view.window="$store.viewMode.current = $event.detail">

    {{-- Summary Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Active Clients --}}
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50">
                    <svg class="h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_active_clients') }}</p>
                    <p class="mt-0.5 text-xl font-bold text-gray-900">{{ $summary['totalActiveClients'] }}</p>
                </div>
            </div>
        </div>

        {{-- Total Receivables --}}
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                    <svg class="h-5 w-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_receivables') }}</p>
                    <p class="mt-0.5 text-xl font-bold {{ (float) $summary['totalReceivables'] > 0 ? 'text-amber-700' : 'text-gray-900' }}">
                        {{ format_currency((float) $summary['totalReceivables']) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Monthly Sales Growth --}}
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $summary['monthlySalesGrowth'] >= 0 ? 'bg-green-50' : 'bg-red-50' }}">
                    @if($summary['monthlySalesGrowth'] >= 0)
                        <svg class="h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    @else
                        <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.monthly_sales_growth') }}</p>
                    <p class="mt-0.5 text-xl font-bold {{ $summary['monthlySalesGrowth'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        {{ $summary['monthlySalesGrowth'] >= 0 ? '+' : '' }}{{ $summary['monthlySalesGrowth'] }}%
                    </p>
                    <p class="text-xs text-gray-400">{{ __('messages.growth_vs_last_month') }}</p>
                </div>
            </div>
        </div>

        {{-- Top Spender --}}
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50">
                    <svg class="h-5 w-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.top_spender') }}</p>
                    @if($summary['topSpender'])
                        <p class="mt-0.5 text-sm font-bold text-gray-900 truncate max-w-[140px]" title="{{ $summary['topSpender']->name }}">{{ $summary['topSpender']->name }}</p>
                        <p class="text-xs text-purple-600 font-medium">{{ format_currency((float) $summary['topSpender']->lifetime_sales) }}</p>
                    @else
                        <p class="mt-0.5 text-sm text-gray-400">—</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ TABLE VIEW ═══════════ --}}
    <div x-show="$store.viewMode.current === 'table'" x-cloak>
        {{-- Filters --}}
        <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
            <form method="GET" action="{{ route('clients.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('messages.search_clients_placeholder') }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                    />
                </div>
                <select name="status" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="">{{ __('messages.all_status') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('messages.active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('messages.inactive') }}</option>
                </select>
                <select name="type" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="">{{ __('messages.all_types') }}</option>
                    <option value="customer" @selected(request('type') === 'customer')>{{ __('messages.customer') }}</option>
                    <option value="lead" @selected(request('type') === 'lead')>{{ __('messages.lead') }}</option>
                    <option value="prospect" @selected(request('type') === 'prospect')>{{ __('messages.prospect') }}</option>
                </select>
                <div class="flex gap-2">
                    @include('components.button', ['label' => __('messages.filter'), 'type' => 'secondary', 'buttonType' => 'submit'])
                    @if(request()->hasAny(['search', 'status', 'type']))
                        @include('components.button', ['label' => __('messages.clear'), 'type' => 'ghost', 'href' => route('clients.index')])
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.client_column') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.contact') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.type') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.sales_to_date') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.current_balance') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.credit_status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.last_interaction') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($clients as $client)
                            @php
                                $creditLimit = (float) $client->credit_limit;
                                $balance = (float) $client->current_balance;
                                $usagePct = $creditLimit > 0 ? min(100, round(($balance / $creditLimit) * 100, 1)) : 0;
                                $overLimit = $creditLimit > 0 && $balance > $creditLimit;
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                {{-- Client Name --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700 font-semibold text-sm">
                                            {{ strtoupper(substr($client->name, 0, 2)) }}
                                            @if($client->status === 'active' && (float) $client->sales_to_date > 0)
                                                <span class="absolute -top-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-white" title="{{ __('messages.active') }}"></span>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $client->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $client->company ?? $client->code }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Contact --}}
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-700">{{ $client->email ?? '—' }}</p>
                                    <p class="text-xs text-gray-500">{{ $client->phone ?? '' }}</p>
                                </td>

                                {{-- Type --}}
                                <td class="whitespace-nowrap px-6 py-4">
                                    @php
                                        $typeBadge = match($client->type) {
                                            'customer' => 'bg-primary-50 text-primary-700 ring-primary-600/20',
                                            'lead'     => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                            'prospect' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                            default    => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $typeBadge }}">
                                        {{ __('messages.' . $client->type) }}
                                    </span>
                                </td>

                                {{-- Sales To Date --}}
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    @if((float) $client->sales_to_date > 0)
                                        <span class="text-sm font-medium text-gray-900">{{ format_currency((float) $client->sales_to_date) }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('messages.no_transactions') }}</span>
                                    @endif
                                </td>

                                {{-- Current Balance (receivables) --}}
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    @if($balance > 0)
                                        <span class="text-sm font-medium {{ $overLimit ? 'text-red-700' : 'text-gray-900' }}">
                                            {{ format_currency($balance) }}
                                        </span>
                                        @if($overLimit)
                                            <p class="text-xs text-red-500 font-medium">{{ __('messages.over_limit') }}</p>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Credit Status (progress bar) --}}
                                <td class="whitespace-nowrap px-6 py-4" style="min-width: 140px;">
                                    @if($client->is_credit_blocked)
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-red-50 text-red-700 ring-1 ring-red-600/20">{{ __('messages.blocked') }}</span>
                                    @elseif($creditLimit > 0)
                                        @php
                                            $barColor = $usagePct > 90 ? 'bg-red-500' : ($usagePct > 75 ? 'bg-amber-500' : 'bg-green-500');
                                            $textColor = $usagePct > 90 ? 'text-red-700' : ($usagePct > 75 ? 'text-amber-700' : 'text-green-700');
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden" style="min-width: 60px;">
                                                <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ $usagePct }}%"></div>
                                            </div>
                                            <span class="text-xs font-medium {{ $textColor }}">{{ $usagePct }}%</span>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ format_currency($creditLimit) }}</p>
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('messages.unlimited') }}</span>
                                    @endif
                                </td>

                                {{-- Last Interaction --}}
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $client->last_invoice_date ? \Carbon\Carbon::parse($client->last_invoice_date)->format('M d, Y') : '—' }}
                                </td>

                                {{-- Status --}}
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1
                                        {{ $client->status === 'active'
                                            ? 'bg-green-50 text-green-700 ring-green-600/20'
                                            : 'bg-gray-100 text-gray-600 ring-gray-500/20' }}">
                                        {{ $client->status === 'active' ? __('messages.active') : __('messages.inactive') }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm space-x-1">
                                    <a href="{{ route('clients.show', $client) . '?sig=' . \App\Http\Controllers\ClientController::clientHmac($client->id) }}"
                                       class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 ring-1 ring-gray-200 transition">
                                        {{ __('messages.detail') }}
                                    </a>
                                    <a href="{{ route('clients.edit', $client) }}"
                                       class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">
                                        {{ __('messages.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline" onsubmit="return confirm('{{ __('messages.delete_client_confirm', ['name' => $client->name]) }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 transition">
                                            {{ __('messages.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-500">{{ __('messages.no_clients_found') }}</p>
                                        <a href="{{ route('clients.create') }}" class="mt-3 text-sm font-medium text-primary-600 hover:text-primary-700">
                                            {{ __('messages.add_your_first_client') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($clients->hasPages())
                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $clients->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════ PIPELINE VIEW (Kanban) ═══════════ --}}
    <div x-show="$store.viewMode.current === 'pipeline'" x-cloak
         x-data="pipelineBoard()"
         class="space-y-4">

        {{-- Conversion Rate Banner --}}
        <div class="rounded-2xl bg-gradient-to-r from-primary-50 to-purple-50 p-4 shadow-sm ring-1 ring-primary-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white shadow-sm">
                        <svg class="h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ __('messages.conversion_rate') }}</p>
                        <p class="text-xs text-gray-500">{{ __('messages.conversion_rate_desc') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold {{ $conversionRate > 0 ? 'text-primary-700' : 'text-gray-400' }}">{{ $conversionRate }}%</p>
                    <p class="text-xs text-gray-500">{{ now()->translatedFormat('F Y') }}</p>
                </div>
            </div>
        </div>

        {{-- Kanban Columns --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @php
                $columns = [
                    'lead'     => ['label' => __('messages.lead'), 'color' => 'amber', 'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'prospect' => ['label' => __('messages.prospect'), 'color' => 'purple', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                    'customer' => ['label' => __('messages.customer'), 'color' => 'primary', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                ];
            @endphp

            @foreach($columns as $type => $col)
                <div class="flex flex-col rounded-2xl bg-gray-50/80 ring-1 ring-gray-200 overflow-hidden">
                    {{-- Column Header --}}
                    <div class="flex items-center justify-between px-4 py-3 bg-{{ $col['color'] }}-50/60 border-b border-{{ $col['color'] }}-100">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-{{ $col['color'] }}-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $col['icon'] }}" /></svg>
                            <h3 class="text-sm font-semibold text-{{ $col['color'] }}-800">{{ $col['label'] }}</h3>
                        </div>
                        <span class="inline-flex items-center justify-center h-5 min-w-[20px] rounded-full bg-{{ $col['color'] }}-100 px-1.5 text-xs font-bold text-{{ $col['color'] }}-700">
                            {{ ($pipelineClients[$type] ?? collect())->count() }}
                        </span>
                    </div>

                    {{-- Cards Container (sortable) --}}
                    <div class="pipeline-column flex-1 min-h-[200px] p-3 space-y-2 overflow-y-auto" data-type="{{ $type }}" style="max-height: 600px;">
                        @foreach(($pipelineClients[$type] ?? collect()) as $pc)
                            @php
                                $pcBalance = (float) $pc->current_balance;
                                $pcLimit = (float) $pc->credit_limit;
                                $pcOverLimit = $pcLimit > 0 && $pcBalance > $pcLimit;
                                $pcIdleDays = (int) $pc->updated_at->diffInDays(now());
                                $pcIsStagnant = $type === 'lead' && $pcIdleDays >= $leadFollowupDays;
                            @endphp
                            <div class="pipeline-card group cursor-grab active:cursor-grabbing rounded-xl bg-white p-3 shadow-sm ring-1 {{ $pcIsStagnant ? 'ring-red-300 bg-red-50/40' : 'ring-gray-100' }} hover:shadow-md hover:ring-gray-200 transition-all"
                                 data-client-id="{{ $pc->id }}"
                                 data-hmac-lead="{{ \App\Http\Controllers\ClientController::pipelineHmac($pc->id, 'lead') }}"
                                 data-hmac-prospect="{{ \App\Http\Controllers\ClientController::pipelineHmac($pc->id, 'prospect') }}"
                                 data-hmac-customer="{{ \App\Http\Controllers\ClientController::pipelineHmac($pc->id, 'customer') }}">
                                {{-- Stagnant Badge --}}
                                @if($pcIsStagnant)
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700 ring-1 ring-red-200">
                                            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ __('messages.days_idle', ['days' => $pcIdleDays]) }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            {{-- Email Sent Urgency Indicator --}}
                                            @if($pc->reminder_email_sent_at)
                                                <span class="inline-flex items-center rounded-full bg-red-600 p-1" title="{{ __('messages.email_reminder_sent') }} — {{ $pc->reminder_email_sent_at->diffForHumans() }}">
                                                    <svg class="h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                </span>
                                            @endif
                                            <button type="button"
                                                onclick="snoozeClient({{ $pc->id }}, this)"
                                                class="inline-flex items-center gap-1 rounded-lg px-1.5 py-0.5 text-[10px] font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 ring-1 ring-amber-200 transition"
                                                title="{{ __('messages.snooze_tooltip') }}">
                                                <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                {{ __('messages.followed_up') }}
                                            </button>
                                        </div>
                                    </div>
                                @endif
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-{{ $col['color'] }}-50 text-{{ $col['color'] }}-700 text-xs font-bold">
                                            {{ strtoupper(substr($pc->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 leading-tight">{{ $pc->name }}</p>
                                            <p class="text-xs text-gray-400">{{ $pc->company ?? $pc->code }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('clients.show', $pc) . '?sig=' . \App\Http\Controllers\ClientController::clientHmac($pc->id) }}"
                                       class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-primary-600 transition">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                </div>
                                @if((float) $pc->sales_to_date > 0 || $pcBalance > 0)
                                    <div class="flex items-center justify-between text-xs mt-1">
                                        @if((float) $pc->sales_to_date > 0)
                                            <span class="text-gray-500">{{ format_currency((float) $pc->sales_to_date) }}</span>
                                        @else
                                            <span></span>
                                        @endif
                                        @if($pcBalance > 0)
                                            <span class="{{ $pcOverLimit ? 'text-red-600 font-medium' : 'text-amber-600' }}">
                                                {{ __('messages.current_balance') }}: {{ format_currency($pcBalance) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                {{-- Last Updated --}}
                                <p class="text-[10px] text-gray-400 mt-1.5 truncate" title="{{ $pc->updated_at->format('M d, Y H:i') }}">
                                    {{ __('messages.last_updated') }}: {{ $pc->updated_at->diffForHumans() }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Toast notification for pipeline updates --}}
        <div x-show="toast.show" x-transition:enter="transition ease-out duration-300" x-transition:leave="transition ease-in duration-200"
             :class="toast.error ? 'bg-red-50 text-red-800 ring-red-200' : 'bg-green-50 text-green-800 ring-green-200'"
             class="fixed bottom-6 right-6 z-50 rounded-xl px-4 py-3 shadow-lg ring-1 text-sm font-medium max-w-sm"
             x-text="toast.message">
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
function snoozeClient(clientId, btn) {
    btn.disabled = true;
    btn.textContent = '...';
    fetch(`/clients/${clientId}/snooze`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (ok) {
            // Remove stagnant badge and red styling from card
            const card = btn.closest('.pipeline-card');
            card.classList.remove('ring-red-300', 'bg-red-50/40');
            card.classList.add('ring-gray-100');
            const badge = card.querySelector('.flex.items-center.justify-between.mb-2');
            if (badge && badge.querySelector('[onclick]')) badge.remove();
            Alpine.store('viewMode').current === 'pipeline' && window.dispatchEvent(new CustomEvent('toast-message', { detail: { message: data.message } }));
        } else {
            btn.disabled = false;
            btn.textContent = '{{ __("messages.followed_up") }}';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = '{{ __("messages.followed_up") }}';
    });
}
function pipelineBoard() {
    return {
        toast: { show: false, message: '', error: false },
        showToast(msg, isError = false) {
            this.toast = { show: true, message: msg, error: isError };
            setTimeout(() => this.toast.show = false, 4000);
        },
        init() {
            this.$nextTick(() => {
                document.querySelectorAll('.pipeline-column').forEach(col => {
                    new Sortable(col, {
                        group: 'pipeline',
                        animation: 200,
                        ghostClass: 'opacity-30',
                        dragClass: 'shadow-xl',
                        handle: '.pipeline-card',
                        onEnd: (evt) => {
                            const card = evt.item;
                            const newType = evt.to.dataset.type;
                            const clientId = card.dataset.clientId;
                            const sig = card.dataset['hmac' + newType.charAt(0).toUpperCase() + newType.slice(1)];

                            if (evt.from === evt.to) return;

                            fetch(`/clients/${clientId}/type`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ type: newType, sig: sig }),
                            })
                            .then(r => r.json().then(data => ({ ok: r.ok, data })))
                            .then(({ ok, data }) => {
                                if (!ok) {
                                    evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);
                                    this.showToast(data.error || 'Update failed', true);
                                } else {
                                    this.showToast(data.message);
                                    // Update column counters
                                    document.querySelectorAll('.pipeline-column').forEach(c => {
                                        const count = c.querySelectorAll('.pipeline-card').length;
                                        c.closest('.flex.flex-col').querySelector('span').textContent = count;
                                    });
                                }
                            })
                            .catch(() => {
                                evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);
                                this.showToast('Network error', true);
                            });
                        }
                    });
                });
            });
        }
    };
}
</script>
@endpush
