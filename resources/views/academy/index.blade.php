@extends('layouts.app')
@section('title', __('messages.academy_title'))

@section('breadcrumbs')
    <span class="text-gray-400">/</span>
    <span class="text-gray-700 font-medium">{{ __('messages.academy_title') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.academy_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.academy_subtitle') }}</p>
    </div>
@endsection

@section('content')
    {{-- Search --}}
    <div class="mb-6">
        <form method="GET" action="{{ route('academy.index') }}" class="max-w-md">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('messages.academy_search_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-4 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
        </form>
    </div>

    {{-- Category Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Glossary --}}
        <a href="{{ route('academy.glossary') }}" class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 hover:shadow-md hover:ring-primary-200 transition-all">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-100 transition">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">{{ __('messages.academy_glossary') }}</h3>
                    <p class="text-sm text-gray-500">{{ $stats['glossary'] }} {{ __('messages.academy_terms') }}</p>
                </div>
            </div>
            <p class="mt-3 text-sm text-gray-500">{{ __('messages.academy_glossary_desc') }}</p>
        </a>

        {{-- Workflows --}}
        <a href="{{ route('academy.workflows') }}" class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 hover:shadow-md hover:ring-primary-200 transition-all">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 transition">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">{{ __('messages.academy_workflows') }}</h3>
                    <p class="text-sm text-gray-500">{{ $stats['workflow'] }} {{ __('messages.academy_guides') }}</p>
                </div>
            </div>
            <p class="mt-3 text-sm text-gray-500">{{ __('messages.academy_workflows_desc') }}</p>
        </a>

        {{-- Tutorials --}}
        <div class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">{{ __('messages.academy_tutorials') }}</h3>
                    <p class="text-sm text-gray-500">{{ $stats['tutorial'] }} {{ __('messages.academy_articles') }}</p>
                </div>
            </div>
            <p class="mt-3 text-sm text-gray-500">{{ __('messages.academy_tutorials_desc') }}</p>
        </div>
    </div>

    {{-- All Articles by Category --}}
    @foreach(['glossary' => __('messages.academy_glossary'), 'workflow' => __('messages.academy_workflows'), 'tutorial' => __('messages.academy_tutorials')] as $cat => $label)
        @if(isset($articles[$cat]) && $articles[$cat]->isNotEmpty())
            <div class="mb-8">
                <h2 class="text-lg font-bold text-gray-900 mb-4">{{ $label }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($articles[$cat] as $article)
                        <a href="{{ route('academy.show', $article) }}"
                           class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100 hover:shadow-md hover:ring-primary-200 transition-all">
                            <div class="flex items-start gap-3">
                                @if($article->icon)
                                    <span class="text-xl">{{ $article->icon }}</span>
                                @endif
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $article->title }}</h3>
                                    <p class="mt-1 text-xs text-gray-500 line-clamp-2">{{ Str::limit(strip_tags($article->rendered_content), 100) }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    @if(collect($articles)->flatten()->isEmpty() && $search)
        <div class="rounded-2xl bg-white px-6 py-16 text-center shadow-sm ring-1 ring-gray-100">
            <svg class="mx-auto h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <h3 class="mt-3 text-sm font-medium text-gray-900">{{ __('messages.academy_no_results') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.academy_no_results_desc') }}</p>
        </div>
    @endif
@endsection
