@extends('layouts.app')

@section('title', __('messages.annual_tax_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.tax.spt-masa-ppn') }}" class="hover:text-gray-700">{{ __('messages.spt_masa_ppn_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.annual_tax_title') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.annual_tax_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.annual_tax_subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @include('components.button', ['label' => __('messages.spt_masa_ppn_title'), 'type' => 'ghost', 'href' => route('accounting.tax.spt-masa-ppn')])
        </div>
    </div>
@endsection

@section('content')
    {{-- Year Selector --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
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

    {{-- Annual Totals --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_dpp_keluaran') }}</p>
            <p class="mt-1 text-xl font-bold text-gray-900">{{ format_currency($summary['totals']['ppn_keluaran_dpp'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-red-200">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_ppn_keluaran') }}</p>
            <p class="mt-1 text-xl font-bold text-red-600">{{ format_currency($summary['totals']['ppn_keluaran'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-green-200">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_ppn_masukan') }}</p>
            <p class="mt-1 text-xl font-bold text-green-600">{{ format_currency($summary['totals']['ppn_masukan'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-amber-200">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.net_ppn_annual') }}</p>
            <p class="mt-1 text-xl font-bold text-amber-600">{{ format_currency($summary['totals']['net_ppn'] ?? 0) }}</p>
        </div>
    </div>

    {{-- Monthly Breakdown --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900">{{ __('messages.monthly_breakdown') }} — {{ $year }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.month') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">DPP {{ __('messages.ppn_keluaran') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">PPN {{ __('messages.ppn_keluaran') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">DPP {{ __('messages.ppn_masukan') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">PPN {{ __('messages.ppn_masukan') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.net_ppn') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-500">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($summary['months'] as $data)
                        @php $net = ($data['ppn_keluaran']['total_ppn'] ?? 0) - ($data['ppn_masukan']['total_ppn'] ?? 0); @endphp
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ date('F', mktime(0, 0, 0, $data['month'], 1)) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-700">{{ format_currency($data['ppn_keluaran']['total_dpp'] ?? 0) }}</td>
                            <td class="px-6 py-3 text-sm text-right font-medium text-red-600">{{ format_currency($data['ppn_keluaran']['total_ppn'] ?? 0) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-700">{{ format_currency($data['ppn_masukan']['total_dpp'] ?? 0) }}</td>
                            <td class="px-6 py-3 text-sm text-right font-medium text-green-600">{{ format_currency($data['ppn_masukan']['total_ppn'] ?? 0) }}</td>
                            <td class="px-6 py-3 text-sm text-right font-semibold {{ $net > 0 ? 'text-amber-600' : ($net < 0 ? 'text-blue-600' : 'text-gray-400') }}">
                                {{ format_currency(abs($net)) }}
                                @if($net > 0) <span class="text-xs">(KB)</span> @elseif($net < 0) <span class="text-xs">(LB)</span> @endif
                            </td>
                            <td class="px-6 py-3 text-center">
                                @if($net > 0)
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/10">KB</span>
                                @elseif($net < 0)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/10">LB</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-500/10">Nihil</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center">
                                <a href="{{ route('accounting.tax.spt-masa-ppn', ['year' => $year, 'month' => $data['month']]) }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">{{ __('messages.view_detail') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-semibold">
                        <td class="px-6 py-3 text-sm text-gray-900">Total</td>
                        <td class="px-6 py-3 text-sm text-right text-gray-900">{{ format_currency($summary['totals']['ppn_keluaran_dpp'] ?? 0) }}</td>
                        <td class="px-6 py-3 text-sm text-right text-red-700">{{ format_currency($summary['totals']['ppn_keluaran'] ?? 0) }}</td>
                        <td class="px-6 py-3 text-sm text-right text-gray-900">{{ format_currency($summary['totals']['ppn_masukan_dpp'] ?? 0) }}</td>
                        <td class="px-6 py-3 text-sm text-right text-green-700">{{ format_currency($summary['totals']['ppn_masukan'] ?? 0) }}</td>
                        <td class="px-6 py-3 text-sm text-right text-amber-700">{{ format_currency($summary['totals']['net_ppn'] ?? 0) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
