{{-- Academy Tooltip Component --}}
{{-- Usage: @include('components.academy-tooltip', ['slug' => 'capex']) --}}
<span class="relative group inline-flex items-center" x-data="{ show: false, content: '', title: '', loaded: false }"
    @mouseenter="if (!loaded) { fetch('{{ route('academy.tooltip') }}?slug={{ $slug }}').then(r => r.json()).then(d => { if (d.content) { title = d.title; content = d.content; } loaded = true; }); } show = true"
    @mouseleave="show = false">
    <button type="button" class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-gray-200 text-gray-500 text-[10px] font-bold cursor-help hover:bg-primary-100 hover:text-primary-700 transition"
        aria-label="Info" @focus="show = true" @blur="show = false">i</button>
    <div x-show="show" x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 rounded-xl bg-gray-900 p-3 text-xs text-white shadow-lg"
        x-cloak>
        <p class="font-semibold text-primary-300 mb-1" x-text="title"></p>
        <p class="leading-relaxed text-gray-300" x-text="content"></p>
        <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-px border-4 border-transparent border-t-gray-900"></div>
    </div>
</span>
