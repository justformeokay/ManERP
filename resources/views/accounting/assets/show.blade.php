@extends('layouts.app')

@section('title', $asset->name . ' - ' . __('messages.fixed_assets_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.assets.index') }}" class="hover:text-gray-700">{{ __('messages.fixed_assets_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $asset->code }}</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $asset->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $asset->code }} &mdash; {{ ucfirst($asset->category) }}</p>
        </div>
        @if($asset->isActive())
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="inline-flex items-center rounded-xl bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 ring-1 ring-red-200 hover:bg-red-100">
                    {{ __('messages.dispose_asset') }}
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-72 rounded-2xl bg-white p-4 shadow-lg ring-1 ring-gray-100 z-10">
                    <form method="POST" action="{{ route('accounting.assets.dispose', $asset) }}">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.date') }}</label>
                                <input type="date" name="disposed_date" value="{{ now()->toDateString() }}" required class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.disposal_amount') }}</label>
                                <input type="number" name="disposal_amount" value="0" step="0.01" min="0" required class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">
                                {{ __('messages.confirm_dispose') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('content')
    {{-- Asset Details --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-3">
            <h3 class="text-sm font-semibold text-gray-700">{{ __('messages.asset_details') }}</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.purchase_date') }}</dt><dd class="font-medium">{{ $asset->purchase_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.purchase_cost') }}</dt><dd class="font-medium">{{ format_currency($asset->purchase_cost) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.salvage_value') }}</dt><dd class="font-medium">{{ format_currency($asset->salvage_value) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.useful_life_months') }}</dt><dd class="font-medium">{{ $asset->useful_life_months }} {{ __('messages.months') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.depreciation_method') }}</dt><dd class="font-medium">{{ str_replace('_', ' ', ucfirst($asset->depreciation_method)) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.location') }}</dt><dd class="font-medium">{{ $asset->location ?: '-' }}</dd></div>
            </dl>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-3">
            <h3 class="text-sm font-semibold text-gray-700">{{ __('messages.depreciation_summary') }}</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.depreciable_amount') }}</dt><dd class="font-medium">{{ format_currency($asset->getDepreciableAmount()) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.accumulated_depreciation') }}</dt><dd class="font-medium text-amber-700">{{ format_currency($asset->accumulated_depreciation) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.net_book_value') }}</dt><dd class="font-bold text-blue-700">{{ format_currency($asset->book_value) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.monthly_depreciation') }}</dt><dd class="font-medium">{{ format_currency($asset->getMonthlyDepreciation()) }}</dd></div>
            </dl>
            {{-- Progress bar --}}
            @php $pct = $asset->purchase_cost > 0 ? ($asset->accumulated_depreciation / ($asset->purchase_cost - $asset->salvage_value)) * 100 : 0; @endphp
            <div class="pt-2">
                <div class="flex justify-between text-xs text-gray-500 mb-1"><span>{{ __('messages.depreciation_progress') }}</span><span>{{ number_format(min($pct, 100), 1) }}%</span></div>
                <div class="h-2 w-full rounded-full bg-gray-100"><div class="h-2 rounded-full bg-amber-500" style="width: {{ min($pct, 100) }}%"></div></div>
            </div>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-3">
            <h3 class="text-sm font-semibold text-gray-700">{{ __('messages.status') }}</h3>
            @php
                $statusColor = match($asset->status) {
                    'active' => 'bg-green-50 text-green-700 ring-green-200',
                    'fully_depreciated' => 'bg-amber-50 text-amber-700 ring-amber-200',
                    'disposed' => 'bg-gray-100 text-gray-600 ring-gray-200',
                    'sold' => 'bg-blue-50 text-blue-700 ring-blue-200',
                    default => 'bg-gray-100 text-gray-600 ring-gray-200',
                };
            @endphp
            <p class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 {{ $statusColor }}">
                {{ str_replace('_', ' ', ucfirst($asset->status)) }}
            </p>
            @if($asset->disposed_date)
                <dl class="space-y-2 text-sm mt-3">
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.disposed_date') }}</dt><dd class="font-medium">{{ $asset->disposed_date->format('d M Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.disposal_amount') }}</dt><dd class="font-medium">{{ format_currency($asset->disposal_amount) }}</dd></div>
                </dl>
            @endif
        </div>
    </div>

    {{-- Depreciation Schedule --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="bg-amber-50 px-6 py-4 border-b border-amber-100">
            <h3 class="text-base font-semibold text-amber-900">{{ __('messages.depreciation_schedule') }}</h3>
        </div>
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="px-6 py-4">#</th>
                    <th class="px-6 py-4">{{ __('messages.period') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.depreciation') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.accumulated') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.net_book_value') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($schedule as $row)
                    <tr class="hover:bg-gray-50/50 {{ $row['month'] <= $asset->depreciationEntries->count() ? 'bg-green-50/30' : '' }}">
                        <td class="px-6 py-3 text-sm text-gray-500">{{ $row['month'] }}</td>
                        <td class="px-6 py-3 text-sm text-gray-900">{{ $row['period'] }}</td>
                        <td class="px-6 py-3 text-sm text-right text-gray-700">{{ format_currency($row['depreciation']) }}</td>
                        <td class="px-6 py-3 text-sm text-right text-amber-700">{{ format_currency($row['accumulated']) }}</td>
                        <td class="px-6 py-3 text-sm text-right font-semibold text-gray-700">{{ format_currency($row['book_value']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">{{ __('messages.no_schedule') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
