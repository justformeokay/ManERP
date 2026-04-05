{{-- Sidebar Navigation --}}
<div class="flex h-full flex-col bg-gray-900 text-white" x-bind:class="sidebarOpen ? 'w-64' : 'w-20'">
    {{-- Logo --}}
    <div class="flex h-16 items-center gap-3 px-4 border-b border-gray-800">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-600 font-bold text-white text-sm">
            M
        </div>
        <span x-show="sidebarOpen" x-cloak class="text-lg font-semibold tracking-tight">ManERP</span>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @foreach($sidebarMenu ?? [] as $item)
            @if(isset($item['heading']))
                {{-- Check special permission on heading --}}
                @if(isset($item['special_permission']) && auth()->check() && !auth()->user()->hasPermission($item['special_permission']))
                    @continue
                @endif
                {{-- Check permission-gated heading --}}
                @if(isset($item['permission']) && auth()->check() && !auth()->user()->hasModuleAccess($item['permission']))
                    @continue
                @endif
                <p x-show="sidebarOpen" x-cloak class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    {{ $item['heading'] }}
                </p>
            @else
                {{-- Check special permission on item (fine-grained) --}}
                @if(isset($item['special_permission']) && auth()->check() && !auth()->user()->hasPermission($item['special_permission']))
                    @continue
                @endif
                {{-- Check permission-gated item (module-level) --}}
                @if(isset($item['permission']) && auth()->check() && !auth()->user()->hasModuleAccess($item['permission']))
                    @continue
                @endif
                <a
                    href="{{ $item['url'] }}"
                    class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors
                        {{ request()->routeIs($item['active'] ?? '') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
                    :title="!sidebarOpen ? '{{ $item['label'] }}' : ''"
                >
                    <span class="shrink-0 w-5 h-5">{!! $item['icon'] !!}</span>
                    <span x-show="sidebarOpen" x-cloak>{{ $item['label'] }}</span>
                    @if(isset($item['badge']))
                        <span x-show="sidebarOpen" x-cloak class="ml-auto inline-flex items-center rounded-full bg-primary-600 px-2 py-0.5 text-xs font-medium text-white">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                </a>
            @endif
        @endforeach
    </nav>

    {{-- Collapse toggle (desktop) --}}
    <div class="hidden lg:flex border-t border-gray-800 p-3">
        <button
            @click="sidebarOpen = !sidebarOpen"
            class="flex w-full items-center justify-center rounded-lg p-2 text-gray-400 hover:bg-gray-800 hover:text-white transition-colors"
        >
            <svg x-show="sidebarOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
            <svg x-show="!sidebarOpen" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
            </svg>
        </button>
    </div>
</div>
