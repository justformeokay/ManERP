@extends('layouts.app')
@section('title', __('messages.academy_glossary'))

@section('breadcrumbs')
    <span class="text-gray-400">/</span>
    <a href="{{ route('academy.index') }}" class="text-primary-600 hover:text-primary-700">{{ __('messages.academy_title') }}</a>
    <span class="text-gray-400">/</span>
    <span class="text-gray-700 font-medium">{{ __('messages.academy_glossary') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.academy_glossary') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.academy_glossary_page_desc') }}</p>
    </div>
@endsection

@section('content')
    {{-- Search + A-Z Filter --}}
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <form method="GET" action="{{ route('academy.glossary') }}" class="flex-1 max-w-md">
            @if($letter) <input type="hidden" name="letter" value="{{ $letter }}"> @endif
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('messages.academy_search_glossary') }}"
                    class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-4 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
        </form>
    </div>

    {{-- Alphabet Navigation --}}
    <div class="flex flex-wrap gap-1 mb-6">
        <a href="{{ route('academy.glossary', ['search' => $search]) }}"
           class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-medium transition
               {{ !$letter ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' }}">
            {{ __('messages.all') }}
        </a>
        @foreach($allLetters as $l)
            <a href="{{ route('academy.glossary', ['letter' => $l, 'search' => $search]) }}"
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-medium transition
                   {{ $letter === $l ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' }}">
                {{ $l }}
            </a>
        @endforeach
    </div>

    {{-- Terms grouped by letter --}}
    @forelse($grouped as $firstLetter => $terms)
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-sm font-bold text-primary-700">{{ $firstLetter }}</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>
            <div class="space-y-2">
                @foreach($terms as $term)
                    <a href="{{ route('academy.show', $term) }}"
                       class="block rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100 hover:shadow-md hover:ring-primary-200 transition-all">
                        <div class="flex items-start gap-3">
                            @if($term->icon)
                                <span class="text-lg mt-0.5">{{ $term->icon }}</span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-semibold text-gray-900">{{ $term->title }}</h3>
                                <p class="mt-1 text-xs text-gray-500 line-clamp-2">{{ Str::limit(strip_tags($term->rendered_content), 150) }}</p>
                            </div>
                            <svg class="h-4 w-4 text-gray-400 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white px-6 py-16 text-center shadow-sm ring-1 ring-gray-100">
            <svg class="mx-auto h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <h3 class="mt-3 text-sm font-medium text-gray-900">{{ __('messages.academy_no_results') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.academy_no_results_desc') }}</p>
        </div>
    @endforelse
@endsection
