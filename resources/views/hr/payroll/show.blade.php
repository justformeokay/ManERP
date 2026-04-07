@extends('layouts.app')

@section('title', $period->period_label)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('hr.payroll.index') }}" class="hover:text-gray-700">{{ __('messages.payroll') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $period->period_label }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.payroll') }}: {{ $period->period_label }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                @php $colors = \App\Models\PayrollPeriod::statusColors(); @endphp
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $colors[$period->status] ?? '' }}">
                    {{ ucfirst($period->status) }}
                </span>
                · {{ $summary['employee_count'] }} {{ __('messages.employees') }}
            </p>
        </div>
        <div class="flex gap-2">
            @if($period->canTransitionTo('approved'))
                <form method="POST" action="{{ route('hr.payroll.approve', $period) }}">
                    @csrf
                    @include('components.button', ['label' => __('messages.approve'), 'type' => 'secondary', 'buttonType' => 'submit'])
                </form>
            @endif
            @if($period->canTransitionTo('posted'))
                <form method="POST" action="{{ route('hr.payroll.post', $period) }}" onsubmit="return confirm('{{ __('messages.confirm_post_payroll') }}')">
                    @csrf
                    @include('components.button', ['label' => __('messages.post_to_accounting'), 'type' => 'primary', 'buttonType' => 'submit'])
                </form>
            @endif
        </div>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.total_gross') }}</p>
            <p class="mt-2 text-xl font-bold text-gray-900">{{ format_currency($summary['total_gross']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.total_net') }}</p>
            <p class="mt-2 text-xl font-bold text-green-700">{{ format_currency($summary['total_net']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">PPh 21</p>
            <p class="mt-2 text-xl font-bold text-amber-700">{{ format_currency($summary['total_pph21']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">BPJS ({{ __('messages.company') }})</p>
            <p class="mt-2 text-xl font-bold text-blue-700">{{ format_currency($summary['total_bpjs_co']) }}</p>
        </div>
    </div>

    {{-- Payslips Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.employee') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.gross_salary') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">BPJS ({{ __('messages.employee_short') }})</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">PPh 21</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.other_deductions') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.net_salary') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($period->payslips->sortBy('employee.name') as $slip)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $slip->employee->name }}</p>
                                <p class="text-xs text-gray-500">{{ $slip->employee->nik }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900">{{ format_currency($slip->gross_salary) }}</td>
                            <td class="px-6 py-4 text-sm text-right text-red-600">{{ format_currency($slip->total_bpjs_employee) }}</td>
                            <td class="px-6 py-4 text-sm text-right text-amber-700">{{ format_currency($slip->pph21_amount) }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-600">{{ format_currency((float)$slip->loan_deduction + (float)$slip->absence_deduction + (float)$slip->other_deductions) }}</td>
                            <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900">{{ format_currency($slip->net_salary) }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('hr.payroll.payslip', $slip) }}" class="text-sm text-primary-600 hover:text-primary-800">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50/50">
                    <tr class="font-semibold">
                        <td class="px-6 py-3 text-sm text-gray-900">Total</td>
                        <td class="px-6 py-3 text-sm text-right text-gray-900">{{ format_currency($summary['total_gross']) }}</td>
                        <td class="px-6 py-3 text-sm text-right text-red-600">{{ format_currency($summary['total_bpjs_emp']) }}</td>
                        <td class="px-6 py-3 text-sm text-right text-amber-700">{{ format_currency($summary['total_pph21']) }}</td>
                        <td class="px-6 py-3 text-sm text-right text-gray-600">—</td>
                        <td class="px-6 py-3 text-sm text-right text-gray-900">{{ format_currency($summary['total_net']) }}</td>
                        <td class="px-6 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
