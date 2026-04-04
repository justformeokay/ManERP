@extends('layouts.app')

@section('title', __('messages.user_guide'))

@section('breadcrumbs')
    <span class="text-gray-400">/</span>
    <a href="{{ route('user-guide.index') }}" class="text-primary-600 hover:text-primary-700 font-medium">{{ __('messages.user_guide') }}</a>
    <span class="text-gray-400">/</span>
    <span class="text-gray-700 font-medium">{{ __('messages.chapter') }} {{ (int) substr($chapter, 0, 2) }}</span>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto flex gap-8">
        {{-- Sidebar Navigation --}}
        <aside class="hidden lg:block w-64 shrink-0">
            <div class="sticky top-24">
                <nav class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-4 space-y-1">
                    <a href="{{ route('user-guide.index') }}"
                       class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ __('messages.back_to_toc') }}
                    </a>
                    <hr class="my-2 border-gray-100">
                    @foreach($chapters as $ch)
                        <a href="{{ route('user-guide.show', $ch['slug']) }}"
                           class="block rounded-lg px-3 py-2 text-sm font-medium transition-colors
                               {{ $ch['slug'] === $chapter ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <span class="text-xs text-gray-400 mr-1">{{ $ch['number'] }}.</span>
                            {{ $ch['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 min-w-0">
            {{-- Mobile chapter nav --}}
            <div class="lg:hidden mb-4">
                <a href="{{ route('user-guide.index') }}"
                   class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 font-medium">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('messages.back_to_toc') }}
                </a>
            </div>

            <article class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6 sm:p-8 lg:p-10">
                <div class="prose prose-gray max-w-none
                            prose-headings:font-bold prose-headings:text-gray-900
                            prose-h1:text-2xl prose-h1:border-b prose-h1:border-gray-200 prose-h1:pb-3 prose-h1:mb-6
                            prose-h2:text-xl prose-h2:mt-10 prose-h2:mb-4 prose-h2:text-primary-700
                            prose-h3:text-lg prose-h3:mt-8 prose-h3:mb-3
                            prose-table:text-sm prose-th:bg-gray-50 prose-th:font-semibold
                            prose-td:border-gray-200 prose-th:border-gray-200
                            prose-a:text-primary-600 prose-a:no-underline hover:prose-a:underline
                            prose-code:bg-gray-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-sm prose-code:font-mono
                            prose-blockquote:border-primary-300 prose-blockquote:bg-primary-50/50 prose-blockquote:rounded-r-lg prose-blockquote:py-1
                            prose-strong:text-gray-900
                            prose-img:rounded-xl prose-img:shadow-md">
                    {!! \Illuminate\Support\Str::markdown($markdown, [
                        'html_input' => 'strip',
                        'allow_unsafe_links' => false,
                    ]) !!}
                </div>
            </article>

            {{-- Prev / Next navigation --}}
            @php
                $currentIdx = null;
                foreach ($chapters as $i => $ch) {
                    if ($ch['slug'] === $chapter) { $currentIdx = $i; break; }
                }
                $prev = $currentIdx !== null && $currentIdx > 0 ? $chapters[$currentIdx - 1] : null;
                $next = $currentIdx !== null && $currentIdx < count($chapters) - 1 ? $chapters[$currentIdx + 1] : null;
            @endphp
            <div class="flex items-center justify-between mt-6">
                @if($prev)
                    <a href="{{ route('user-guide.show', $prev['slug']) }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        {{ $prev['label'] }}
                    </a>
                @else
                    <div></div>
                @endif
                @if($next)
                    <a href="{{ route('user-guide.show', $next['slug']) }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50 transition">
                        {{ $next['label'] }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @else
                    <div></div>
                @endif
            </div>
        </div>
    </div>
@endsection
