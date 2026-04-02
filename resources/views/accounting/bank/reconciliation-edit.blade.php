@extends('layouts.app')

@section('title', __('messages.reconcile') . ' - ' . $reconciliation->bankAccount->name)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.bank.reconciliation.index') }}" class="hover:text-gray-700">{{ __('messages.bank_reconciliation') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.reconcile') }}</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.reconcile') }}: {{ $reconciliation->bankAccount->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.statement_date') }}: {{ \Carbon\Carbon::parse($reconciliation->statement_date)->format('d M Y') }}</p>
        </div>
        @if(abs($reconciliation->difference) < 0.01)
            <form method="POST" action="{{ route('accounting.bank.reconciliation.complete', $reconciliation) }}">
                @csrf
                @include('components.button', ['label' => __('messages.complete_reconciliation'), 'type' => 'primary', 'buttonType' => 'submit'])
            </form>
        @endif
    </div>
@endsection

@section('content')
    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.statement_balance') }}</p>
            <p class="mt-1 text-xl font-bold text-gray-700">{{ format_currency($reconciliation->statement_balance) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.book_balance') }}</p>
            <p class="mt-1 text-xl font-bold text-gray-700">{{ format_currency($reconciliation->book_balance) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.reconciled_items') }}</p>
            <p class="mt-1 text-xl font-bold text-blue-700">{{ $reconciledTransactions->count() }}</p>
        </div>
        <div class="rounded-2xl {{ abs($reconciliation->difference) < 0.01 ? 'bg-green-50 ring-green-200' : 'bg-red-50 ring-red-200' }} p-5 shadow-sm ring-1">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.difference') }}</p>
            <p class="mt-1 text-xl font-bold {{ abs($reconciliation->difference) < 0.01 ? 'text-green-700' : 'text-red-600' }}">
                {{ format_currency($reconciliation->difference) }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Unreconciled Transactions --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="bg-yellow-50 px-6 py-4 border-b border-yellow-100">
                <h3 class="text-base font-semibold text-yellow-900">{{ __('messages.unreconciled_transactions') }}</h3>
            </div>
            <div class="divide-y divide-gray-50 max-h-[500px] overflow-y-auto">
                @forelse($transactions as $tx)
                    <div class="flex items-center justify-between px-6 py-3 hover:bg-gray-50/50">
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">{{ $tx->description }}</p>
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($tx->transaction_date)->format('d M Y') }} &mdash; {{ $tx->reference }}</p>
                        </div>
                        <div class="text-sm font-medium {{ $tx->type === 'debit' ? 'text-green-700' : 'text-red-600' }} mr-4">
                            {{ $tx->type === 'debit' ? '+' : '-' }}{{ format_currency($tx->amount) }}
                        </div>
                        <form method="POST" action="{{ route('accounting.bank.reconciliation.toggle', [$reconciliation, $tx]) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                {{ __('messages.reconcile') }}
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-sm text-gray-400">{{ __('messages.all_reconciled') }}</div>
                @endforelse
            </div>
        </div>

        {{-- Reconciled Transactions --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="bg-green-50 px-6 py-4 border-b border-green-100">
                <h3 class="text-base font-semibold text-green-900">{{ __('messages.reconciled_items') }} ({{ $reconciledTransactions->count() }})</h3>
            </div>
            <div class="divide-y divide-gray-50 max-h-[500px] overflow-y-auto">
                @forelse($reconciledTransactions as $tx)
                    <div class="flex items-center justify-between px-6 py-3 hover:bg-gray-50/50">
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">{{ $tx->description }}</p>
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($tx->transaction_date)->format('d M Y') }}</p>
                        </div>
                        <div class="text-sm font-medium {{ $tx->type === 'debit' ? 'text-green-700' : 'text-red-600' }} mr-4">
                            {{ $tx->type === 'debit' ? '+' : '-' }}{{ format_currency($tx->amount) }}
                        </div>
                        <form method="POST" action="{{ route('accounting.bank.reconciliation.toggle', [$reconciliation, $tx]) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200">
                                {{ __('messages.undo') }}
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-sm text-gray-400">{{ __('messages.no_reconciled_items') }}</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
