@extends('layouts.app')

@section('title', __('messages.budgets_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.budgets_title') }}</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.budgets_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.budgets_subtitle') }}</p>
        </div>
        @include('components.button', ['label' => __('messages.create_budget'), 'type' => 'primary', 'href' => route('accounting.budgets.create')])
    </div>
@endsection

@section('content')
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="px-6 py-4">{{ __('messages.name') }}</th>
                    <th class="px-6 py-4">{{ __('messages.fiscal_year') }}</th>
                    <th class="px-6 py-4">{{ __('messages.status') }}</th>
                    <th class="px-6 py-4">{{ __('messages.created_by') }}</th>
                    <th class="px-6 py-4">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($budgets as $budget)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $budget->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $budget->fiscal_year }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $budget->status === 'approved' ? 'bg-green-50 text-green-700' : ($budget->status === 'closed' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-50 text-yellow-700') }}">
                                {{ ucfirst($budget->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $budget->createdBy?->name ?? '-' }}</td>
                        <td class="px-6 py-4 flex gap-2">
                            <a href="{{ route('accounting.budgets.show', $budget) }}" class="text-sm text-blue-600 hover:underline">{{ __('messages.view') }}</a>
                            @if($budget->isDraft())
                                <form method="POST" action="{{ route('accounting.budgets.approve', $budget) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-sm text-green-600 hover:underline">{{ __('messages.approve') }}</button>
                                </form>
                                <form method="POST" action="{{ route('accounting.budgets.destroy', $budget) }}" class="inline" onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('messages.delete') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('messages.no_budgets') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
