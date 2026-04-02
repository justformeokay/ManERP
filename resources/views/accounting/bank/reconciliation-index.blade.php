@extends('layouts.app')

@section('title', __('messages.bank_reconciliation'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.bank.index') }}" class="hover:text-gray-700">{{ __('messages.bank_accounts_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.bank_reconciliation') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.bank_reconciliation') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.bank_reconciliation_subtitle') }}</p>
    </div>
@endsection

@section('content')
    {{-- Start New Reconciliation --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('messages.start_reconciliation') }}</h3>
        <form method="POST" action="{{ route('accounting.bank.reconciliation.create') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.bank_account') }}</label>
                <select name="bank_account_id" required class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                    <option value="">-- {{ __('messages.select') }} --</option>
                    @foreach($bankAccounts as $ba)
                        <option value="{{ $ba->id }}">{{ $ba->bank_name }} - {{ $ba->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.statement_date') }}</label>
                <input type="date" name="statement_date" value="{{ now()->toDateString() }}" required
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.statement_balance') }}</label>
                <input type="number" name="statement_balance" step="0.01" required
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm w-40">
            </div>
            @include('components.button', ['label' => __('messages.start'), 'type' => 'primary', 'buttonType' => 'submit'])
        </form>
    </div>

    {{-- Reconciliation History --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">{{ __('messages.reconciliation_history') }}</h3>
        </div>
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="px-6 py-4">{{ __('messages.bank_account') }}</th>
                    <th class="px-6 py-4">{{ __('messages.statement_date') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.statement_balance') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.book_balance') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.difference') }}</th>
                    <th class="px-6 py-4">{{ __('messages.status') }}</th>
                    <th class="px-6 py-4">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($reconciliations as $rec)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-3 text-sm text-gray-900">{{ $rec->bankAccount->name }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600">{{ \Carbon\Carbon::parse($rec->statement_date)->format('d M Y') }}</td>
                        <td class="px-6 py-3 text-sm text-right">{{ format_currency($rec->statement_balance) }}</td>
                        <td class="px-6 py-3 text-sm text-right">{{ format_currency($rec->book_balance) }}</td>
                        <td class="px-6 py-3 text-sm text-right font-semibold {{ abs($rec->difference) < 0.01 ? 'text-green-700' : 'text-red-600' }}">
                            {{ format_currency($rec->difference) }}
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $rec->status === 'completed' ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">
                                {{ ucfirst($rec->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            @if($rec->status === 'draft')
                                <a href="{{ route('accounting.bank.reconciliation.edit', $rec) }}" class="text-sm text-blue-600 hover:underline">{{ __('messages.continue') }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('messages.no_reconciliations') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($reconciliations->hasPages())
            <div class="px-6 py-3 border-t border-gray-100">{{ $reconciliations->links() }}</div>
        @endif
    </div>
@endsection
