@extends('layouts.app')

@section('title', __('messages.payroll'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.payroll') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.payroll') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.payroll_desc') }}</p>
        </div>
        <div class="flex gap-2">
            @include('components.button', ['label' => __('messages.payroll_dashboard'), 'type' => 'secondary', 'href' => route('hr.payroll.dashboard')])
            @include('components.button', ['label' => __('messages.generate_payroll'), 'type' => 'primary', 'href' => route('hr.payroll.create')])
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <select name="year" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="">{{ __('messages.all_years') }}</option>
                    @for($y = now()->year; $y >= 2020; $y--)
                        <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="flex gap-2">
                @include('components.button', ['label' => __('messages.filter'), 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['year']))
                    @include('components.button', ['label' => __('messages.clear'), 'type' => 'ghost', 'href' => route('hr.payroll.index')])
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.period') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.total_gross') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.total_deductions') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.total_net') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($periods as $period)
                        @php $colors = \App\Models\PayrollPeriod::statusColors(); @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('hr.payroll.show', $period) }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800">
                                    {{ $period->period_label }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $colors[$period->status] ?? '' }}">
                                    {{ ucfirst($period->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">Rp {{ number_format($period->total_gross, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm text-right text-red-600">Rp {{ number_format($period->total_deductions, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900">Rp {{ number_format($period->total_net, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('hr.payroll.show', $period) }}" class="text-sm text-primary-600 hover:text-primary-800">{{ __('messages.view') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('messages.no_payroll_periods') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($periods->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">{{ $periods->links() }}</div>
        @endif
    </div>
@endsection
