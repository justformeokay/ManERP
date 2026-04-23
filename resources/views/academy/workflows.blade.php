@extends('layouts.app')
@section('title', __('messages.academy_workflows'))

@section('breadcrumbs')
    <span class="text-gray-400">/</span>
    <a href="{{ route('academy.index') }}" class="text-primary-600 hover:text-primary-700">{{ __('messages.academy_title') }}</a>
    <span class="text-gray-400">/</span>
    <span class="text-gray-700 font-medium">{{ __('messages.academy_workflows') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.academy_workflows') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.academy_workflows_page_desc') }}</p>
    </div>
@endsection

@section('content')
    @forelse($articles as $article)
        <div class="mb-8 rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            {{-- Header --}}
            <div class="border-b border-gray-100 px-6 py-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    @if($article->icon)
                        <span class="text-lg">{{ $article->icon }}</span>
                    @else
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    @endif
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">{{ $article->title }}</h2>
                </div>
                <a href="{{ route('academy.show', $article) }}" class="ml-auto text-xs text-primary-600 hover:text-primary-700 font-medium">
                    {{ __('messages.academy_read_more') }} →
                </a>
            </div>

            {{-- Workflow Stepper Visualization --}}
            <div class="px-6 py-6">
                @php
                    // Extract steps from content: lines starting with "**Step N:" or "### Step" or numbered list
                    $lines = collect(explode("\n", $article->content));
                    $steps = $lines->filter(function ($line) {
                        return preg_match('/^(\d+\.\s+\*\*|###?\s+Step|\*\*Step\s+\d)/i', trim($line));
                    })->map(function ($line) {
                        // Clean markdown bold/heading markers
                        $clean = preg_replace('/^(\d+\.\s+|\#{1,3}\s+)/', '', trim($line));
                        $clean = str_replace(['**', '*'], '', $clean);
                        return trim($clean);
                    })->values();
                @endphp

                @if($steps->count() >= 2)
                    {{-- Stepper UI --}}
                    <div class="flex items-start gap-0 overflow-x-auto pb-2">
                        @foreach($steps as $i => $step)
                            <div class="flex items-start shrink-0 {{ !$loop->last ? 'flex-1' : '' }}">
                                {{-- Step circle + label --}}
                                <div class="flex flex-col items-center text-center" style="min-width: 100px; max-width: 160px;">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full
                                        {{ $loop->first ? 'bg-primary-600 text-white' : ($loop->last ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-700') }}
                                        text-sm font-bold shadow-sm">
                                        {{ $i + 1 }}
                                    </div>
                                    <p class="mt-2 text-xs font-medium text-gray-700 leading-tight px-1">{{ Str::limit($step, 60) }}</p>
                                </div>
                                {{-- Connector line --}}
                                @if(!$loop->last)
                                    <div class="flex-1 flex items-center mt-5 px-1">
                                        <div class="h-0.5 w-full bg-gray-200 relative">
                                            <svg class="absolute -right-1 -top-1.5 h-3 w-3 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Fallback: render first 200 chars of content --}}
                    <p class="text-sm text-gray-600">{{ Str::limit(strip_tags($article->rendered_content), 200) }}</p>
                @endif
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white px-6 py-16 text-center shadow-sm ring-1 ring-gray-100">
            <svg class="mx-auto h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <h3 class="mt-3 text-sm font-medium text-gray-900">{{ __('messages.academy_no_workflows') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.academy_no_workflows_desc') }}</p>
        </div>
    @endforelse
@endsection
