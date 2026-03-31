@extends('layouts.app')

@php
    $isEdit = $project->exists;
    $pageTitle = $isEdit ? 'Edit Project' : 'New Project';
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-700">Projects</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $isEdit ? 'Update project details.' : 'Create a new project and assign it to a client.' }}
        </p>
    </div>
@endsection

@section('content')
    <form method="POST"
          action="{{ $isEdit ? route('projects.update', $project) : route('projects.store') }}"
          class="space-y-6">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Project Details --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Project Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Project Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $project->name) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('name') border-red-300 @enderror"
                        placeholder="e.g. Warehouse Automation Phase 1">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                    <select id="client_id" name="client_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('client_id') border-red-300 @enderror">
                        <option value="">Select client...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('client_id', $project->client_id) == $client->id)>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-1">Project Manager</label>
                    <select id="manager_id" name="manager_id"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('manager_id') @enderror">
                        <option value="">No manager assigned</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('manager_id', $project->manager_id) == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('manager_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        @foreach(\App\Models\Project::statusOptions() as $status)
                            <option value="{{ $status }}" @selected(old('status', $project->status) === $status)>
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="3"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="Brief description of the project scope...">{{ old('description', $project->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Timeline & Budget --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Timeline & Budget</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
                    <input type="date" id="start_date" name="start_date"
                        value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('start_date') border-red-300 @enderror">
                    @error('start_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" id="end_date" name="end_date"
                        value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('end_date') border-red-300 @enderror">
                    @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="budget" class="block text-sm font-medium text-gray-700 mb-1">Budget ($)</label>
                    <input type="number" id="budget" name="budget" step="0.01" min="0"
                        value="{{ old('budget', $project->budget) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="0.00">
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Notes</h3>
            <textarea id="notes" name="notes" rows="3"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                placeholder="Internal notes...">{{ old('notes', $project->notes) }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => 'Cancel', 'type' => 'secondary', 'href' => route('projects.index')])
            @include('components.button', [
                'label' => $isEdit ? 'Update Project' : 'Create Project',
                'type' => 'primary',
                'buttonType' => 'submit',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            ])
        </div>
    </form>
@endsection
