{{-- Button Component --}}
{{-- Usage: @include('components.button', ['label' => 'Add New', 'type' => 'primary', 'href' => '...']) --}}
@php
    $baseClasses = 'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2';
    $variants = [
        'primary'   => 'bg-primary-600 text-black hover:bg-primary-700 focus:ring-primary-500 shadow-md',
        'secondary' => 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 focus:ring-primary-500',
        'danger'    => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-sm',
        'ghost'     => 'text-gray-600 hover:bg-gray-100 focus:ring-gray-500',
    ];
    $classes = $baseClasses . ' ' . ($variants[$type ?? 'primary'] ?? $variants['primary']);
@endphp

@if(isset($href))
    <a href="{{ $href }}" class="{{ $classes }}">
        @isset($icon){!! $icon !!}@endisset
        {{ $label }}
    </a>
@else
    <button type="{{ $buttonType ?? 'button' }}" class="{{ $classes }}" {{ $attributes ?? '' }}>
        @isset($icon){!! $icon !!}@endisset
        {{ $label }}
    </button>
@endif
