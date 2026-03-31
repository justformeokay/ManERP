@extends('layouts.app')

@section('title', __('approval.approval_detail'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('common.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('approvals.index') }}" class="hover:text-gray-700">{{ __('approval.approvals') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">#{{ $approval->id }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
        <div class="min-w-0">
            <div class="flex items-center gap-3 mb-1">
                <span class="inline-flex items-center rounded-lg bg-{{ $approval->getModuleColor() }}-100 px-2.5 py-1 text-sm font-medium text-{{ $approval->getModuleColor() }}-700">
                    {{ $approval->module_label }}
                </span>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-sm font-medium
                    @if($approval->status === 'approved') bg-green-100 text-green-700
                    @elseif($approval->status === 'rejected') bg-red-100 text-red-700
                    @elseif($approval->status === 'cancelled') bg-gray-100 text-gray-700
                    @else bg-amber-100 text-amber-700 @endif">
                    {{ ucfirst($approval->status) }}
                </span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('approval.approval_detail') }}</h1>
        </div>
        <div class="flex gap-2">
            @if($approval->status === 'rejected' && $approval->requested_by === auth()->id())
                <form action="{{ route('approvals.resubmit', $approval) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 transition">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        {{ __('approval.resubmit') }}
                    </button>
                </form>
            @endif

            @if($approval->status === 'pending' && $approval->requested_by === auth()->id())
                <form action="{{ route('approvals.cancel', $approval) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('approval.confirm_cancel') }}')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition">
                        {{ __('common.cancel') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Document Info --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('approval.document_info') }}</h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('approval.module') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $approval->module_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('approval.reference_id') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">#{{ $approval->reference_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('common.amount') }}</dt>
                        <dd class="mt-1 text-lg font-bold text-gray-900">{{ format_currency($approval->amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('approval.requested_by') }}</dt>
                        <dd class="mt-1 flex items-center gap-2">
                            <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">
                                {{ strtoupper(substr($approval->requester?->name ?? 'U', 0, 1)) }}
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $approval->requester?->name ?? 'Unknown' }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('common.created_at') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $approval->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @if($approval->approved_at)
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('approval.approved_at') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $approval->approved_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @endif
                    @if($approval->rejected_at)
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('approval.rejected_at') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $approval->rejected_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @endif
                </dl>

                {{-- View Document Link --}}
                @if($document)
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <a href="{{ $approval->getDocumentUrl() }}" class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-800 text-sm font-medium">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                        {{ __('approval.view_document') }}
                    </a>
                </div>
                @endif
            </div>

            {{-- Action Form (for approvers) --}}
            @if($approval->status === 'pending' && $approval->canBeApprovedBy(auth()->user()))
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('approval.take_action') }}</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Approve Form --}}
                    <form action="{{ route('approvals.approve', $approval) }}" method="POST" class="space-y-3">
                        @csrf
                        <textarea name="notes" rows="2" placeholder="{{ __('approval.notes_optional') }}"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700 focus:border-green-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-green-500 transition"></textarea>
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-green-500 px-4 py-3 text-sm font-medium text-white hover:bg-green-600 transition">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            {{ __('approval.approve') }}
                        </button>
                    </form>

                    {{-- Reject Form --}}
                    <form action="{{ route('approvals.reject', $approval) }}" method="POST" class="space-y-3">
                        @csrf
                        <textarea name="reason" rows="2" placeholder="{{ __('approval.rejection_reason') }}" required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700 focus:border-red-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-red-500 transition"></textarea>
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-red-500 px-4 py-3 text-sm font-medium text-white hover:bg-red-600 transition">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ __('approval.reject') }}
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar - Approval Timeline --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 sticky top-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('approval.approval_history') }}</h2>

                {{-- Progress --}}
                <div class="mb-6">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-500">{{ __('approval.progress') }}</span>
                        <span class="font-medium text-gray-900">{{ $approval->progress }}%</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-gray-200">
                        <div class="h-2 rounded-full bg-primary-500 transition-all" style="width: {{ $approval->progress }}%"></div>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="relative">
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                    <div class="space-y-6">
                        @foreach($approval->logs as $log)
                            <div class="relative flex gap-4">
                                {{-- Icon --}}
                                <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full
                                    @if($log->action === 'approved') bg-green-500 text-white
                                    @elseif($log->action === 'rejected') bg-red-500 text-white
                                    @elseif($log->action === 'pending' && $log->step_order === $approval->current_step) bg-amber-500 text-white
                                    @else bg-gray-200 text-gray-500 @endif">
                                    @if($log->action === 'approved')
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    @elseif($log->action === 'rejected')
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    @elseif($log->action === 'pending' && $log->step_order === $approval->current_step)
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @else
                                        <span class="text-xs font-medium">{{ $log->step_order }}</span>
                                    @endif
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0 pb-4">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $log->role?->name ?? 'Unknown Role' }}
                                        </p>
                                        <span class="text-xs px-2 py-0.5 rounded-full
                                            @if($log->action === 'approved') bg-green-100 text-green-700
                                            @elseif($log->action === 'rejected') bg-red-100 text-red-700
                                            @elseif($log->action === 'pending') bg-amber-100 text-amber-700
                                            @else bg-gray-100 text-gray-600 @endif">
                                            {{ $log->action_label }}
                                        </span>
                                    </div>

                                    @if($log->actor)
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ $log->actor->name }}
                                        </p>
                                    @endif

                                    @if($log->notes)
                                        <p class="mt-2 text-sm text-gray-600 bg-gray-50 rounded-lg p-2">
                                            "{{ $log->notes }}"
                                        </p>
                                    @endif

                                    @if($log->updated_at && $log->action !== 'pending')
                                        <p class="mt-1 text-xs text-gray-400">
                                            {{ $log->updated_at->format('d M Y, H:i') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
