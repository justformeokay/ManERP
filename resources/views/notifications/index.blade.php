@extends('layouts.app')

@section('title', 'Notifications')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Notifications</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
            <p class="mt-1 text-sm text-gray-500">View and manage your notifications.</p>
        </div>
        @if($notifications->where('read_at', null)->count())
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                @include('components.button', [
                    'label' => 'Mark All as Read',
                    'type' => 'secondary',
                    'buttonType' => 'submit',
                ])
            </form>
        @endif
    </div>
@endsection

@section('content')
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        @forelse($notifications as $notification)
            <div class="flex items-start gap-4 px-6 py-4 border-b border-gray-50 {{ $notification->read_at ? 'opacity-60' : 'bg-blue-50/30' }}">
                <div class="shrink-0 mt-0.5">
                    @if(($notification->data['type'] ?? '') === 'low_stock')
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100">
                            <svg class="h-5 w-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        </span>
                    @else
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100">
                            <svg class="h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-gray-900">{{ $notification->data['title'] ?? 'Notification' }}</p>
                        @unless($notification->read_at)
                            <span class="inline-flex h-2 w-2 rounded-full bg-blue-500"></span>
                        @endunless
                    </div>
                    <p class="text-sm text-gray-600 mt-0.5">{{ $notification->data['message'] ?? '' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }} &mdash; {{ $notification->created_at->format('d M Y H:i') }}</p>
                </div>
                @unless($notification->read_at)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf
                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 font-medium whitespace-nowrap">Mark read</button>
                    </form>
                @endunless
            </div>
        @empty
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <h3 class="mt-3 text-sm font-medium text-gray-900">No notifications</h3>
                <p class="mt-1 text-sm text-gray-500">You're all caught up!</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
@endsection
