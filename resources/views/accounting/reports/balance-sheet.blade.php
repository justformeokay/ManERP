@extends('layouts.app')

@section('title', 'Balance Sheet')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Balance Sheet</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Balance Sheet</h1>
        <p class="mt-1 text-sm text-gray-500">Statement of Financial Position</p>
    </div>
@endsection

@section('content')
    {{-- Date Filter --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="date" class="block text-xs font-medium text-gray-500 mb-1">As of Date</label>
                <input type="date" name="date" id="date" value="{{ $data['date'] }}"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            @include('components.button', ['label' => 'Generate', 'type' => 'primary', 'buttonType' => 'submit'])
        </form>
    </div>

    {{-- Balance Status --}}
    <div class="mb-6">
        @if($data['is_balanced'])
            <div class="rounded-2xl bg-green-50 p-4 ring-1 ring-green-200">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium text-green-800">Balance sheet is <strong>balanced</strong>. Assets = Liabilities + Equity = {{ number_format($data['total_assets'], 2) }}</p>
                </div>
            </div>
        @else
            <div class="rounded-2xl bg-red-50 p-4 ring-1 ring-red-200">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium text-red-800">Balance sheet is <strong>not balanced</strong>. Difference: {{ number_format(abs($data['total_assets'] - $data['total_liabilities_equity']), 2) }}</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Assets</p>
            <p class="mt-1 text-2xl font-bold text-blue-700">{{ number_format($data['total_assets'], 2) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Liabilities</p>
            <p class="mt-1 text-2xl font-bold text-red-600">{{ number_format($data['total_liabilities'], 2) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Equity</p>
            <p class="mt-1 text-2xl font-bold text-purple-700">{{ number_format($data['total_equity'], 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Assets --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-blue-50/50">
                <h3 class="text-base font-semibold text-blue-900">Assets</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Account</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($data['assets'] as $account)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-3 text-sm font-mono font-semibold text-gray-900">{{ $account->code }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $account->name }}</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($account->balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-400">No asset accounts with balance.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-blue-50/50">
                        <tr>
                            <td colspan="2" class="px-6 py-3 text-right text-sm font-bold text-blue-900">Total Assets</td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-blue-900">{{ number_format($data['total_assets'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Liabilities & Equity --}}
        <div class="space-y-6">
            {{-- Liabilities --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-red-50/50">
                    <h3 class="text-base font-semibold text-red-900">Liabilities</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Account</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($data['liabilities'] as $account)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3 text-sm font-mono font-semibold text-gray-900">{{ $account->code }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $account->name }}</td>
                                    <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($account->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-400">No liability accounts with balance.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-red-50/50">
                            <tr>
                                <td colspan="2" class="px-6 py-3 text-right text-sm font-bold text-red-900">Total Liabilities</td>
                                <td class="px-6 py-3 text-right text-sm font-bold text-red-900">{{ number_format($data['total_liabilities'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Equity --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-purple-50/50">
                    <h3 class="text-base font-semibold text-purple-900">Equity</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Account</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($data['equity'] as $account)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3 text-sm font-mono font-semibold text-gray-900">{{ $account->code }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $account->name }}</td>
                                    <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($account->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-400">No equity accounts with balance.</td></tr>
                            @endforelse
                            {{-- Retained Earnings (auto-calculated) --}}
                            <tr class="bg-purple-50/30">
                                <td class="px-6 py-3 text-sm font-mono text-gray-500">—</td>
                                <td class="px-6 py-3 text-sm font-medium text-purple-800 italic">Retained Earnings (Revenue − Expense)</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold {{ $data['retained_earnings'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                    {{ number_format($data['retained_earnings'], 2) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-purple-50/50">
                            <tr>
                                <td colspan="2" class="px-6 py-3 text-right text-sm font-bold text-purple-900">Total Equity</td>
                                <td class="px-6 py-3 text-right text-sm font-bold text-purple-900">{{ number_format($data['total_equity'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Grand Totals --}}
    <div class="mt-6 rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <table class="min-w-full">
            <tbody>
                <tr class="border-b border-gray-100">
                    <td class="px-6 py-4 text-sm font-bold text-gray-900">Total Assets</td>
                    <td class="px-6 py-4 text-right text-sm font-bold text-blue-700">{{ number_format($data['total_assets'], 2) }}</td>
                </tr>
                <tr class="border-b border-gray-100">
                    <td class="px-6 py-4 text-sm font-bold text-gray-900">Total Liabilities + Equity</td>
                    <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ number_format($data['total_liabilities_equity'], 2) }}</td>
                </tr>
                <tr class="{{ $data['is_balanced'] ? 'bg-green-50' : 'bg-red-50' }}">
                    <td class="px-6 py-4 text-sm font-bold {{ $data['is_balanced'] ? 'text-green-800' : 'text-red-800' }}">
                        Difference
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-bold {{ $data['is_balanced'] ? 'text-green-800' : 'text-red-800' }}">
                        {{ number_format(abs($data['total_assets'] - $data['total_liabilities_equity']), 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="mt-4 text-xs text-gray-400 text-right">As of {{ \Carbon\Carbon::parse($data['date'])->format('F d, Y') }}</p>
@endsection
