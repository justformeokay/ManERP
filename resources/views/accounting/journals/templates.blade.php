@extends('layouts.app')

@section('title', __('messages.journal_templates_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.journals.index') }}" class="hover:text-gray-700">{{ __('messages.journal_entries') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.journal_templates_title') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.journal_templates_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.journal_templates_subtitle') }}</p>
        </div>
        @include('components.button', ['label' => __('messages.create_template'), 'type' => 'primary', 'href' => route('accounting.journals.templates.create')])
    </div>
@endsection

@section('content')
    {{-- Tab Navigation: Journals & Templates --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6">
            <a href="{{ route('accounting.journals.index') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition">{{ __('messages.journal_entries') }}</a>
            <a href="{{ route('accounting.journals.templates') }}" class="border-primary-500 text-primary-600 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition">{{ __('messages.journal_templates_title') }}</a>
        </nav>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($templates as $tpl)
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 hover:ring-primary-200 transition group">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 group-hover:text-primary-700 transition">{{ $tpl->name }}</h4>
                        @if($tpl->description)
                            <p class="mt-0.5 text-xs text-gray-500">{{ $tpl->description }}</p>
                        @endif
                    </div>
                    @if($tpl->is_active)
                        <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/10">{{ __('messages.active') }}</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-500/10">{{ __('messages.inactive') }}</span>
                    @endif
                </div>

                {{-- Template Lines Preview --}}
                <div class="border-t border-gray-100 pt-3 space-y-1">
                    @foreach(($tpl->items ?? []) as $item)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600">{{ $item['account_code'] ?? '' }} {{ $item['account_name'] ?? '' }}</span>
                            <span class="font-mono">
                                @if(($item['debit'] ?? 0) > 0)
                                    <span class="text-gray-900">D {{ number_format($item['debit'], 0) }}</span>
                                @else
                                    <span class="text-gray-500">C {{ number_format($item['credit'] ?? 0, 0) }}</span>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Actions --}}
                <div class="mt-4 border-t border-gray-100 pt-3 flex items-center justify-between">
                    <span class="text-xs text-gray-400">{{ $tpl->creator->name ?? '-' }}</span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('accounting.journals.create') }}?template={{ $tpl->id }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">{{ __('messages.use_template') }}</a>
                        <form method="POST" action="{{ route('accounting.journals.templates.destroy', $tpl) }}" class="inline" x-data
                              @submit.prevent="if(confirm('{{ __('messages.confirm_delete_template') }}')) $el.submit()">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700">{{ __('messages.delete') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-100">
                <p class="text-sm text-gray-400">{{ __('messages.no_templates') }}</p>
                <a href="{{ route('accounting.journals.templates.create') }}" class="mt-2 inline-block text-sm font-medium text-primary-600 hover:text-primary-700">{{ __('messages.create_first_template') }}</a>
            </div>
        @endforelse
    </div>
@endsection
