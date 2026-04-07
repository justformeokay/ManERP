@extends('layouts.app')

@section('title', __('messages.payslip') . ' — ' . $payslip->employee->name)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('hr.payroll.index') }}" class="hover:text-gray-700">{{ __('messages.payroll') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('hr.payroll.show', $payslip->payrollPeriod) }}" class="hover:text-gray-700">{{ $payslip->payrollPeriod->period_label }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $payslip->employee->name }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.payslip') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $payslip->employee->name }} · {{ $payslip->payrollPeriod->period_label }}</p>
    </div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    {{-- Employee Info --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">{{ __('messages.name') }}:</span> <span class="font-medium text-gray-900">{{ $payslip->employee->name }}</span></div>
            <div><span class="text-gray-500">NIK:</span> <span class="font-medium text-gray-900">{{ $payslip->employee->nik }}</span></div>
            <div><span class="text-gray-500">{{ __('messages.department') }}:</span> <span class="font-medium text-gray-900">{{ $payslip->employee->department ?? '—' }}</span></div>
            <div><span class="text-gray-500">PTKP:</span> <span class="font-medium text-gray-900">{{ $payslip->employee->ptkp_status }} (TER {{ $payslip->employee->ter_category }})</span></div>
        </div>
    </div>

    {{-- Earnings --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.earnings') }}</h3>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-50">
                @foreach($payslip->items->where('type', 'earning') as $item)
                    <tr>
                        <td class="py-2 text-gray-700">{{ $item->label }}</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ format_currency($item->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 font-semibold">
                    <td class="py-3 text-gray-900">{{ __('messages.gross_salary') }}</td>
                    <td class="py-3 text-right text-gray-900">{{ format_currency($payslip->gross_salary) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Deductions --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.deductions') }}</h3>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-50">
                @foreach($payslip->items->where('type', 'deduction') as $item)
                    <tr>
                        <td class="py-2 text-gray-700">{{ $item->label }}</td>
                        <td class="py-2 text-right font-medium text-red-600">{{ format_currency($item->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 font-semibold">
                    <td class="py-3 text-gray-900">{{ __('messages.total_deductions') }}</td>
                    <td class="py-3 text-right text-red-700">{{ format_currency($payslip->total_deductions) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- BPJS Company Portion (informational) --}}
    <div class="rounded-2xl bg-blue-50 p-6 shadow-sm ring-1 ring-blue-100">
        <h3 class="text-sm font-semibold text-blue-800 uppercase tracking-wider mb-3">BPJS {{ __('messages.company_portion') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
            <div><span class="text-blue-600">JHT 3.7%</span><p class="font-medium text-blue-900">{{ format_currency($payslip->bpjs_jht_company) }}</p></div>
            <div><span class="text-blue-600">JKK</span><p class="font-medium text-blue-900">{{ format_currency($payslip->bpjs_jkk_company) }}</p></div>
            <div><span class="text-blue-600">JKM</span><p class="font-medium text-blue-900">{{ format_currency($payslip->bpjs_jkm_company) }}</p></div>
            <div><span class="text-blue-600">JP 2%</span><p class="font-medium text-blue-900">{{ format_currency($payslip->bpjs_jp_company) }}</p></div>
            <div><span class="text-blue-600">Kes 4%</span><p class="font-medium text-blue-900">{{ format_currency($payslip->bpjs_kes_company) }}</p></div>
        </div>
        <p class="mt-3 text-sm font-semibold text-blue-900">Total: {{ format_currency($payslip->total_bpjs_company) }}</p>
    </div>

    {{-- Net Salary --}}
    <div class="rounded-2xl bg-green-50 p-6 shadow-sm ring-1 ring-green-100">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-green-900">{{ __('messages.net_salary') }} (Take Home Pay)</h3>
            <p class="text-2xl font-bold text-green-800">{{ format_currency($payslip->net_salary) }}</p>
        </div>
    </div>
</div>
@endsection
