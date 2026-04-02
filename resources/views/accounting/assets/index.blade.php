@extends('layouts.app')

@section('title', __('messages.fixed_assets_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.fixed_assets_title') }}</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.fixed_assets_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.fixed_assets_subtitle') }}</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('accounting.assets.run-depreciation') }}" class="inline" x-data>
                @csrf
                <input type="hidden" name="period_date" value="{{ now()->startOfMonth()->toDateString() }}">
                <button type="submit" onclick="return confirm('{{ __('messages.confirm_run_depreciation') }}')"
                    class="inline-flex items-center rounded-xl bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100">
                    {{ __('messages.run_depreciation') }}
                </button>
            </form>
            @include('components.button', ['label' => __('messages.add_asset'), 'type' => 'primary', 'href' => route('accounting.assets.create')])
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_assets') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-700">{{ $summary['total'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.active') }}</p>
            <p class="mt-1 text-2xl font-bold text-green-700">{{ $summary['active'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_cost') }}</p>
            <p class="mt-1 text-xl font-bold text-gray-700">{{ format_currency($summary['total_cost']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.accumulated_depreciation') }}</p>
            <p class="mt-1 text-xl font-bold text-amber-700">{{ format_currency($summary['total_depreciation']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.net_book_value') }}</p>
            <p class="mt-1 text-xl font-bold text-blue-700">{{ format_currency($summary['total_book_value']) }}</p>
        </div>
    </div>

    {{-- Assets Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="px-6 py-4">{{ __('messages.code') }}</th>
                    <th class="px-6 py-4">{{ __('messages.name') }}</th>
                    <th class="px-6 py-4">{{ __('messages.category') }}</th>
                    <th class="px-6 py-4">{{ __('messages.purchase_date') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.purchase_cost') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.net_book_value') }}</th>
                    <th class="px-6 py-4">{{ __('messages.status') }}</th>
                    <th class="px-6 py-4">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($assets as $asset)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-3 text-sm font-mono text-gray-700">{{ $asset->code }}</td>
                        <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $asset->name }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600">{{ ucfirst($asset->category) }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600">{{ $asset->purchase_date->format('d M Y') }}</td>
                        <td class="px-6 py-3 text-sm text-right text-gray-700">{{ format_currency($asset->purchase_cost) }}</td>
                        <td class="px-6 py-3 text-sm text-right font-semibold text-gray-700">{{ format_currency($asset->book_value) }}</td>
                        <td class="px-6 py-3">
                            @php
                                $statusColor = match($asset->status) {
                                    'active' => 'bg-green-50 text-green-700',
                                    'fully_depreciated' => 'bg-amber-50 text-amber-700',
                                    'disposed' => 'bg-gray-100 text-gray-600',
                                    'sold' => 'bg-blue-50 text-blue-700',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                                {{ str_replace('_', ' ', ucfirst($asset->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <a href="{{ route('accounting.assets.show', $asset) }}" class="text-sm text-blue-600 hover:underline">{{ __('messages.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('messages.no_assets') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
