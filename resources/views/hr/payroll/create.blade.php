@extends('layouts.app')

@section('title', __('messages.generate_payroll'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('hr.payroll.index') }}" class="hover:text-gray-700">{{ __('messages.payroll') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.generate') }}</span>
@endsection

@section('page-header')
    <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.generate_payroll') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('messages.generate_payroll_desc') }}</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('hr.payroll.store') }}">
        @csrf
        <div class="space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.month') }} <span class="text-red-500">*</span></label>
                        <select name="month" required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                            @php $months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; @endphp
                            @foreach($months as $i => $m)
                                <option value="{{ $i + 1 }}" @selected(old('month', now()->month) == $i + 1)>{{ $m }}</option>
                            @endforeach
                        </select>
                        @error('month') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.year') }} <span class="text-red-500">*</span></label>
                        <input type="number" name="year" value="{{ old('year', now()->year) }}" min="2020" max="2099" required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        @error('year') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <div class="rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-700 w-full">
                            <span class="font-semibold">{{ $employeeCount }}</span> {{ __('messages.active_employees') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 justify-end">
                @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('hr.payroll.index')])
                @include('components.button', ['label' => __('messages.generate_payroll'), 'type' => 'primary', 'buttonType' => 'submit'])
            </div>
        </div>
    </form>
@endsection
