@extends('layouts.app')

@section('title', __('messages.projects_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.projects_heading') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.projects_heading') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.projects_subtitle') }}</p>
        </div>
        @include('components.button', [
            'label' => __('messages.new_project'),
            'type' => 'primary',
            'href' => route('projects.create'),
            'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
        ])
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" action="{{ route('projects.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('messages.search_projects_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition" />
            </div>
            <select name="status" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_status') }}</option>
                @foreach(\App\Models\Project::statusOptions() as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <select name="type" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_types') }}</option>
                @foreach(\App\Models\Project::typeOptions() as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('messages.project_type_' . $type) }}</option>
                @endforeach
            </select>
            <select name="client_id" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_clients') }}</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                @include('components.button', ['label' => __('messages.filter'), 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search', 'status', 'type', 'client_id']))
                    @include('components.button', ['label' => __('messages.clear'), 'type' => 'ghost', 'href' => route('projects.index')])
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.project_column') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.type_column') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.client_column') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.timeline') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.budget') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($projects as $project)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('projects.show', $project) }}" class="group">
                                    <p class="text-sm font-medium text-gray-900 group-hover:text-primary-600 transition">{{ $project->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $project->code }}</p>
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php $typeColors = \App\Models\Project::typeColors(); @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $typeColors[$project->type] ?? $typeColors['sales'] }}">
                                    {{ $project->typeLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($project->client)
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 text-xs font-semibold">
                                            {{ strtoupper(substr($project->client->name, 0, 2)) }}
                                        </div>
                                        <span class="text-sm text-gray-700">{{ $project->client->name }}</span>
                                    </div>
                                @elseif($project->isCapex())
                                    <span class="text-sm text-gray-500 italic">{{ __('messages.internal_project') }}</span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php $colors = \App\Models\Project::statusColors(); @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $colors[$project->status] ?? $colors['draft'] }}">
                                    {{ ucwords(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                <p>{{ $project->start_date?->format('M d, Y') ?? '—' }}</p>
                                @if($project->end_date)
                                    <p class="text-xs text-gray-500">→ {{ $project->end_date->format('M d, Y') }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ $project->budget ? format_currency($project->budget) : '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm space-x-1">
                                <a href="{{ route('projects.show', $project) }}"
                                   class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 transition">{{ __('messages.view') }}</a>
                                <a href="{{ route('projects.edit', $project) }}"
                                   class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">{{ __('messages.edit') }}</a>
                                <form method="POST" action="{{ route('projects.destroy', $project) }}" class="inline"
                                      onsubmit="return confirm('{{ __('messages.delete_project_confirm', ['name' => $project->name]) }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 transition">{{ __('messages.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                @include('components.empty-state-guide', [
                                    'icon' => 'clipboard',
                                    'title' => __('messages.no_projects_found'),
                                    'description' => __('messages.academy_empty_projects_hint'),
                                    'cta_url' => route('projects.create'),
                                    'cta_label' => __('messages.create_your_first_project'),
                                    'academy_slug' => 'pr-vs-po',
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($projects->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
@endsection
