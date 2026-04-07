@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6" x-data="dashboardApp()" x-init="init()">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.dashboard') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ str_replace('{name}', auth()->user()->name, __('messages.welcome_back')) }}</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-400">
            <span class="inline-block h-2 w-2 rounded-full" :class="polling ? 'bg-green-400 animate-pulse' : 'bg-gray-300'"></span>
            {{ __('dashboard.auto_refresh') }}
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        {{-- Total Clients --}}
        <a href="{{ route('clients.index') }}" class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100 hover:shadow-md hover:ring-blue-200 transition group">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 group-hover:bg-blue-200 transition">
                    <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_clients']) }}</p>
                    <p class="text-xs text-gray-500">{{ __('messages.total_clients') }}</p>
                </div>
            </div>
        </a>

        {{-- Total Products --}}
        <a href="{{ route('inventory.products.index') }}" class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100 hover:shadow-md hover:ring-green-200 transition group">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100 group-hover:bg-green-200 transition">
                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_products']) }}</p>
                    <p class="text-xs text-gray-500">{{ __('messages.total_products') }}</p>
                </div>
            </div>
        </a>

        {{-- Sales Orders --}}
        <a href="{{ route('sales.index') }}" class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100 hover:shadow-md hover:ring-purple-200 transition group">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 group-hover:bg-purple-200 transition">
                    <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['sales_orders']) }}</p>
                    <p class="text-xs text-gray-500">{{ __('messages.sales_orders') }}</p>
                </div>
            </div>
        </a>

        {{-- Purchase Orders --}}
        <a href="{{ route('purchasing.index') }}" class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100 hover:shadow-md hover:ring-orange-200 transition group">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 group-hover:bg-orange-200 transition">
                    <svg class="h-5 w-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['purchase_orders']) }}</p>
                    <p class="text-xs text-gray-500">{{ __('messages.purchase_orders') }}</p>
                </div>
            </div>
        </a>

        {{-- Pending Manufacturing --}}
        <a href="{{ route('manufacturing.orders.index') }}" class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100 hover:shadow-md hover:ring-yellow-200 transition group">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-100 group-hover:bg-yellow-200 transition">
                    <svg class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending_manufacturing']) }}</p>
                    <p class="text-xs text-gray-500">{{ __('messages.pending_manufacturing') }}</p>
                </div>
            </div>
        </a>

        {{-- Low Stock --}}
        <a href="{{ route('inventory.stocks.index') }}" class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100 hover:shadow-md hover:ring-red-200 transition group">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 group-hover:bg-red-200 transition">
                    <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['low_stock_items']) }}</p>
                    <p class="text-xs text-gray-500">{{ __('messages.low_stock_items') }}</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Row 2: Cash on Hand + AR/AP with Sparklines --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Cash on Hand Card --}}
        <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-emerald-100 text-sm">{{ __('dashboard.cash_on_hand') }}</p>
                    <p class="text-3xl font-bold mt-1">{{ format_currency($cashOnHand) }}</p>
                </div>
                <div class="bg-white/20 rounded-xl p-2">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
            </div>
            <div class="flex gap-6 text-sm">
                <div>
                    <p class="text-emerald-200">{{ __('messages.total_revenue') }}</p>
                    <p class="font-semibold">{{ format_currency($salesStats['total_revenue']) }}</p>
                </div>
                <div>
                    <p class="text-emerald-200">{{ __('messages.this_month') }}</p>
                    <p class="font-semibold">{{ format_currency($salesStats['this_month']) }}</p>
                </div>
            </div>
        </div>

        {{-- Accounts Receivable with Sparkline --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-sm">{{ __('dashboard.accounts_receivable') }}</h3>
                </div>
                <a href="{{ route('finance.invoices.index') }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">{{ __('messages.view_all') }}</a>
            </div>
            <p class="text-2xl font-bold text-gray-900 mb-2">{{ format_currency($arStats['total_outstanding']) }}</p>
            <div class="h-10 mb-2">
                <canvas x-ref="arSparkline" class="w-full h-full"></canvas>
            </div>
            <div class="space-y-2">
                @if($arStats['overdue_count'] > 0)
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-1.5 text-red-600">
                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                        {{ $arStats['overdue_count'] }} {{ __('dashboard.overdue') }}
                    </span>
                    <span class="font-medium text-red-600">{{ format_currency($arStats['overdue_amount']) }}</span>
                </div>
                @endif
                @if($arStats['due_this_week'] > 0)
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-1.5 text-amber-600">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        {{ $arStats['due_this_week'] }} {{ __('dashboard.due_this_week') }}
                    </span>
                </div>
                @endif
                @if($arStats['overdue_count'] === 0 && $arStats['due_this_week'] === 0 && $arStats['total_outstanding'] == 0)
                <p class="text-sm text-green-600">{{ __('dashboard.all_invoices_settled') }}</p>
                @endif
            </div>
        </div>

        {{-- Accounts Payable with Sparkline --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100">
                        <svg class="h-4 w-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-sm">{{ __('dashboard.accounts_payable') }}</h3>
                </div>
                <a href="{{ route('ap.bills.index') }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">{{ __('messages.view_all') }}</a>
            </div>
            <p class="text-2xl font-bold text-gray-900 mb-2">{{ format_currency($apStats['total_outstanding']) }}</p>
            <div class="h-10 mb-2">
                <canvas x-ref="apSparkline" class="w-full h-full"></canvas>
            </div>
            <div class="space-y-2">
                @if($apStats['overdue_count'] > 0)
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-1.5 text-red-600">
                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                        {{ $apStats['overdue_count'] }} {{ __('dashboard.overdue') }}
                    </span>
                    <span class="font-medium text-red-600">{{ format_currency($apStats['overdue_amount']) }}</span>
                </div>
                @endif
                @if($apStats['due_this_week'] > 0)
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-1.5 text-amber-600">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        {{ $apStats['due_this_week'] }} {{ __('dashboard.due_this_week') }}
                    </span>
                </div>
                @endif
                @if($apStats['overdue_count'] === 0 && $apStats['due_this_week'] === 0 && $apStats['total_outstanding'] == 0)
                <p class="text-sm text-green-600">{{ __('dashboard.all_bills_settled') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Row 3: P&L Chart + Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Profit & Loss Chart --}}
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.profit_loss_chart') }}</h3>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span> {{ __('dashboard.revenue') }}</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-400"></span> {{ __('dashboard.cogs') }}</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> {{ __('dashboard.net_income') }}</span>
                    </div>
                    <select x-model="plPeriod" @change="updatePLChart()"
                        class="text-xs border-gray-300 rounded-lg py-1 pl-2 pr-7 focus:ring-blue-500 focus:border-blue-500">
                        <option value="month">{{ __('dashboard.this_month') }}</option>
                        <option value="quarter">{{ __('dashboard.this_quarter') }}</option>
                        <option value="year" selected>{{ __('dashboard.this_year') }}</option>
                    </select>
                </div>
            </div>
            <div class="h-56 relative">
                <div x-show="plLoading" class="absolute inset-0 flex items-center justify-center bg-white/70 z-10">
                    <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
                <canvas x-ref="plCanvas" class="w-full h-full"></canvas>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('messages.quick_actions') }}</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('sales.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 hover:bg-blue-50 hover:text-blue-600 transition group">
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-xs font-medium text-gray-700 group-hover:text-blue-600">{{ __('messages.new_order') }}</span>
                </a>
                <a href="{{ route('inventory.products.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 hover:bg-green-50 transition group">
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span class="text-xs font-medium text-gray-700 group-hover:text-green-600">{{ __('messages.new_product') }}</span>
                </a>
                <a href="{{ route('clients.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 hover:bg-purple-50 transition group">
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span class="text-xs font-medium text-gray-700 group-hover:text-purple-600">{{ __('messages.new_client') }}</span>
                </a>
                <a href="{{ route('purchasing.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 hover:bg-orange-50 transition group">
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                    </svg>
                    <span class="text-xs font-medium text-gray-700 group-hover:text-orange-600">{{ __('dashboard.new_purchase') }}</span>
                </a>
                <a href="{{ route('approvals.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 hover:bg-amber-50 transition group">
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-xs font-medium text-gray-700 group-hover:text-amber-600">{{ __('dashboard.approvals') }}</span>
                </a>
                <a href="{{ route('reports.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 hover:bg-indigo-50 transition group">
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span class="text-xs font-medium text-gray-700 group-hover:text-indigo-600">{{ __('messages.view_reports') }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Row 3.5: Inventory Valuation + QC Rejection Rate --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Inventory Valuation Pie --}}
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.inventory_valuation') }}</h3>
                <a href="{{ route('inventory.stocks.index') }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">{{ __('messages.view_all') }}</a>
            </div>
            <div class="flex items-center gap-8">
                <div class="h-48 w-48 flex-shrink-0">
                    <canvas x-ref="inventoryPie" class="w-full h-full"></canvas>
                </div>
                <div class="flex-1 space-y-3">
                    @php
                        $invTotal = ($inventoryValuation['raw_material'] ?? 0) + ($inventoryValuation['finished_good'] ?? 0) + ($inventoryValuation['consumable'] ?? 0);
                    @endphp
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-sm">
                            <span class="h-3 w-3 rounded-full bg-blue-500"></span>
                            {{ __('dashboard.raw_material') }}
                        </span>
                        <span class="text-sm font-semibold text-gray-900">{{ format_currency($inventoryValuation['raw_material'] ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-sm">
                            <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                            {{ __('dashboard.finished_good') }}
                        </span>
                        <span class="text-sm font-semibold text-gray-900">{{ format_currency($inventoryValuation['finished_good'] ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-sm">
                            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                            {{ __('dashboard.consumable_other') }}
                        </span>
                        <span class="text-sm font-semibold text-gray-900">{{ format_currency($inventoryValuation['consumable'] ?? 0) }}</span>
                    </div>
                    <div class="pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">{{ __('dashboard.total_valuation') }}</span>
                            <span class="text-sm font-bold text-gray-900">{{ format_currency($invTotal) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- QC Rejection Rate --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.qc_rejection_rate') }}</h3>
                <span class="text-xs text-gray-400">{{ __('dashboard.this_month') }}</span>
            </div>
            <div class="flex flex-col items-center justify-center py-4">
                <div class="relative h-32 w-32">
                    <canvas x-ref="qcGauge" class="w-full h-full"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-bold {{ ($qcRejectionRate['rate'] ?? 0) > 5 ? 'text-red-600' : 'text-emerald-600' }}">{{ $qcRejectionRate['rate'] ?? 0 }}%</span>
                        <span class="text-xs text-gray-400">{{ __('dashboard.rejection') }}</span>
                    </div>
                </div>
            </div>
            <div class="space-y-2 mt-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">{{ __('dashboard.total_inspections') }}</span>
                    <span class="font-semibold text-gray-900">{{ number_format($qcRejectionRate['total_inspections'] ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">{{ __('dashboard.inspected_qty') }}</span>
                    <span class="font-semibold text-gray-900">{{ number_format($qcRejectionRate['total_qty'] ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-red-500">{{ __('dashboard.failed_qty') }}</span>
                    <span class="font-semibold text-red-600">{{ number_format($qcRejectionRate['failed_qty'] ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 4: Pending Approvals + Active Projects --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Pending Approvals --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <h3 class="font-semibold text-gray-900">{{ __('dashboard.pending_approvals') }}</h3>
                    @if($pendingApprovals->count() > 0)
                        <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-amber-500 text-white text-xs font-bold">{{ $pendingApprovals->count() }}</span>
                    @endif
                    @if(($pendingBadges['po_pending'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700">PO <span class="font-bold">{{ $pendingBadges['po_pending'] }}</span></span>
                    @endif
                    @if(($pendingBadges['mo_pending'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700">MO <span class="font-bold">{{ $pendingBadges['mo_pending'] }}</span></span>
                    @endif
                </div>
                <a href="{{ route('approvals.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">{{ __('messages.view_all') }}</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($pendingApprovals as $approval)
                    <a href="{{ route('approvals.show', $approval) }}" class="block px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full flex items-center justify-center text-sm font-bold
                                    @if($approval->module === 'purchase_order') bg-blue-100 text-blue-700
                                    @elseif($approval->module === 'invoice') bg-purple-100 text-purple-700
                                    @elseif($approval->module === 'supplier_bill') bg-orange-100 text-orange-700
                                    @else bg-gray-100 text-gray-700 @endif">
                                    @if($approval->module === 'purchase_order') PO
                                    @elseif($approval->module === 'invoice') INV
                                    @elseif($approval->module === 'supplier_bill') BIL
                                    @elseif($approval->module === 'payment') PAY
                                    @else {{ strtoupper(substr($approval->module, 0, 2)) }} @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $approval->module_label }}</p>
                                    <p class="text-xs text-gray-500">{{ $approval->requester?->name }} &middot; {{ format_currency($approval->amount) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-gray-500">{{ $approval->created_at->diffForHumans() }}</span>
                                <div class="mt-1">
                                    <div class="h-1 w-16 rounded-full bg-gray-200">
                                        <div class="h-1 rounded-full bg-amber-500" style="width: {{ $approval->progress }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <svg class="mx-auto h-8 w-8 text-green-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm">{{ __('dashboard.no_pending_approvals') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Active Projects --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-gray-900">{{ __('dashboard.active_projects') }}</h3>
                    <div class="flex items-center gap-1.5">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700">{{ $projectStats['active'] }} {{ __('dashboard.active') }}</span>
                        @if($projectStats['on_hold'] > 0)
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700">{{ $projectStats['on_hold'] }} {{ __('dashboard.on_hold') }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('projects.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">{{ __('messages.view_all') }}</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($activeProjects as $project)
                    <div class="px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $project->name }}</p>
                                <p class="text-xs text-gray-500">{{ $project->client?->name ?? 'No Client' }} &middot; {{ $project->code }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($project->status === 'active') bg-green-100 text-green-700
                                @elseif($project->status === 'on_hold') bg-amber-100 text-amber-700
                                @else bg-gray-100 text-gray-600 @endif">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </span>
                        </div>
                        @if($project->budget)
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span>{{ __('dashboard.budget') }}: {{ format_currency($project->budget) }}</span>
                            @if($project->end_date)
                            <span>&middot;</span>
                            <span>{{ __('dashboard.deadline') }}: {{ \Carbon\Carbon::parse($project->end_date)->format('d M Y') }}</span>
                            @endif
                        </div>
                        @endif
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                        </svg>
                        <p class="text-sm">{{ __('dashboard.no_active_projects') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Row 5: Manufacturing Progress --}}
    @if($activeManufacturing->count() > 0)
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-100">
                    <svg class="h-3.5 w-3.5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.manufacturing_progress') }}</h3>
            </div>
            <a href="{{ route('manufacturing.orders.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">{{ __('messages.view_all') }}</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('dashboard.order_number') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.product_column') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('dashboard.priority') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('dashboard.progress') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($activeManufacturing as $mo)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $mo->number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $mo->product?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($mo->priority === 'urgent') bg-red-100 text-red-700
                                @elseif($mo->priority === 'high') bg-orange-100 text-orange-700
                                @elseif($mo->priority === 'normal') bg-blue-100 text-blue-700
                                @else bg-gray-100 text-gray-600 @endif">
                                {{ ucfirst($mo->priority) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @php $progress = $mo->planned_quantity > 0 ? round(($mo->produced_quantity / $mo->planned_quantity) * 100) : 0; @endphp
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-24 rounded-full bg-gray-200">
                                    <div class="h-2 rounded-full {{ $progress >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ min($progress, 100) }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-600">{{ $mo->produced_quantity }}/{{ $mo->planned_quantity }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($mo->status === 'in_progress') bg-blue-100 text-blue-700
                                @elseif($mo->status === 'confirmed') bg-green-100 text-green-700
                                @else bg-gray-100 text-gray-600 @endif">
                                {{ ucfirst(str_replace('_', ' ', $mo->status)) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Row 6: Recent Sales Orders + Recent Purchase Orders --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Sales Orders --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-semibold text-gray-900">{{ __('messages.recent_sales_orders') }}</h3>
                <a href="{{ route('sales.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">{{ __('messages.view_all') }}</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentSales as $order)
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-700">
                                {{ strtoupper(substr($order->client->name ?? 'N', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $order->number }}</p>
                                <p class="text-xs text-gray-500">{{ $order->client->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">{{ format_currency($order->total ?? 0) }}</p>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($order->status === 'completed') bg-green-100 text-green-700
                                @elseif($order->status === 'draft') bg-gray-100 text-gray-600
                                @elseif($order->status === 'confirmed') bg-blue-100 text-blue-700
                                @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                                @else bg-yellow-100 text-yellow-700
                                @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm">{{ __('messages.no_sales_orders') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Purchase Orders --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-semibold text-gray-900">{{ __('messages.recent_purchase_orders') }}</h3>
                <a href="{{ route('purchasing.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">{{ __('messages.view_all') }}</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentPurchases as $order)
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-orange-100 flex items-center justify-center text-sm font-bold text-orange-700">
                                {{ strtoupper(substr($order->supplier->name ?? 'N', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $order->number }}</p>
                                <p class="text-xs text-gray-500">{{ $order->supplier->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">{{ format_currency($order->total ?? 0) }}</p>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($order->status === 'received') bg-green-100 text-green-700
                                @elseif($order->status === 'draft') bg-gray-100 text-gray-600
                                @elseif($order->status === 'confirmed') bg-blue-100 text-blue-700
                                @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                                @else bg-yellow-100 text-yellow-700
                                @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm">{{ __('messages.no_purchase_orders') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Row 7: Recent Activity + Low Stock --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Activity --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-gray-900">{{ __('dashboard.recent_activity') }}</h3>
                </div>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('audit-logs.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">{{ __('messages.view_all') }}</a>
                @endif
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentActivity as $activity)
                    <div class="px-6 py-3 flex items-start gap-3">
                        <div class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-full flex-shrink-0
                            @if($activity->action === 'create') bg-green-100
                            @elseif($activity->action === 'update') bg-blue-100
                            @elseif($activity->action === 'delete') bg-red-100
                            @elseif($activity->action === 'confirm') bg-purple-100
                            @else bg-gray-100 @endif">
                            @if($activity->action === 'create')
                                <svg class="h-3.5 w-3.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            @elseif($activity->action === 'update')
                                <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                            @elseif($activity->action === 'delete')
                                <svg class="h-3.5 w-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                            @else
                                <svg class="h-3.5 w-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-700 truncate">{{ $activity->description }}</p>
                            <p class="text-xs text-gray-400">{{ $activity->user?->name ?? 'System' }} &middot; {{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm">{{ __('dashboard.no_recent_activity') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Low Stock Alert --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    @if($lowStockItems->count() > 0)
                    <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    @endif
                    <h3 class="font-semibold text-gray-900">{{ __('messages.low_stock_warning') }}</h3>
                    @if($lowStockItems->count() > 0)
                    <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-red-500 text-white text-xs font-bold">{{ $stats['low_stock_items'] }}</span>
                    @endif
                </div>
                <a href="{{ route('inventory.stocks.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">{{ __('messages.manage_stock') }}</a>
            </div>
            @if($lowStockItems->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.product_column') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.stock') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.min_stock') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($lowStockItems as $stock)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <p class="text-sm font-medium text-gray-900">{{ $stock->product->name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ $stock->product->sku ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-3 text-sm text-right font-semibold text-red-600">{{ number_format($stock->quantity) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-500">{{ number_format($stock->product->min_stock ?? 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="px-6 py-8 text-center text-gray-500">
                <svg class="mx-auto h-8 w-8 text-green-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm">{{ __('dashboard.stock_levels_healthy') }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function dashboardApp() {
    return {
        polling: true,
        plPeriod: 'year',
        plLoading: false,
        plChart: null,
        arSparkChart: null,
        apSparkChart: null,
        inventoryChart: null,
        qcChart: null,

        // Initial data from server
        plData: @json($profitLossChart),
        arTrend: @json($arTrend),
        apTrend: @json($apTrend),
        inventoryValuation: @json($inventoryValuation),
        qcRate: {{ $qcRejectionRate['rate'] ?? 0 }},

        init() {
            this.$nextTick(() => {
                this.renderPLChart();
                this.renderSparkline(this.$refs.arSparkline, this.arTrend, 'rgba(16, 185, 129, 0.8)', 'rgba(16, 185, 129, 0.1)');
                this.renderSparkline(this.$refs.apSparkline, this.apTrend, 'rgba(249, 115, 22, 0.8)', 'rgba(249, 115, 22, 0.1)');
                this.renderInventoryPie();
                this.renderQcGauge();
            });

            // Auto-refresh every 15 minutes
            setInterval(() => this.refresh(), 900000);
        },

        async refresh() {
            this.polling = true;
            try {
                const res = await fetch('{{ route("dashboard.api") }}?period=' + this.plPeriod, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.plData = data.profitLossChart;
                    this.updatePLChartData();
                }
            } catch (e) {
                console.error('Dashboard refresh failed:', e);
            }
            setTimeout(() => { this.polling = true; }, 500);
        },

        async updatePLChart() {
            this.plLoading = true;
            try {
                const res = await fetch('{{ route("dashboard.api") }}?chart_only=1&period=' + this.plPeriod, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.plData = data.profitLossChart;
                    this.updatePLChartData();
                }
            } catch (e) {
                console.error('PL chart update failed:', e);
            }
            this.plLoading = false;
        },

        updatePLChartData() {
            if (!this.plChart) return;
            this.plChart.data.labels = this.plData.labels;
            this.plChart.data.datasets[0].data = this.plData.revenue;
            this.plChart.data.datasets[1].data = this.plData.cogs;
            this.plChart.data.datasets[2].data = this.plData.netIncome;
            this.plChart.update();
        },

        renderPLChart() {
            const ctx = this.$refs.plCanvas?.getContext('2d');
            if (!ctx) return;

            this.plChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.plData.labels,
                    datasets: [
                        {
                            label: '{{ __("dashboard.revenue") }}',
                            data: this.plData.revenue,
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderRadius: 6,
                            barPercentage: 0.3,
                            categoryPercentage: 0.7,
                            order: 2,
                        },
                        {
                            label: '{{ __("dashboard.cogs") }}',
                            data: this.plData.cogs,
                            backgroundColor: 'rgba(248, 113, 113, 0.8)',
                            borderRadius: 6,
                            barPercentage: 0.3,
                            categoryPercentage: 0.7,
                            order: 3,
                        },
                        {
                            label: '{{ __("dashboard.net_income") }}',
                            data: this.plData.netIncome,
                            type: 'line',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            pointRadius: 3,
                            pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                            fill: true,
                            tension: 0.3,
                            order: 1,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + ManERP.formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                font: { size: 11 },
                                callback: function(value) {
                                    if (value >= 1000000000) return (value/1000000000).toFixed(1) + 'B';
                                    if (value >= 1000000) return (value/1000000).toFixed(0) + 'M';
                                    if (value >= 1000) return (value/1000).toFixed(0) + 'K';
                                    return value;
                                }
                            }
                        }
                    }
                }
            });
        },

        renderSparkline(canvas, data, lineColor, fillColor) {
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map((_, i) => i),
                    datasets: [{
                        data: data,
                        borderColor: lineColor,
                        backgroundColor: fillColor,
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: true,
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    scales: {
                        x: { display: false },
                        y: { display: false }
                    },
                    elements: { line: { borderWidth: 1.5 } }
                }
            });
        },

        renderInventoryPie() {
            const canvas = this.$refs.inventoryPie;
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const vals = this.inventoryValuation;

            this.inventoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['{{ __("dashboard.raw_material") }}', '{{ __("dashboard.finished_good") }}', '{{ __("dashboard.consumable_other") }}'],
                    datasets: [{
                        data: [vals.raw_material || 0, vals.finished_good || 0, vals.consumable || 0],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)'
                        ],
                        borderWidth: 0,
                        spacing: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + ManERP.formatCurrency(context.parsed);
                                }
                            }
                        }
                    }
                }
            });
        },

        renderQcGauge() {
            const canvas = this.$refs.qcGauge;
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const rate = this.qcRate;
            const good = 100 - rate;

            this.qcChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [rate, good],
                        backgroundColor: [
                            rate > 5 ? 'rgba(239, 68, 68, 0.8)' : 'rgba(16, 185, 129, 0.8)',
                            'rgba(229, 231, 235, 0.5)'
                        ],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    rotation: -90,
                    circumference: 180,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
        }
    };
}
</script>
@endpush
@endsection