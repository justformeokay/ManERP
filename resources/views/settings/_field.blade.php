@php
    $type     = $type ?? 'text';
    $value    = $value ?? '';
    $tooltip  = $tooltip ?? '';
    $suffix   = $suffix ?? '';
    $step     = $step ?? null;
    $min      = $min ?? null;
    $max      = $max ?? null;
    $currency = $currency ?? false;
@endphp

<div>
    <label for="{{ $name }}" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
        {{ $label }}
        @if($tooltip)
            @include('settings._tooltip', ['text' => $tooltip])
        @endif
    </label>
    <div class="{{ $suffix ? 'flex items-center gap-2' : '' }}">
        <input type="{{ $currency ? 'text' : $type }}" id="{{ $name }}" name="{{ $name }}"
            value="{{ old($name, $value) }}"
            @if($currency) x-currency @endif
            @if($step && !$currency) step="{{ $step }}" @endif
            @if(!is_null($min) && !$currency) min="{{ $min }}" @endif
            @if(!is_null($max) && !$currency) max="{{ $max }}" @endif
            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 transition @error($name) border-red-300 @enderror">
        @if($suffix)
            <span class="text-sm text-gray-500 whitespace-nowrap">{{ $suffix }}</span>
        @endif
    </div>
    @error($name) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>
