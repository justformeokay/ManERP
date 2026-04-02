@extends('layouts.app')

@section('title', __('messages.bank_accounts_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.bank_accounts_title') }}</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.bank_accounts_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.bank_accounts_subtitle') }}</p>
        </div>
        <div class="flex gap-2">
            @include('components.button', ['label' => __('messages.add_bank_account'), 'type' => 'primary', 'href' => route('accounting.bank.create')])
            @include('components.button', ['label' => __('messages.bank_reconciliation'), 'type' => 'ghost', 'href' => route('accounting.bank.reconciliation.index')])
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_accounts') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-700">{{ $accounts->count() }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.active') }}</p>
            <p class="mt-1 text-2xl font-bold text-green-700">{{ $accounts->where('is_active', true)->count() }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_balance') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-700">{{ format_currency($accounts->sum('current_balance')) }}</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="px-6 py-4">{{ __('messages.bank_name') }}</th>
                    <th class="px-6 py-4">{{ __('messages.account_number') }}</th>
                    <th class="px-6 py-4">{{ __('messages.account_name') }}</th>
                    <th class="px-6 py-4">{{ __('messages.coa_account') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.current_balance') }}</th>
                    <th class="px-6 py-4">{{ __('messages.status') }}</th>
                    <th class="px-6 py-4">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($accounts as $account)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $account->bank_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $account->account_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $account->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $account->chartOfAccount?->code }} - {{ $account->chartOfAccount?->name }}</td>
                        <td class="px-6 py-4 text-sm text-right font-semibold {{ $account->current_balance >= 0 ? 'text-green-700' : 'text-red-600' }}">
                            {{ format_currency($account->current_balance) }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $account->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $account->is_active ? __('messages.active') : __('messages.inactive') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('accounting.bank.transactions', $account) }}" class="text-sm text-blue-600 hover:underline">
                                {{ __('messages.transactions') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">
                            {{ __('messages.no_bank_accounts') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
