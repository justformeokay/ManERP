@extends('layouts.app')
@section('title', $article->title)

@section('breadcrumbs')
    <span class="text-gray-400">/</span>
    <a href="{{ route('academy.index') }}" class="text-primary-600 hover:text-primary-700">{{ __('messages.academy_title') }}</a>
    <span class="text-gray-400">/</span>
    @if($article->category === 'glossary')
        <a href="{{ route('academy.glossary') }}" class="text-primary-600 hover:text-primary-700">{{ __('messages.academy_glossary') }}</a>
    @elseif($article->category === 'workflow')
        <a href="{{ route('academy.workflows') }}" class="text-primary-600 hover:text-primary-700">{{ __('messages.academy_workflows') }}</a>
    @else
        <span class="text-gray-600">{{ __('messages.academy_tutorials') }}</span>
    @endif
    <span class="text-gray-400">/</span>
    <span class="text-gray-700 font-medium">{{ Str::limit($article->title, 40) }}</span>
@endsection

@section('page-header')
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                @if($article->category === 'glossary') bg-blue-50 text-blue-700
                @elseif($article->category === 'workflow') bg-emerald-50 text-emerald-700
                @else bg-amber-50 text-amber-700 @endif">
                {{ __('messages.academy_cat_' . $article->category) }}
            </span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">
            @if($article->icon) {{ $article->icon }} @endif
            {{ $article->title }}
        </h1>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-3">
            <div class="rounded-2xl bg-white p-6 sm:p-8 shadow-sm ring-1 ring-gray-100">
                <div class="prose prose-gray max-w-none prose-headings:font-bold prose-headings:text-gray-900
                            prose-p:text-gray-600 prose-a:text-primary-600 prose-a:no-underline hover:prose-a:underline
                            prose-strong:text-gray-900 prose-code:text-primary-700 prose-code:bg-primary-50 prose-code:rounded prose-code:px-1.5 prose-code:py-0.5
                            prose-li:text-gray-600 prose-blockquote:border-primary-300 prose-blockquote:text-gray-600
                            prose-table:text-sm prose-th:bg-gray-50 prose-th:text-gray-700 prose-td:text-gray-600">
                    {!! $article->rendered_content !!}
                </div>
            </div>
        </div>

        {{-- Sidebar: Related Articles --}}
        <div class="lg:col-span-1">
            @if($related->isNotEmpty())
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 sticky top-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('messages.academy_related') }}</h3>
                    <div class="space-y-2">
                        @foreach($related as $rel)
                            <a href="{{ route('academy.show', $rel) }}"
                               class="block rounded-lg p-2.5 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition">
                                @if($rel->icon) {{ $rel->icon }} @endif
                                {{ $rel->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
