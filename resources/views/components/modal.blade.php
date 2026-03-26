{{-- Modal Component --}}
{{-- Usage: wrap content with this, controlled by Alpine.js --}}
{{-- <div x-data="{ open: false }"> <button @click="open = true">Open</button> @include('components.modal', ['title' => 'My Modal']) </div> --}}
<template x-teleport="#modal-container">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        {{-- Backdrop --}}
        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-gray-900/50" @click="open = false"></div>

        {{-- Panel --}}
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full {{ $maxWidth ?? 'max-w-lg' }} rounded-2xl bg-white shadow-xl"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ $title ?? 'Modal' }}</h3>
                <button @click="open = false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-4">
                {{ $slot ?? '' }}
            </div>
        </div>
    </div>
</template>
