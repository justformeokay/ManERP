@extends('layouts.app')

@section('title', __('messages.notifications'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.notifications') }}</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.notifications') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.notifications_subtitle') }}</p>
        </div>
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                @include('components.button', [
                    'label' => __('messages.mark_all_read'),
                    'type' => 'secondary',
                    'buttonType' => 'submit',
                ])
            </form>
        @endif
    </div>
@endsection

@section('content')
    {{-- Filter Tabs --}}
    <div class="mb-6 flex flex-wrap items-center gap-4">
        {{-- Status Filter --}}
        <div class="flex items-center rounded-xl bg-white shadow-sm ring-1 ring-gray-100 p-1">
            @php
                $tabs = [
                    'all'    => __('messages.all'),
                    'unread' => __('messages.unread'),
                    'read'   => __('messages.read_status'),
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ route('notifications.index', array_merge(request()->query(), ['filter' => $key])) }}"
                   class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $filter === $key ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    {{ $label }}
                    @if($key === 'unread' && $unreadCount > 0)
                        <span class="ml-1 inline-flex items-center rounded-full {{ $filter === $key ? 'bg-white/20' : 'bg-red-100 text-red-700' }} px-2 py-0.5 text-xs font-semibold">{{ $unreadCount }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Category Filter --}}
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.category') }}:</span>
            <div class="flex flex-wrap gap-1">
                @php
                    $categories = [
                        ''                        => __('messages.all'),
                        'low_stock'               => __('messages.inventory'),
                        'sales_order_confirmed'   => __('messages.sales_heading'),
                        'support_ticket'          => __('messages.support'),
                        'support_ticket_reply'    => __('messages.support'),
                    ];
                @endphp
                <a href="{{ route('notifications.index', array_merge(request()->query(), ['category' => ''])) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ !$category ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ __('messages.all') }}
                </a>
                <a href="{{ route('notifications.index', array_merge(request()->query(), ['category' => 'low_stock'])) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ $category === 'low_stock' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                    {{ __('messages.inventory') }}
                </a>
                <a href="{{ route('notifications.index', array_merge(request()->query(), ['category' => 'sales_order_confirmed'])) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ $category === 'sales_order_confirmed' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                    {{ __('messages.sales_heading') }}
                </a>
                <a href="{{ route('notifications.index', array_merge(request()->query(), ['category' => 'support_ticket'])) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ in_array($category, ['support_ticket', 'support_ticket_reply']) ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-700 hover:bg-purple-100' }}">
                    {{ __('messages.support') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Notification List --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        @forelse($notifications as $notification)
            @php
                $type = $notification->data['type'] ?? '';
                $iconMap = [
                    'low_stock'              => ['bg' => 'bg-amber-100', 'text' => 'text-amber-600', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z'],
                    'sales_order_confirmed'  => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'support_ticket'         => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'],
                    'support_ticket_reply'   => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                ];
                $ico = $iconMap[$type] ?? ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
            @endphp
            <div class="flex items-start gap-4 px-6 py-4 border-b border-gray-50 {{ $notification->read_at ? 'opacity-60' : 'bg-blue-50/30' }}">
                <div class="shrink-0 mt-0.5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $ico['bg'] }}">
                        <svg class="h-5 w-5 {{ $ico['text'] }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $ico['icon'] }}" />
                        </svg>
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-gray-900">{{ $notification->data['title'] ?? __('messages.notifications') }}</p>
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
                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 font-medium whitespace-nowrap">{{ __('messages.mark_read') }}</button>
                    </form>
                @endunless
            </div>
        @empty
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <h3 class="mt-3 text-sm font-medium text-gray-900">{{ __('messages.no_notifications') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.all_caught_up') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
@endsection
