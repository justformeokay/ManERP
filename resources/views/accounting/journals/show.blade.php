@extends('layouts.app')

@section('title', $journal->reference)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.journals.index') }}" class="hover:text-gray-700">Journal Entries</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $journal->reference }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">{{ $journal->reference }}</h1>
            @if($journal->is_balanced)
                <span class="inline-flex rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-300">Balanced</span>
            @else
                <span class="inline-flex rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-300">Unbalanced</span>
            @endif
        </div>
        @include('components.button', ['label' => '← Back', 'type' => 'ghost', 'href' => route('accounting.journals.index')])
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left column: Entry Info --}}
        <div class="space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Entry Information</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Reference</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $journal->reference }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Date</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $journal->date->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Created By</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $journal->creator->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Created At</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $journal->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-2">Description</h3>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $journal->description }}</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Totals</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Total Debit</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ number_format($journal->total_debit, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Total Credit</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ number_format($journal->total_credit, 2) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3">
                        <dt class="text-sm font-semibold text-gray-900">Difference</dt>
                        <dd class="text-sm font-bold {{ $journal->is_balanced ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format(abs($journal->total_debit - $journal->total_credit), 2) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Right column: Line Items --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Journal Lines</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Account</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Debit</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Credit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($journal->items as $i => $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-6 py-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $item->account->code }} — {{ $item->account->name }}</p>
                                        @php $typeColors = \App\Models\ChartOfAccount::typeColors(); @endphp
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium ring-1 {{ $typeColors[$item->account->type] ?? '' }}">
                                            {{ ucfirst($item->account->type) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm {{ $item->debit > 0 ? 'font-semibold text-gray-900' : 'text-gray-400' }}">
                                        {{ $item->debit > 0 ? number_format($item->debit, 2) : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm {{ $item->credit > 0 ? 'font-semibold text-gray-900' : 'text-gray-400' }}">
                                        {{ $item->credit > 0 ? number_format($item->credit, 2) : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50/50">
                            <tr>
                                <td colspan="2" class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Total</td>
                                <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-bold text-gray-900">{{ number_format($journal->total_debit, 2) }}</td>
                                <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-bold text-gray-900">{{ number_format($journal->total_credit, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
