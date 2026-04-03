@extends('layouts.app')

@section('title', __('messages.payroll_dashboard'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('hr.payroll.index') }}" class="hover:text-gray-700">{{ __('messages.payroll') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.payroll_dashboard') }}</span>
@endsection

@section('page-header')
    <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.payroll_dashboard') }} — {{ $currentYear }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('messages.workforce_cost_summary') }}</p>
@endsection

@section('content')
<div class="space-y-6">
    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.active_employees') }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $employeeCount }}</p>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.ytd_gross') }}</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">Rp {{ number_format($monthlySummary->sum('total_gross'), 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.ytd_net') }}</p>
            <p class="mt-2 text-2xl font-bold text-green-700">Rp {{ number_format($monthlySummary->sum('total_net'), 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Monthly Chart (Table fallback) --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.monthly_payroll_trend') }} {{ $currentYear }}</h3>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.month') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.total_gross') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.total_net') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500">{{ __('messages.gross_vs_net') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($monthlySummary as $m)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $m['label'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">Rp {{ number_format($m['total_gross'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-700 font-medium">Rp {{ number_format($m['total_net'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @php $colors = \App\Models\PayrollPeriod::statusColors(); @endphp
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $colors[$m['status']] ?? '' }}">{{ ucfirst($m['status']) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($m['total_gross'] > 0)
                                @php $netPct = round(($m['total_net'] / $m['total_gross']) * 100); @endphp
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-100 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $netPct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-10 text-right">{{ $netPct }}%</span>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">{{ __('messages.no_payroll_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
