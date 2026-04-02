@extends('layouts.app')

@section('title', __('messages.ar_aging_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.ar_aging_title') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.ar_aging_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.ar_aging_subtitle') }}</p>
    </div>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm font-medium text-gray-500">{{ __('messages.total_outstanding') }}</p>
            <p class="mt-1 text-xl font-bold text-gray-900">{{ format_currency($result['totals']['total']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-green-200 border-l-4 border-green-500">
            <p class="text-sm font-medium text-gray-500">{{ __('messages.current') }}</p>
            <p class="mt-1 text-xl font-bold text-green-600">{{ format_currency($result['totals']['current']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-yellow-200 border-l-4 border-yellow-500">
            <p class="text-sm font-medium text-gray-500">1-30 {{ __('messages.days') }}</p>
            <p class="mt-1 text-xl font-bold text-yellow-600">{{ format_currency($result['totals']['1-30']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-orange-200 border-l-4 border-orange-500">
            <p class="text-sm font-medium text-gray-500">31-60 {{ __('messages.days') }}</p>
            <p class="mt-1 text-xl font-bold text-orange-600">{{ format_currency($result['totals']['31-60']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-red-200 border-l-4 border-red-500">
            <p class="text-sm font-medium text-gray-500">61-90 {{ __('messages.days') }}</p>
            <p class="mt-1 text-xl font-bold text-red-500">{{ format_currency($result['totals']['61-90']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-red-300 border-l-4 border-red-700">
            <p class="text-sm font-medium text-gray-500">90+ {{ __('messages.days') }}</p>
            <p class="mt-1 text-xl font-bold text-red-700">{{ format_currency($result['totals']['90+']) }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <select name="client_id" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_clients') }}</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                @include('components.button', ['label' => __('messages.filter'), 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request('client_id'))
                    @include('components.button', ['label' => __('messages.clear'), 'type' => 'ghost', 'href' => route('accounting.ar-aging')])
                @endif
            </div>
        </form>
    </div>

    {{-- Aging Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.client') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-green-600">{{ __('messages.current') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-yellow-600">1-30</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-orange-600">31-60</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-red-500">61-90</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-red-700">90+</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-900">{{ __('messages.total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($result['report'] as $row)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900">{{ $row['client_name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $row['invoice_count'] }} {{ __('messages.invoices') }}</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm {{ $row['current'] > 0 ? 'text-green-600 font-medium' : 'text-gray-300' }}">
                                {{ format_currency($row['current']) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm {{ $row['1-30'] > 0 ? 'text-yellow-600 font-medium' : 'text-gray-300' }}">
                                {{ format_currency($row['1-30']) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm {{ $row['31-60'] > 0 ? 'text-orange-600 font-medium' : 'text-gray-300' }}">
                                {{ format_currency($row['31-60']) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm {{ $row['61-90'] > 0 ? 'text-red-500 font-medium' : 'text-gray-300' }}">
                                {{ format_currency($row['61-90']) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm {{ $row['90+'] > 0 ? 'text-red-700 font-medium' : 'text-gray-300' }}">
                                {{ format_currency($row['90+']) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-gray-900">
                                {{ format_currency($row['total']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">
                                {{ __('messages.no_outstanding_receivables') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($result['report']) > 0)
                    <tfoot class="bg-gray-50 font-semibold">
                        <tr>
                            <td class="px-6 py-3 text-sm text-gray-900">{{ __('messages.total') }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-green-600">{{ format_currency($result['totals']['current']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-yellow-600">{{ format_currency($result['totals']['1-30']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-orange-600">{{ format_currency($result['totals']['31-60']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-red-500">{{ format_currency($result['totals']['61-90']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-red-700">{{ format_currency($result['totals']['90+']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-bold text-gray-900">{{ format_currency($result['totals']['total']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
