@extends('layouts.app')

@section('title', __('messages.credit_notes_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.credit_notes_title') }}</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.credit_notes_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.credit_notes_subtitle') }}</p>
        </div>
        @include('components.button', ['label' => __('messages.create_credit_note'), 'type' => 'primary', 'href' => route('accounting.credit-notes.create')])
    </div>
@endsection

@section('content')
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="px-6 py-4">{{ __('messages.number') }}</th>
                    <th class="px-6 py-4">{{ __('messages.date') }}</th>
                    <th class="px-6 py-4">{{ __('messages.client') }}</th>
                    <th class="px-6 py-4">{{ __('messages.invoice') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('messages.amount') }}</th>
                    <th class="px-6 py-4">{{ __('messages.reason') }}</th>
                    <th class="px-6 py-4">{{ __('messages.status') }}</th>
                    <th class="px-6 py-4">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($creditNotes as $cn)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-3 text-sm font-mono font-medium text-gray-900">{{ $cn->credit_note_number }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600">{{ \Carbon\Carbon::parse($cn->date)->format('d M Y') }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600">{{ $cn->client?->name ?? '-' }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600">{{ $cn->invoice?->invoice_number ?? '-' }}</td>
                        <td class="px-6 py-3 text-sm text-right font-semibold text-gray-700">{{ format_currency($cn->total_amount) }}</td>
                        <td class="px-6 py-3 text-sm text-gray-500 max-w-xs truncate">{{ $cn->reason }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $cn->status === 'approved' ? 'bg-green-50 text-green-700' : ($cn->status === 'applied' ? 'bg-blue-50 text-blue-700' : 'bg-yellow-50 text-yellow-700') }}">
                                {{ ucfirst($cn->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            @if($cn->isDraft())
                                <form method="POST" action="{{ route('accounting.credit-notes.approve', $cn) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-sm text-green-600 hover:underline">{{ __('messages.approve') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('messages.no_credit_notes') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
