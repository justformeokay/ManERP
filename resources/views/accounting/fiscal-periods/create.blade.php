@extends('layouts.app')

@section('title', __('messages.create_period'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.fiscal-periods.index') }}" class="hover:text-gray-700">{{ __('messages.fiscal_periods_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.create_period') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.create_period') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.create_period_subtitle') }}</p>
    </div>
@endsection

@section('content')
    <div class="max-w-xl">
        <form method="POST" action="{{ route('accounting.fiscal-periods.store') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.period_name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                       placeholder="e.g. Januari 2026">
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.start_date') }} <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('start_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.end_date') }} <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" required
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('end_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('accounting.fiscal-periods.index')])
                @include('components.button', ['label' => __('messages.create_period'), 'type' => 'primary', 'buttonType' => 'submit'])
            </div>
        </form>
    </div>
@endsection
