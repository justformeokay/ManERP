@extends('layouts.app')

@section('title', __('messages.user_guide'))

@section('breadcrumbs')
    <span class="text-gray-400">/</span>
    <span class="text-gray-700 font-medium">{{ __('messages.user_guide') }}</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.user_guide') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.user_guide_desc') }}</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-5xl mx-auto">
        {{-- Hero Card --}}
        <div class="bg-gradient-to-br from-blue-600 via-blue-700 to-blue-900 rounded-2xl shadow-lg p-8 mb-8 text-white">
            <div class="flex items-center gap-4 mb-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 backdrop-blur">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-white">ManERP {{ __('messages.user_guide') }}</h2>
                    <p class="text-blue-100 text-lg mt-1">Master User Guide & SOP — v1.0</p>
                </div>
            </div>
            <p class="text-blue-50 max-w-3xl text-base leading-relaxed">
                @if(app()->getLocale() === 'id')
                    Dokumentasi lengkap mencakup navigasi sistem, keamanan, alur kerja bisnis inti, laporan keuangan, penggajian, dan administrasi. Tersedia dalam 4 bahasa: English, Bahasa Indonesia, 한국어, 中文.
                @elseif(app()->getLocale() === 'ko')
                    시스템 탐색, 보안, 핵심 비즈니스 워크플로우, 재무 보고서, 급여 및 관리를 다루는 종합 문서입니다. 4개 언어 지원: English, Bahasa Indonesia, 한국어, 中文.
                @elseif(app()->getLocale() === 'zh')
                    综合文档涵盖系统导航、安全、核心业务流程、财务报告、薪资和管理。支持4种语言：English、Bahasa Indonesia、한국어、中文。
                @else
                    Comprehensive documentation covering system navigation, security, core business workflows, financial reports, payroll, and administration. Available in 4 languages: English, Bahasa Indonesia, 한국어, 中文.
                @endif
            </p>
        </div>

        {{-- Chapter Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($chapters as $ch)
                @php
                    $icons = [
                        1 => ['icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'blue'],
                        2 => ['icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'color' => 'red'],
                        3 => ['icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'color' => 'green'],
                        4 => ['icon' => 'M9 7h6m0 10v-3m-3 3v-6m-3 6v-1m6-9a2 2 0 002-2V5a2 2 0 00-2-2H9a2 2 0 00-2 2v2a2 2 0 002 2zm-3 4H4a2 2 0 00-2 2v4a2 2 0 002 2h2m14-6h-2a2 2 0 00-2 2v4a2 2 0 002 2h2a2 2 0 002-2v-4a2 2 0 00-2-2z', 'color' => 'purple'],
                        5 => ['icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'amber'],
                        6 => ['icon' => 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129', 'color' => 'teal'],
                    ];
                    $meta = $icons[$ch['number']] ?? ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'gray'];
                    $colorMap = [
                        'blue'   => 'bg-blue-50 text-blue-600 ring-blue-200',
                        'red'    => 'bg-red-50 text-red-600 ring-red-200',
                        'green'  => 'bg-green-50 text-green-600 ring-green-200',
                        'purple' => 'bg-purple-50 text-purple-600 ring-purple-200',
                        'amber'  => 'bg-amber-50 text-amber-600 ring-amber-200',
                        'teal'   => 'bg-teal-50 text-teal-600 ring-teal-200',
                        'gray'   => 'bg-gray-50 text-gray-600 ring-gray-200',
                    ];
                @endphp
                <a href="{{ route('user-guide.show', $ch['slug']) }}"
                   class="group block bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6 hover:shadow-md hover:ring-gray-200 transition-all">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ring-1 {{ $colorMap[$meta['color']] }}">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">
                                {{ __('messages.chapter') }} {{ $ch['number'] }}
                            </p>
                            <h3 class="text-base font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                                {{ $ch['label'] }}
                            </h3>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm text-primary-600 font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                        <span>
                            @if(app()->getLocale() === 'id') Baca selengkapnya
                            @elseif(app()->getLocale() === 'ko') 자세히 읽기
                            @elseif(app()->getLocale() === 'zh') 阅读更多
                            @else Read more
                            @endif
                        </span>
                        <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endsection
