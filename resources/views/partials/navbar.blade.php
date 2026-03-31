{{-- Top Navbar --}}
<header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6 lg:px-8">
    {{-- Left Section --}}
    <div class="flex items-center gap-4 flex-1 min-w-0">
        {{-- Mobile menu button --}}
        <button @click="mobileSidebarOpen = true" class="lg:hidden -ml-1 p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 shrink-0">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        {{-- Search --}}
        <div class="flex-1 max-w-lg">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    type="search"
                    placeholder="{{ __('messages.search_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2 pl-10 pr-4 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                />
            </div>
        </div>
    </div>

    {{-- Right Section --}}
    <div class="flex items-center gap-2 ml-4">
        {{-- Notifications --}}
        @php
            $unreadNotifications = auth()->user()->unreadNotifications()->latest()->limit(10)->get();
            $unreadCount = auth()->user()->unreadNotifications()->count();
        @endphp
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="relative rounded-xl p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                @if($unreadCount > 0)
                    <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </button>

            {{-- Notification dropdown --}}
            <div x-show="open" @click.away="open = false" x-cloak
                 class="absolute right-0 mt-2 w-80 rounded-xl bg-white shadow-lg ring-1 ring-black/5 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-semibold text-gray-900">{{ __('messages.notifications') }}</p>
                    @if($unreadCount > 0)
                        <form method="POST" action="{{ route('notifications.readAll') }}">
                            @csrf
                            <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 font-medium">{{ __('messages.mark_all_read') }}</button>
                        </form>
                    @endif
                </div>
                <div class="max-h-72 overflow-y-auto divide-y divide-gray-50">
                    @forelse($unreadNotifications as $notification)
                        <div class="px-4 py-3 hover:bg-gray-50 transition">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 mt-0.5">
                                    @if(($notification->data['type'] ?? '') === 'low_stock')
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100">
                                            <svg class="h-4 w-4 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                        </span>
                                    @else
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100">
                                            <svg class="h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </span>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $notification->data['message'] ?? '' }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-gray-400 hover:text-blue-600 shrink-0" title="Mark as read">&times;</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center">
                            <svg class="mx-auto h-8 w-8 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <p class="mt-2 text-sm text-gray-500">{{ __('messages.no_notifications') }}</p>
                        </div>
                    @endforelse
                </div>
                @if($unreadCount > 0)
                    <div class="border-t border-gray-100 px-4 py-2.5">
                        <a href="{{ route('notifications.index') }}" class="block text-center text-xs font-medium text-blue-600 hover:text-blue-800">{{ __('messages.view_all_notifications') }}</a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Language Switcher --}}
        @php
            $currentLocale = app()->getLocale();
            $locales = [
                'en' => ['flag' => '🇺🇸', 'name' => 'English'],
                'id' => ['flag' => '🇮🇩', 'name' => 'Bahasa Indonesia'],
                'zh' => ['flag' => '🇨🇳', 'name' => '中文'],
            ];
        @endphp
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-1.5 rounded-xl px-2.5 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition">
                <span class="text-base">{{ $locales[$currentLocale]['flag'] }}</span>
                <span class="hidden sm:inline text-sm">{{ $locales[$currentLocale]['name'] }}</span>
                <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" @click.away="open = false" x-cloak
                 class="absolute right-0 mt-2 w-48 rounded-xl bg-white shadow-lg ring-1 ring-black/5 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('messages.language') }}</p>
                </div>
                @foreach($locales as $code => $locale)
                    <a href="{{ route('language.switch', $code) }}"
                       class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-gray-50 transition {{ $currentLocale === $code ? 'bg-primary-50 text-primary-700' : 'text-gray-700' }}">
                        <span class="text-lg">{{ $locale['flag'] }}</span>
                        <span>{{ $locale['name'] }}</span>
                        @if($currentLocale === $code)
                            <svg class="ml-auto h-4 w-4 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- User dropdown --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-2 rounded-xl p-1.5 hover:bg-gray-100 transition">
                <div class="h-8 w-8 rounded-lg bg-primary-600 flex items-center justify-center text-white text-sm font-semibold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="hidden sm:block text-left">
                    <p class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ ucfirst(auth()->user()->role) }}</p>
                </div>
                <svg class="hidden sm:block h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" @click.away="open = false" x-cloak
                 class="absolute right-0 mt-2 w-48 rounded-xl bg-white shadow-lg ring-1 ring-black/5 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">{{ __('messages.profile_settings') }}</a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('settings.index') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">{{ __('messages.system_settings') }}</a>
                @endif
                <div class="border-t border-gray-100"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
                        {{ __('messages.sign_out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
