@extends('layouts.app')

@section('title', __('messages.support_tickets'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.support_tickets') }}</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.support_tickets') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.support_tickets_subtitle') }}</p>
        </div>
        @include('components.button', [
            'label' => __('messages.new_ticket'),
            'type' => 'primary',
            'href' => route('support.create'),
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
        ])
    </div>
@endsection

@section('content')
    {{-- Status Filters --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @php
            $statusFilters = [
                ''             => __('messages.all'),
                'open'         => __('messages.status_open'),
                'in_progress'  => __('messages.status_in_progress'),
                'resolved'     => __('messages.status_resolved'),
                'closed'       => __('messages.status_closed'),
            ];
            $currentStatus = request('status', '');
        @endphp
        @foreach($statusFilters as $key => $label)
            <a href="{{ route('support.index', $key ? ['status' => $key] : []) }}"
               class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $currentStatus === $key ? 'bg-primary-600 text-white shadow-sm' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Tickets Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="px-6 py-4">{{ __('messages.ticket_number') }}</th>
                    <th class="px-6 py-4">{{ __('messages.title') }}</th>
                    @if(auth()->user()->isAdmin())
                        <th class="px-6 py-4">{{ __('messages.submitted_by') }}</th>
                    @endif
                    <th class="px-6 py-4">{{ __('messages.category') }}</th>
                    <th class="px-6 py-4">{{ __('messages.priority') }}</th>
                    <th class="px-6 py-4">{{ __('messages.status') }}</th>
                    <th class="px-6 py-4">{{ __('messages.date') }}</th>
                    <th class="px-6 py-4">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($tickets as $ticket)
                    @php
                        $priorityColors = [
                            'low'      => 'bg-gray-100 text-gray-700',
                            'medium'   => 'bg-blue-50 text-blue-700',
                            'high'     => 'bg-orange-50 text-orange-700',
                            'critical' => 'bg-red-50 text-red-700',
                        ];
                        $statusColors = [
                            'open'        => 'bg-yellow-50 text-yellow-700',
                            'in_progress' => 'bg-blue-50 text-blue-700',
                            'resolved'    => 'bg-green-50 text-green-700',
                            'closed'      => 'bg-gray-100 text-gray-600',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-3 text-sm font-mono text-gray-900">{{ $ticket->ticket_number }}</td>
                        <td class="px-6 py-3 text-sm text-gray-900 font-medium max-w-xs truncate">{{ $ticket->title }}</td>
                        @if(auth()->user()->isAdmin())
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $ticket->user->name }}</td>
                        @endif
                        <td class="px-6 py-3">
                            <span class="text-xs font-medium text-gray-600 capitalize">{{ __('messages.cat_' . $ticket->category) }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $priorityColors[$ticket->priority] ?? '' }} capitalize">
                                {{ __('messages.priority_' . $ticket->priority) }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$ticket->status] ?? '' }}">
                                {{ __('messages.status_' . $ticket->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-500">{{ $ticket->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-3">
                            <a href="{{ route('support.show', $ticket) }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">{{ __('messages.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 8 : 7 }}" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('messages.no_tickets') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($tickets->hasPages())
            <div class="px-6 py-3 border-t border-gray-100">{{ $tickets->links() }}</div>
        @endif
    </div>
@endsection
