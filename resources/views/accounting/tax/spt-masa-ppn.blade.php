@extends('layouts.app')

@section('title', __('messages.spt_masa_ppn_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.spt_masa_ppn_title') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.spt_masa_ppn_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.spt_masa_ppn_subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @include('components.button', ['label' => __('messages.annual_summary'), 'type' => 'ghost', 'href' => route('accounting.tax.annual', ['year' => $year])])
            @include('components.button', ['label' => __('messages.ppn_calculator'), 'type' => 'ghost', 'href' => route('accounting.tax.calculator')])
        </div>
    </div>
@endsection

@section('content')
    {{-- Period Selector --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.month') }}</label>
                <select name="month" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($month == $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.year') }}</label>
                <select name="year" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            @include('components.button', ['label' => __('messages.generate'), 'type' => 'primary', 'buttonType' => 'submit'])
        </form>
    </div>

    {{-- Period Label --}}
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $spt['period_label'] }}</h2>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-red-200 border-l-4 border-red-500">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.ppn_keluaran') }}</p>
            <p class="mt-1 text-2xl font-bold text-red-600">{{ format_currency($spt['ppn_keluaran']['total_ppn']) }}</p>
            <p class="mt-1 text-xs text-gray-500">DPP: {{ format_currency($spt['ppn_keluaran']['total_dpp']) }} &middot; {{ $spt['ppn_keluaran']['count'] }} {{ __('messages.invoices') }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-green-200 border-l-4 border-green-500">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.ppn_masukan') }}</p>
            <p class="mt-1 text-2xl font-bold text-green-600">{{ format_currency($spt['ppn_masukan']['total_ppn']) }}</p>
            <p class="mt-1 text-xs text-gray-500">DPP: {{ format_currency($spt['ppn_masukan']['total_dpp']) }} &middot; {{ $spt['ppn_masukan']['count'] }} {{ __('messages.bills') }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 {{ $spt['ppn_kurang_bayar'] > 0 ? 'ring-amber-200 border-l-4 border-amber-500' : ($spt['ppn_kurang_bayar'] < 0 ? 'ring-blue-200 border-l-4 border-blue-500' : 'ring-gray-200 border-l-4 border-gray-400') }}">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
                @if($spt['status'] === 'kurang_bayar')
                    {{ __('messages.ppn_kurang_bayar') }}
                @elseif($spt['status'] === 'lebih_bayar')
                    {{ __('messages.ppn_lebih_bayar') }}
                @else
                    {{ __('messages.ppn_nihil') }}
                @endif
            </p>
            <p class="mt-1 text-2xl font-bold {{ $spt['ppn_kurang_bayar'] > 0 ? 'text-amber-600' : ($spt['ppn_kurang_bayar'] < 0 ? 'text-blue-600' : 'text-gray-600') }}">
                {{ format_currency(abs($spt['ppn_kurang_bayar'])) }}
            </p>
        </div>
    </div>

    {{-- PPN Keluaran Detail --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden mb-6">
        <div class="bg-red-50 px-6 py-4 border-b border-red-100">
            <h3 class="text-base font-semibold text-red-900">{{ __('messages.ppn_keluaran_detail') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.invoice_number') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.client') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.faktur_pajak') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">DPP</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">PPN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($ppnKeluaranDetail as $inv)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $inv->invoice_number }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $inv->invoice_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $inv->client->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $inv->faktur_pajak_number ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-900">{{ format_currency($inv->dpp) }}</td>
                            <td class="px-6 py-3 text-sm text-right font-medium text-red-600">{{ format_currency($inv->tax_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">{{ __('messages.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- PPN Masukan Detail --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="bg-green-50 px-6 py-4 border-b border-green-100">
            <h3 class="text-base font-semibold text-green-900">{{ __('messages.ppn_masukan_detail') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.bill_number') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.supplier') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.faktur_pajak') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">DPP</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">PPN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($ppnMasukanDetail as $bill)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $bill->bill_number }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $bill->bill_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $bill->supplier->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $bill->faktur_pajak_number ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-900">{{ format_currency($bill->dpp) }}</td>
                            <td class="px-6 py-3 text-sm text-right font-medium text-green-600">{{ format_currency($bill->tax_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">{{ __('messages.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
