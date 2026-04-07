@extends('layouts.app')

@section('title', __('messages.add_asset'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.assets.index') }}" class="hover:text-gray-700">{{ __('messages.fixed_assets_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.add_asset') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.add_asset') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.add_asset_subtitle') }}</p>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('accounting.assets.store') }}" class="max-w-3xl">
        @csrf
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.asset_code') }}</label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm" placeholder="FA-001">
                    @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.asset_name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.category') }}</label>
                    <select name="category" id="category" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ __('messages.category_' . $cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.purchase_date') }}</label>
                    <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date') }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.location') }}</label>
                    <input type="text" name="location" id="location" value="{{ old('location') }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="purchase_cost" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.purchase_cost') }}</label>
                    <input type="text" name="purchase_cost" id="purchase_cost" value="{{ old('purchase_cost') }}" x-currency required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label for="salvage_value" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.salvage_value') }}</label>
                    <input type="text" name="salvage_value" id="salvage_value" value="{{ old('salvage_value', 0) }}" x-currency required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label for="useful_life_months" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.useful_life_months') }}</label>
                    <input type="number" name="useful_life_months" id="useful_life_months" value="{{ old('useful_life_months', 60) }}" min="1" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
            </div>
            <div>
                <label for="depreciation_method" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.depreciation_method') }}</label>
                <select name="depreciation_method" id="depreciation_method" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm max-w-xs">
                    @foreach($methods as $method)
                        <option value="{{ $method }}" {{ old('depreciation_method', 'straight_line') === $method ? 'selected' : '' }}>
                            {{ __('messages.method_' . $method) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                <textarea name="description" id="description" rows="2"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">{{ old('description') }}</textarea>
            </div>

            {{-- COA Accounts --}}
            <div class="border-t border-gray-100 pt-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('messages.linked_accounts') }}</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="coa_asset_id" class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.asset_account') }}</label>
                        <select name="coa_asset_id" id="coa_asset_id" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                            <option value="">--</option>
                            @foreach($assetAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="coa_depreciation_id" class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.depreciation_account') }}</label>
                        <select name="coa_depreciation_id" id="coa_depreciation_id" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                            <option value="">--</option>
                            @foreach($depreciationAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="coa_expense_id" class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.expense_account') }}</label>
                        <select name="coa_expense_id" id="coa_expense_id" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                            <option value="">--</option>
                            @foreach($expenseAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('accounting.assets.index')])
                @include('components.button', ['label' => __('messages.save'), 'type' => 'primary', 'buttonType' => 'submit'])
            </div>
        </div>
    </form>
@endsection
