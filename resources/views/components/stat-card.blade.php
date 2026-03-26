{{-- Stat Card Component --}}
{{-- Usage: @include('components.stat-card', ['title' => '...', 'value' => '...', 'icon' => '...', 'trend' => '+12%', 'trendUp' => true]) --}}
<div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900">{{ $value }}</p>
            @isset($trend)
                <p class="mt-2 flex items-center gap-1 text-sm font-medium {{ ($trendUp ?? false) ? 'text-green-600' : 'text-red-600' }}">
                    @if($trendUp ?? false)
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11l5-5m0 0l5 5m-5-5v12" /></svg>
                    @else
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 13l-5 5m0 0l-5-5m5 5V6" /></svg>
                    @endif
                    {{ $trend }}
                </p>
            @endisset
        </div>
        @isset($icon)
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $iconBg ?? 'bg-primary-50 text-primary-600' }}">
                {!! $icon !!}
            </div>
        @endisset
    </div>
</div>
