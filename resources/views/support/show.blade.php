@extends('layouts.app')

@section('title', $ticket->title)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('support.index') }}" class="hover:text-gray-700">{{ __('messages.support_tickets') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $ticket->ticket_number }}</span>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        {{-- Ticket Header --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <span class="text-xs font-mono text-gray-400">{{ $ticket->ticket_number }}</span>
                        @php
                            $statusColors = [
                                'open'        => 'bg-yellow-50 text-yellow-700 ring-yellow-200',
                                'in_progress' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                'resolved'    => 'bg-green-50 text-green-700 ring-green-200',
                                'closed'      => 'bg-gray-100 text-gray-600 ring-gray-200',
                            ];
                            $priorityColors = [
                                'low'      => 'bg-gray-100 text-gray-700',
                                'medium'   => 'bg-blue-50 text-blue-700',
                                'high'     => 'bg-orange-50 text-orange-700',
                                'critical' => 'bg-red-50 text-red-700',
                            ];
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColors[$ticket->status] ?? '' }}">
                            {{ __('messages.status_' . $ticket->status) }}
                        </span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $priorityColors[$ticket->priority] ?? '' }}">
                            {{ __('messages.priority_' . $ticket->priority) }}
                        </span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $ticket->title }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ __('messages.submitted_by') }}: <span class="font-medium text-gray-700">{{ $ticket->user->name }}</span>
                        &middot; {{ __('messages.category') }}: <span class="font-medium text-gray-700 capitalize">{{ __('messages.cat_' . $ticket->category) }}</span>
                        &middot; {{ $ticket->created_at->format('d M Y H:i') }}
                    </p>
                </div>

                {{-- Admin: Status Change --}}
                @if(auth()->user()->isAdmin() && $ticket->status !== 'closed')
                    <form action="{{ route('support.updateStatus', $ticket) }}" method="POST" class="flex items-center gap-2 shrink-0">
                        @csrf
                        <select name="status" class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                            @foreach(\App\Models\SupportTicket::statuses() as $s)
                                <option value="{{ $s }}" {{ $ticket->status === $s ? 'selected' : '' }}>{{ __('messages.status_' . $s) }}</option>
                            @endforeach
                        </select>
                        @include('components.button', [
                            'label' => __('messages.update'),
                            'type' => 'secondary',
                            'buttonType' => 'submit',
                        ])
                    </form>
                @endif
            </div>
        </div>

        {{-- Chat Messages --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto" id="chat-container">
                {{-- Original Description --}}
                <div class="flex gap-3">
                    <div class="h-9 w-9 shrink-0 rounded-full bg-primary-100 flex items-center justify-center text-sm font-bold text-primary-700">
                        {{ strtoupper(substr($ticket->user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <div class="rounded-2xl rounded-tl-md bg-gray-50 px-4 py-3">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-semibold text-gray-900">{{ $ticket->user->name }}</span>
                                <span class="text-xs text-gray-400">{{ $ticket->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <div class="text-sm text-gray-700 whitespace-pre-line">{{ $ticket->description }}</div>
                        </div>
                    </div>
                </div>

                {{-- Replies --}}
                @foreach($ticket->replies as $reply)
                    @php
                        $isAdmin = $reply->is_admin_reply;
                    @endphp
                    <div class="flex gap-3 {{ $isAdmin ? 'flex-row-reverse' : '' }}">
                        <div class="h-9 w-9 shrink-0 rounded-full {{ $isAdmin ? 'bg-green-100' : 'bg-primary-100' }} flex items-center justify-center text-sm font-bold {{ $isAdmin ? 'text-green-700' : 'text-primary-700' }}">
                            {{ strtoupper(substr($reply->user->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 {{ $isAdmin ? 'flex flex-col items-end' : '' }}">
                            <div class="rounded-2xl {{ $isAdmin ? 'rounded-tr-md bg-green-50' : 'rounded-tl-md bg-gray-50' }} px-4 py-3 max-w-[85%] inline-block">
                                <div class="flex items-center gap-2 mb-1 {{ $isAdmin ? 'justify-end' : '' }}">
                                    @if($isAdmin)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-700">Admin</span>
                                    @endif
                                    <span class="text-sm font-semibold text-gray-900">{{ $reply->user->name }}</span>
                                    <span class="text-xs text-gray-400">{{ $reply->created_at->format('d M Y H:i') }}</span>
                                </div>
                                <div class="text-sm text-gray-700 whitespace-pre-line">{{ $reply->message }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Reply Form --}}
            @if($ticket->isOpen())
                <div class="border-t border-gray-100 p-4">
                    <form action="{{ route('support.reply', $ticket) }}" method="POST" class="flex gap-3 items-end">
                        @csrf
                        <div class="flex-1">
                            <textarea name="message" rows="2" required
                                      class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 resize-none"
                                      placeholder="{{ __('messages.type_reply') }}">{{ old('message') }}</textarea>
                            @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @include('components.button', [
                            'label' => __('messages.send_reply'),
                            'type' => 'primary',
                            'buttonType' => 'submit',
                            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>',
                        ])
                    </form>
                </div>
            @else
                <div class="border-t border-gray-100 p-4 text-center text-sm text-gray-400">
                    {{ __('messages.ticket_closed_no_reply') }}
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chat = document.getElementById('chat-container');
            if (chat) chat.scrollTop = chat.scrollHeight;
        });
    </script>
@endsection
