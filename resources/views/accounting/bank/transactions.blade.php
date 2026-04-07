@extends('layouts.app')

@section('title', __('messages.transactions') . ' - ' . $bankAccount->name)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.bank.index') }}" class="hover:text-gray-700">{{ __('messages.bank_accounts_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $bankAccount->name }}</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $bankAccount->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $bankAccount->bank_name }} &mdash; {{ $bankAccount->account_number }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('messages.current_balance') }}</p>
            <p class="text-2xl font-bold {{ $bankAccount->current_balance >= 0 ? 'text-green-700' : 'text-red-600' }}">
                {{ format_currency($bankAccount->current_balance) }}
            </p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Add Transaction Form --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('messages.record_transaction') }}</h3>
        <form method="POST" action="{{ route('accounting.bank.transactions.store', $bankAccount) }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.date') }}</label>
                <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.type') }}</label>
                <select name="type" required class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                    <option value="debit">{{ __('messages.debit') }} ({{ __('messages.money_in') }})</option>
                    <option value="credit">{{ __('messages.credit') }} ({{ __('messages.money_out') }})</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.amount') }}</label>
                <input type="text" name="amount" x-currency required
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm w-32">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.description') }}</label>
                <input type="text" name="description" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.reference') }}</label>
                <input type="text" name="reference"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm w-32">
            </div>
            @include('components.button', ['label' => __('messages.save'), 'type' => 'primary', 'buttonType' => 'submit'])
        </form>
    </div>

    {{-- Transactions Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="px-6 py-4">{{ __('messages.date') }}</th>
                    <th class="px-6 py-4">{{ __('messages.description') }}</th>
                    <th class="px-6 py-4">{{ __('messages.reference') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.debit') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.credit') }}</th>
                    <th class="px-6 py-4">{{ __('messages.reconciled') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($transactions as $tx)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-3 text-sm text-gray-600">{{ \Carbon\Carbon::parse($tx->transaction_date)->format('d M Y') }}</td>
                        <td class="px-6 py-3 text-sm text-gray-900">{{ $tx->description }}</td>
                        <td class="px-6 py-3 text-sm text-gray-500">{{ $tx->reference ?: '-' }}</td>
                        <td class="px-6 py-3 text-sm text-right text-green-700">{{ $tx->type === 'debit' ? format_currency($tx->amount) : '' }}</td>
                        <td class="px-6 py-3 text-sm text-right text-red-600">{{ $tx->type === 'credit' ? format_currency($tx->amount) : '' }}</td>
                        <td class="px-6 py-3">
                            @if($tx->is_reconciled)
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">✓</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('messages.no_transactions') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($transactions->hasPages())
            <div class="px-6 py-3 border-t border-gray-100">{{ $transactions->links() }}</div>
        @endif
    </div>
@endsection
