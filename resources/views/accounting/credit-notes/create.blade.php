@extends('layouts.app')

@section('title', __('messages.create_credit_note'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.credit-notes.index') }}" class="hover:text-gray-700">{{ __('messages.credit_notes_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.create_credit_note') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.create_credit_note') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.create_credit_note_subtitle') }}</p>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('accounting.credit-notes.store') }}" class="max-w-2xl">
        @csrf
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-5">
            <div>
                <label for="invoice_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.invoice') }}</label>
                <select name="invoice_id" id="invoice_id" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                    <option value="">-- {{ __('messages.select_invoice') }} --</option>
                    @foreach($invoices as $inv)
                        <option value="{{ $inv->id }}" {{ old('invoice_id') == $inv->id ? 'selected' : '' }}>
                            {{ $inv->invoice_number }} - {{ $inv->client?->name }} ({{ format_currency($inv->total) }})
                        </option>
                    @endforeach
                </select>
                @error('invoice_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.date') }}</label>
                    <input type="date" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.amount') }}</label>
                    <input type="number" name="amount" id="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label for="tax_amount" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.tax_amount') }}</label>
                    <input type="number" name="tax_amount" id="tax_amount" value="{{ old('tax_amount', 0) }}" step="0.01" min="0"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
            </div>
            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.reason') }}</label>
                <textarea name="reason" id="reason" rows="2" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">{{ old('reason') }}</textarea>
                @error('reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.notes') }}</label>
                <textarea name="notes" id="notes" rows="2"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">{{ old('notes') }}</textarea>
            </div>
            <div class="flex justify-end gap-2 pt-4">
                @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('accounting.credit-notes.index')])
                @include('components.button', ['label' => __('messages.save'), 'type' => 'primary', 'buttonType' => 'submit'])
            </div>
        </div>
    </form>
@endsection
