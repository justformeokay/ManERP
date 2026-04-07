@extends('layouts.app')

@section('title', $employee->name)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('hr.employees.index') }}" class="hover:text-gray-700">{{ __('messages.employees') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $employee->name }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $employee->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $employee->nik }} · {{ $employee->position ?? '—' }} · {{ $employee->department ?? '—' }}</p>
        </div>
        <div class="flex gap-2">
            @include('components.button', ['label' => __('messages.edit'), 'type' => 'secondary', 'href' => route('hr.employees.edit', $employee)])
        </div>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Employee Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Personal Info --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('messages.personal_data') }}</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">NIK</dt><dd class="font-medium text-gray-900">{{ $employee->nik }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.join_date') }}</dt><dd class="font-medium text-gray-900">{{ $employee->join_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $employee->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-700' }}">{{ ucfirst($employee->status) }}</span></dd></div>
            </dl>
        </div>
        {{-- Tax & BPJS --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('messages.tax_bpjs_info') }}</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">NPWP</dt><dd class="font-medium text-gray-900">{{ $employee->npwp ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">PTKP</dt><dd class="font-medium text-gray-900">{{ $employee->ptkp_status }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">TER</dt><dd class="font-medium text-gray-900">Kategori {{ $employee->ter_category }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">BPJS TK</dt><dd class="font-medium text-gray-900">{{ $employee->bpjs_tk_number ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">BPJS Kes</dt><dd class="font-medium text-gray-900">{{ $employee->bpjs_kes_number ?? '—' }}</dd></div>
            </dl>
        </div>
        {{-- Bank Info --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('messages.bank_info') }}</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.bank_name') }}</dt><dd class="font-medium text-gray-900">{{ $employee->bank?->name ?? $employee->bank_name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.account_number') }}</dt><dd class="font-medium text-gray-900">{{ $employee->bank_account_number ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.account_name') }}</dt><dd class="font-medium text-gray-900">{{ $employee->bank_account_name ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>

    {{-- Salary Structure --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('messages.salary_structure') }}</h3>
            <form method="POST" action="{{ route('hr.salary-structures.store') }}" class="contents" id="salaryForm">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                <button type="button" onclick="document.getElementById('salaryModal').classList.remove('hidden')"
                    class="inline-flex items-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                    {{ __('messages.add_salary') }}
                </button>
            </form>
        </div>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.effective_date') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.basic_salary') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.fixed_allowance') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.meal_allowance') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.transport_allowance') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.overtime_rate') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($employee->salaryStructures as $sal)
                    <tr class="{{ $sal->is_active ? 'bg-green-50/30' : '' }}">
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $sal->effective_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900 font-medium">{{ format_currency($sal->basic_salary) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ format_currency($sal->fixed_allowance) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ format_currency($sal->meal_allowance) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ format_currency($sal->transport_allowance) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ format_currency($sal->overtime_rate) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($sal->is_active)
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Active</span>
                            @else
                                <span class="text-xs text-gray-400">Inactive</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">{{ __('messages.no_salary_structure') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Salary Structure Modal --}}
    <div id="salaryModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.add_salary') }}</h3>
            <form method="POST" action="{{ route('hr.salary-structures.store') }}">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.basic_salary') }} <span class="text-red-500">*</span></label>
                        <input type="text" name="basic_salary" x-currency required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.fixed_allowance') }}</label>
                        <input type="text" name="fixed_allowance" x-currency value="0"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.meal_allowance') }}</label>
                        <input type="text" name="meal_allowance" x-currency value="0"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.transport_allowance') }}</label>
                        <input type="text" name="transport_allowance" x-currency value="0"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.overtime_rate') }}</label>
                        <input type="text" name="overtime_rate" x-currency value="0"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.effective_date') }} <span class="text-red-500">*</span></label>
                        <input type="date" name="effective_date" value="{{ now()->format('Y-m-d') }}" required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="document.getElementById('salaryModal').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">{{ __('messages.cancel') }}</button>
                    <button type="submit"
                        class="inline-flex items-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
