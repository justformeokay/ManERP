@extends('layouts.app')

@section('title', 'General Ledger')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">General Ledger</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">General Ledger</h1>
        <p class="mt-1 text-sm text-gray-500">View account transactions with running balance</p>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[250px]">
                <label for="account_id" class="block text-xs font-medium text-gray-500 mb-1">Account</label>
                <select name="account_id" id="account_id"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    required>
                    <option value="">Select account...</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" @selected(request('account_id') == $acc->id)>
                            {{ $acc->code }} — {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="from" id="from" value="{{ request('from') }}"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div>
                <label for="to" class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="to" id="to" value="{{ request('to') }}"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div class="flex gap-2">
                @include('components.button', ['label' => 'View Ledger', 'type' => 'primary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['account_id']))
                    @include('components.button', ['label' => 'Clear', 'type' => 'ghost', 'href' => route('accounting.ledger')])
                @endif
            </div>
        </form>
    </div>

    @if($data)
        {{-- Account Summary --}}
        @php $typeColors = \App\Models\ChartOfAccount::typeColors(); @endphp
        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3 mb-2">
                <h3 class="text-lg font-bold text-gray-900">{{ $data['account']->code }} — {{ $data['account']->name }}</h3>
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $typeColors[$data['account']->type] ?? '' }}">
                    {{ ucfirst($data['account']->type) }}
                </span>
            </div>
            <p class="text-sm text-gray-500">
                Closing Balance:
                <span class="font-bold text-gray-900">{{ number_format($data['closing_balance'], 2) }}</span>
            </p>
        </div>

        {{-- Ledger Table --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Credit</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($data['entries'] as $entry)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-700">
                                    {{ \Carbon\Carbon::parse($entry->date)->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-3 text-sm font-medium text-primary-700">
                                    {{ $entry->reference }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-700 max-w-xs truncate">
                                    {{ $entry->description }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-right text-sm {{ $entry->debit > 0 ? 'font-semibold text-gray-900' : 'text-gray-400' }}">
                                    {{ $entry->debit > 0 ? number_format($entry->debit, 2) : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-right text-sm {{ $entry->credit > 0 ? 'font-semibold text-gray-900' : 'text-gray-400' }}">
                                    {{ $entry->credit > 0 ? number_format($entry->credit, 2) : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-bold {{ $entry->balance >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                                    {{ number_format($entry->balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">
                                    No transactions found for this account in the selected period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($data['entries']->count())
                        <tfoot class="bg-gray-50/50">
                            <tr>
                                <td colspan="5" class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Closing Balance</td>
                                <td class="px-6 py-3 text-right text-sm font-bold {{ $data['closing_balance'] >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                                    {{ number_format($data['closing_balance'], 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @else
        <div class="rounded-2xl bg-white p-12 shadow-sm ring-1 ring-gray-100 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <p class="mt-3 text-sm text-gray-500">Select an account to view its ledger.</p>
        </div>
    @endif
@endsection
