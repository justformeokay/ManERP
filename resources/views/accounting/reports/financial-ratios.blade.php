@extends('layouts.app')

@section('title', __('messages.financial_ratios_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.financial_ratios_title') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.financial_ratios_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.financial_ratios_subtitle') }}</p>
    </div>
@endsection

@section('content')
    {{-- Date Filter --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="date" class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.as_of_date') }}</label>
                <input type="date" name="date" id="date" value="{{ $date }}"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            @include('components.button', ['label' => __('messages.generate'), 'type' => 'primary', 'buttonType' => 'submit'])
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($ratios as $category => $items)
            @php
                $colors = match($category) {
                    'liquidity' => ['bg-blue-50', 'border-blue-100', 'text-blue-900'],
                    'profitability' => ['bg-green-50', 'border-green-100', 'text-green-900'],
                    'leverage' => ['bg-amber-50', 'border-amber-100', 'text-amber-900'],
                    'efficiency' => ['bg-purple-50', 'border-purple-100', 'text-purple-900'],
                    default => ['bg-gray-50', 'border-gray-100', 'text-gray-900'],
                };
            @endphp
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="{{ $colors[0] }} px-6 py-4 border-b {{ $colors[1] }}">
                    <h3 class="text-base font-semibold {{ $colors[2] }}">{{ __('messages.ratio_' . $category) }}</h3>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($items as $ratio)
                        <div class="px-6 py-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $ratio['label'] }}</p>
                                <p class="text-xs text-gray-400">{{ $ratio['benchmark'] ?? '' }}</p>
                            </div>
                            <div class="text-right">
                                @php
                                    $value = $ratio['value'];
                                    $formatted = is_numeric($value) ? (str_contains($ratio['label'], '%') || str_contains($ratio['label'], 'Margin') || str_contains($ratio['label'], 'ROA') || str_contains($ratio['label'], 'ROE')
                                        ? number_format($value * 100, 2) . '%'
                                        : (str_contains($ratio['label'], 'Days') || str_contains($ratio['label'], 'Hari')
                                            ? number_format($value, 0) . ' days'
                                            : number_format($value, 2))) : $value;
                                @endphp
                                <p class="text-lg font-bold text-gray-900">{{ $formatted }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endsection
