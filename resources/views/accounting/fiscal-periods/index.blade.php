@extends('layouts.app')

@section('title', __('messages.fiscal_periods_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.fiscal_periods_title') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.fiscal_periods_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.fiscal_periods_subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Generate Year --}}
            <form method="POST" action="{{ route('accounting.fiscal-periods.generate-year') }}" class="flex items-center gap-2" x-data>
                @csrf
                <select name="year" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                    @for($y = now()->year - 1; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}" @selected($y == now()->year + 1)>{{ $y }}</option>
                    @endfor
                </select>
                @include('components.button', ['label' => __('messages.generate_year'), 'type' => 'ghost', 'buttonType' => 'submit'])
            </form>
            @include('components.button', ['label' => __('messages.create_period'), 'type' => 'primary', 'href' => route('accounting.fiscal-periods.create')])
        </div>
    </div>
@endsection

@section('content')
    {{-- Tab Navigation: COA & Fiscal Periods --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6">
            <a href="{{ route('accounting.coa.index') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition">{{ __('messages.chart_of_accounts') }}</a>
            <a href="{{ route('accounting.fiscal-periods.index') }}" class="border-primary-500 text-primary-600 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition">{{ __('messages.fiscal_periods_title') }}</a>
        </nav>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_periods') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $periods->count() }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-green-200">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.open_periods') }}</p>
            <p class="mt-1 text-2xl font-bold text-green-600">{{ $periods->where('status', 'open')->count() }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.closed_periods') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-600">{{ $periods->where('status', 'closed')->count() }}</p>
        </div>
    </div>

    {{-- Periods Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.period_name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.start_date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.end_date') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.closed_by') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.closed_at') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($periods as $period)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $period->name }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $period->start_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $period->end_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-center">
                                @if($period->isOpen())
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/10">
                                        {{ __('messages.status_open') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                        {{ __('messages.status_closed') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $period->closedByUser->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $period->closed_at ? $period->closed_at->format('d/m/Y H:i') : '-' }}</td>
                            <td class="px-6 py-3 text-center">
                                @if($period->isOpen())
                                    <form method="POST" action="{{ route('accounting.fiscal-periods.close', $period) }}" class="inline" x-data
                                          @submit.prevent="if(confirm('{{ __('messages.confirm_close_period') }}')) $el.submit()">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100 ring-1 ring-inset ring-amber-600/10 transition">
                                            {{ __('messages.close_period') }}
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('accounting.fiscal-periods.reopen', $period) }}" class="inline" x-data
                                          @submit.prevent="if(confirm('{{ __('messages.confirm_reopen_period') }}')) $el.submit()">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 ring-1 ring-inset ring-blue-600/10 transition">
                                            {{ __('messages.reopen_period') }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-400">{{ __('messages.no_fiscal_periods') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
