@extends('layouts.app')

@section('title', __('messages.add_bank_account'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.bank.index') }}" class="hover:text-gray-700">{{ __('messages.bank_accounts_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.add_bank_account') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.add_bank_account') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.add_bank_account_subtitle') }}</p>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('accounting.bank.store') }}" class="max-w-2xl">
        @csrf
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-5">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.account_name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.bank_name') }}</label>
                    <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('bank_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="account_number" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.account_number') }}</label>
                    <input type="text" name="account_number" id="account_number" value="{{ old('account_number') }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('account_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="opening_balance" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.opening_balance') }}</label>
                    <input type="number" name="opening_balance" id="opening_balance" value="{{ old('opening_balance', 0) }}" step="0.01" min="0" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('opening_balance') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="coa_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.coa_account') }}</label>
                    <select name="coa_id" id="coa_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">-- {{ __('messages.select') }} --</option>
                        @foreach($coaAccounts as $coa)
                            <option value="{{ $coa->id }}" {{ old('coa_id') == $coa->id ? 'selected' : '' }}>
                                {{ $coa->code }} - {{ $coa->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('coa_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-4">
                @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('accounting.bank.index')])
                @include('components.button', ['label' => __('messages.save'), 'type' => 'primary', 'buttonType' => 'submit'])
            </div>
        </div>
    </form>
@endsection
