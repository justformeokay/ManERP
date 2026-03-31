@extends('layouts.app')

@section('title', 'Trial Balance')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Trial Balance</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Trial Balance</h1>
        <p class="mt-1 text-sm text-gray-500">Summary of all account debits and credits</p>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
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
                @include('components.button', ['label' => 'Generate', 'type' => 'primary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['from', 'to']))
                    @include('components.button', ['label' => 'All Time', 'type' => 'ghost', 'href' => route('accounting.trial-balance')])
                @endif
            </div>
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
                    <p class="text-sm font-medium text-green-800">Trial balance is <strong>balanced</strong>. Total Debits = Total Credits = {{ number_format($data['grand_debit'], 2) }}</p>
                </div>
            </div>
        @else
            <div class="rounded-2xl bg-red-50 p-4 ring-1 ring-red-200">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium text-red-800">Trial balance is <strong>not balanced</strong>. Difference: {{ number_format(abs($data['grand_debit'] - $data['grand_credit']), 2) }}</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Account Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total Debit</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php $typeColors = \App\Models\ChartOfAccount::typeColors(); @endphp
                    @forelse($data['accounts'] as $row)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-3">
                                <span class="text-sm font-mono font-semibold text-gray-900">{{ $row->code }}</span>
                            </td>
                            <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $row->name }}</td>
                            <td class="whitespace-nowrap px-6 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $typeColors[$row->type] ?? '' }}">
                                    {{ ucfirst($row->type) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm {{ $row->total_debit > 0 ? 'font-semibold text-gray-900' : 'text-gray-400' }}">
                                {{ $row->total_debit > 0 ? number_format($row->total_debit, 2) : '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm {{ $row->total_credit > 0 ? 'font-semibold text-gray-900' : 'text-gray-400' }}">
                                {{ $row->total_credit > 0 ? number_format($row->total_credit, 2) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <p class="text-sm text-gray-500">No journal entries found for the selected period.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data['accounts']->count())
                    <tfoot class="bg-gray-50/50 border-t-2 border-gray-200">
                        <tr>
                            <td colspan="3" class="px-6 py-3 text-right text-sm font-bold text-gray-900">Grand Total</td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">{{ number_format($data['grand_debit'], 2) }}</td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">{{ number_format($data['grand_credit'], 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
