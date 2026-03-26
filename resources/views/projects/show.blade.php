@extends('layouts.app')

@section('title', $project->name)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-700">Projects</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $project->code }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">{{ $project->name }}</h1>
                @php $colors = \App\Models\Project::statusColors(); @endphp
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $colors[$project->status] ?? $colors['draft'] }}">
                    {{ ucwords(str_replace('_', ' ', $project->status)) }}
                </span>
            </div>
            <p class="mt-1 text-sm text-gray-500">{{ $project->code }}</p>
        </div>
        <div class="flex gap-2">
            @include('components.button', [
                'label' => 'Edit',
                'type' => 'secondary',
                'href' => route('projects.edit', $project),
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
            ])
            @include('components.button', ['label' => 'Back', 'type' => 'ghost', 'href' => route('projects.index')])
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="xl:col-span-2 space-y-6">
            {{-- Description --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Description</h3>
                <p class="text-sm text-gray-700 leading-relaxed">
                    {{ $project->description ?: 'No description provided.' }}
                </p>
            </div>

            {{-- Timeline --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Timeline & Budget</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $project->start_date?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $project->end_date?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $project->budget ? '$' . number_format($project->budget, 2) : '—' }}</p>
                    </div>
                </div>

                @if($project->start_date && $project->end_date)
                    @php
                        $totalDays = $project->start_date->diffInDays($project->end_date);
                        $elapsed = $project->start_date->diffInDays(now());
                        $progress = $totalDays > 0 ? min(100, round(($elapsed / $totalDays) * 100)) : 0;
                    @endphp
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                            <span>Timeline Progress</span>
                            <span>{{ $progress }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100">
                            <div class="h-2 rounded-full {{ $progress >= 100 ? 'bg-red-500' : 'bg-primary-500' }}" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Notes --}}
            @if($project->notes)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">Notes</h3>
                    <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $project->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-6">
            {{-- Client --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Client</h3>
                @if($project->client)
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700 font-semibold text-sm">
                            {{ strtoupper(substr($project->client->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $project->client->name }}</p>
                            <p class="text-xs text-gray-500">{{ $project->client->company ?? $project->client->code }}</p>
                        </div>
                    </div>
                    @if($project->client->email)
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <p class="text-xs text-gray-500">{{ $project->client->email }}</p>
                            @if($project->client->phone)
                                <p class="text-xs text-gray-500">{{ $project->client->phone }}</p>
                            @endif
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-400">No client assigned.</p>
                @endif
            </div>

            {{-- Details --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Details</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Code</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $project->code }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Status</dt>
                        <dd>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $colors[$project->status] ?? $colors['draft'] }}">
                                {{ ucwords(str_replace('_', ' ', $project->status)) }}
                            </span>
                        </dd>
                    </div>
                    @if($project->manager)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Manager</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $project->manager->name }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Created</dt>
                        <dd class="text-sm text-gray-900">{{ $project->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Updated</dt>
                        <dd class="text-sm text-gray-900">{{ $project->updated_at->format('M d, Y') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Danger Zone --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-red-100">
                <h3 class="text-base font-semibold text-red-600 mb-3">Danger Zone</h3>
                <p class="text-sm text-gray-500 mb-4">Deleting this project is irreversible.</p>
                <form method="POST" action="{{ route('projects.destroy', $project) }}"
                      onsubmit="return confirm('Are you sure you want to delete this project?')">
                    @csrf @method('DELETE')
                    @include('components.button', ['label' => 'Delete Project', 'type' => 'danger', 'buttonType' => 'submit'])
                </form>
            </div>
        </div>
    </div>
@endsection
