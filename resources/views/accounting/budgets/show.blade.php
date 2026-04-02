@extends('layouts.app')

@section('title', $budget->name . ' - ' . __('messages.budget_vs_actual'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.budgets.index') }}" class="hover:text-gray-700">{{ __('messages.budgets_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $budget->name }}</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $budget->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.fiscal_year') }}: {{ $budget->fiscal_year }} &mdash;
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $budget->status === 'approved' ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">
                    {{ ucfirst($budget->status) }}
                </span>
            </p>
        </div>
        @if($budget->isDraft())
            <form method="POST" action="{{ route('accounting.budgets.approve', $budget) }}">
                @csrf
                @include('components.button', ['label' => __('messages.approve'), 'type' => 'primary', 'buttonType' => 'submit'])
            </form>
        @endif
    </div>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_budget') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-700">{{ format_currency($comparison['totals']['total_budget'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_actual') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-700">{{ format_currency($comparison['totals']['total_actual'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.total_variance') }}</p>
            @php $variance = ($comparison['totals']['total_budget'] ?? 0) - ($comparison['totals']['total_actual'] ?? 0); @endphp
            <p class="mt-1 text-2xl font-bold {{ $variance >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ format_currency($variance) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('messages.utilization') }}</p>
            @php $pct = ($comparison['totals']['total_budget'] ?? 0) > 0 ? (($comparison['totals']['total_actual'] ?? 0) / ($comparison['totals']['total_budget'] ?? 1)) * 100 : 0; @endphp
            <p class="mt-1 text-2xl font-bold {{ $pct <= 100 ? 'text-blue-700' : 'text-red-600' }}">{{ number_format($pct, 1) }}%</p>
        </div>
    </div>

    {{-- Budget vs Actual Detail --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
            <h3 class="text-base font-semibold text-blue-900">{{ __('messages.budget_vs_actual') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3 sticky left-0 bg-white">{{ __('messages.account') }}</th>
                        @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $i => $m)
                            <th class="px-2 py-3 text-right" colspan="2">{{ $m }}</th>
                        @endforeach
                        <th class="px-3 py-3 text-right">{{ __('messages.annual_budget') }}</th>
                        <th class="px-3 py-3 text-right">{{ __('messages.annual_actual') }}</th>
                        <th class="px-3 py-3 text-right">{{ __('messages.variance') }}</th>
                    </tr>
                    <tr class="border-b border-gray-100 text-[10px] text-gray-400 uppercase">
                        <th class="px-4 py-1 sticky left-0 bg-white"></th>
                        @for($i = 0; $i < 12; $i++)
                            <th class="px-1 py-1 text-right">Bgt</th>
                            <th class="px-1 py-1 text-right">Act</th>
                        @endfor
                        <th class="px-3 py-1"></th>
                        <th class="px-3 py-1"></th>
                        <th class="px-3 py-1"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($comparison['lines'] ?? [] as $line)
                        @php
                            $annBudget = collect($line['months'])->sum('budget');
                            $annActual = collect($line['months'])->sum('actual');
                            $annVariance = $annBudget - $annActual;
                        @endphp
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-2 text-xs font-medium text-gray-900 sticky left-0 bg-white whitespace-nowrap">
                                {{ $line['account_code'] }} - {{ $line['account_name'] }}
                            </td>
                            @foreach($line['months'] as $month)
                                <td class="px-1 py-2 text-xs text-right text-gray-600">{{ number_format($month['budget'], 0) }}</td>
                                <td class="px-1 py-2 text-xs text-right {{ $month['actual'] <= $month['budget'] ? 'text-green-700' : 'text-red-600' }}">
                                    {{ number_format($month['actual'], 0) }}
                                </td>
                            @endforeach
                            <td class="px-3 py-2 text-xs text-right font-semibold text-gray-700">{{ format_currency($annBudget) }}</td>
                            <td class="px-3 py-2 text-xs text-right font-semibold text-gray-700">{{ format_currency($annActual) }}</td>
                            <td class="px-3 py-2 text-xs text-right font-semibold {{ $annVariance >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                {{ format_currency($annVariance) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
