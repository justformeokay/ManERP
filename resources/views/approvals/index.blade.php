@extends('layouts.app')

@section('title', __('approval.approvals'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('common.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('approval.approvals') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900">{{ __('approval.approvals') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('approval.pending_count', ['count' => $pendingApprovals->count()]) }}</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Pending Approvals --}}
    @if($pendingApprovals->isNotEmpty())
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            {{ __('approval.pending_approvals') }}
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($pendingApprovals as $approval)
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="inline-flex items-center rounded-lg bg-{{ $approval->getModuleColor() }}-100 px-2.5 py-1 text-xs font-medium text-{{ $approval->getModuleColor() }}-700">
                                {{ $approval->module_label }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-500">{{ $approval->created_at->diffForHumans() }}</span>
                    </div>

                    <div class="mb-3">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $approval->flow?->name ?? $approval->module }}
                        </p>
                        <p class="text-lg font-bold text-gray-900 mt-1">
                            Rp {{ number_format($approval->amount, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                        <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">
                            {{ strtoupper(substr($approval->requester?->name ?? 'U', 0, 1)) }}
                        </div>
                        <span>{{ $approval->requester?->name ?? 'Unknown' }}</span>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-4">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>{{ __('approval.step') }} {{ $approval->current_step }}</span>
                            <span>{{ $approval->progress }}%</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-gray-200">
                            <div class="h-1.5 rounded-full bg-primary-500 transition-all" style="width: {{ $approval->progress }}%"></div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('approvals.show', $approval) }}" 
                           class="flex-1 inline-flex items-center justify-center gap-1 rounded-xl bg-primary-500 px-3 py-2 text-sm font-medium text-white hover:bg-primary-600 transition">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ __('approval.review') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="mb-8 rounded-2xl bg-green-50 border border-green-200 p-6 text-center">
        <svg class="mx-auto h-12 w-12 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-green-800">{{ __('approval.no_pending') }}</h3>
        <p class="mt-1 text-sm text-green-600">{{ __('approval.no_pending_description') }}</p>
    </div>
    @endif

    {{-- My Requests --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('approval.my_requests') }}</h2>

        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('approval.document') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('common.amount') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('common.status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('approval.progress') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('common.date') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($myRequests as $approval)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center rounded-lg bg-{{ $approval->getModuleColor() }}-100 px-2.5 py-1 text-xs font-medium text-{{ $approval->getModuleColor() }}-700">
                                            {{ $approval->module_label }}
                                        </span>
                                        <span class="text-sm text-gray-600">#{{ $approval->reference_id }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium text-gray-900">Rp {{ number_format($approval->amount, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        @if($approval->status === 'approved') bg-green-100 text-green-700
                                        @elseif($approval->status === 'rejected') bg-red-100 text-red-700
                                        @elseif($approval->status === 'cancelled') bg-gray-100 text-gray-700
                                        @else bg-amber-100 text-amber-700 @endif">
                                        {{ ucfirst($approval->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-20 rounded-full bg-gray-200">
                                            <div class="h-1.5 rounded-full bg-primary-500" style="width: {{ $approval->progress }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $approval->progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $approval->created_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('approvals.show', $approval) }}" class="text-primary-600 hover:text-primary-800 text-sm font-medium">
                                        {{ __('common.view') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                    {{ __('approval.no_requests') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($myRequests->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $myRequests->links() }}
            </div>
            @endif
        </div>
    </div>
@endsection
