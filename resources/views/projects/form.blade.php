@extends('layouts.app')

@php
    $isEdit = $project->exists;
    $pageTitle = $isEdit ? __('messages.edit_project') : __('messages.new_project');
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-700">{{ __('messages.projects_heading') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $isEdit ? __('messages.project_form_edit_subtitle') : __('messages.project_form_create_subtitle') }}
        </p>
    </div>
@endsection

@section('content')
    <form method="POST"
          action="{{ $isEdit ? route('projects.update', $project) : route('projects.store') }}"
          class="space-y-6"
          x-data="{ projectType: '{{ old('type', $project->type ?? 'sales') }}' }">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Project Type Selector --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('messages.project_type_label') }} <span class="text-red-500">*</span></h3>
            <p class="text-sm text-gray-500 mb-4">{{ __('messages.project_type_hint') }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {{-- Sales --}}
                <label class="relative cursor-pointer rounded-xl border-2 p-4 transition-all"
                       :class="projectType === 'sales' ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-200' : 'border-gray-200 bg-gray-50 hover:border-gray-300'">
                    <input type="radio" name="type" value="sales" class="sr-only" x-model="projectType">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                             :class="projectType === 'sales' ? 'bg-blue-100 text-blue-600' : 'bg-gray-200 text-gray-500'">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold" :class="projectType === 'sales' ? 'text-blue-900' : 'text-gray-700'">{{ __('messages.project_type_sales') }}</p>
                            <p class="text-xs" :class="projectType === 'sales' ? 'text-blue-600' : 'text-gray-500'">{{ __('messages.project_type_sales_desc') }}</p>
                        </div>
                    </div>
                    <div x-show="projectType === 'sales'" class="absolute top-3 right-3">
                        <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    </div>
                </label>

                {{-- Internal CAPEX --}}
                <label class="relative cursor-pointer rounded-xl border-2 p-4 transition-all"
                       :class="projectType === 'internal_capex' ? 'border-violet-500 bg-violet-50/50 ring-1 ring-violet-200' : 'border-gray-200 bg-gray-50 hover:border-gray-300'">
                    <input type="radio" name="type" value="internal_capex" class="sr-only" x-model="projectType">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                             :class="projectType === 'internal_capex' ? 'bg-violet-100 text-violet-600' : 'bg-gray-200 text-gray-500'">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold" :class="projectType === 'internal_capex' ? 'text-violet-900' : 'text-gray-700'">{{ __('messages.project_type_internal_capex') }}</p>
                            <p class="text-xs" :class="projectType === 'internal_capex' ? 'text-violet-600' : 'text-gray-500'">{{ __('messages.project_type_capex_desc') }}</p>
                        </div>
                    </div>
                    <div x-show="projectType === 'internal_capex'" class="absolute top-3 right-3">
                        <svg class="h-5 w-5 text-violet-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    </div>
                </label>
            </div>
            @error('type') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Project Details --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.project_details') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.project_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $project->name) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('name') border-red-300 @enderror"
                        placeholder="{{ __('messages.project_name_placeholder') }}">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Client — show only for Sales type --}}
                <div x-show="projectType === 'sales'" x-transition>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.client') }} <span class="text-red-500">*</span></label>
                    <select id="client_id" name="client_id"
                        :required="projectType === 'sales'"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('client_id') border-red-300 @enderror">
                        <option value="">{{ __('messages.select_client') }}</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('client_id', $project->client_id) == $client->id)>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.project_manager') }}</label>
                    <select id="manager_id" name="manager_id"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('manager_id') border-red-300 @enderror">
                        <option value="">{{ __('messages.no_manager') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('manager_id', $project->manager_id) == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('manager_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.status') }} <span class="text-red-500">*</span></label>
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
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                    <textarea id="description" name="description" rows="3"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="{{ __('messages.project_desc_placeholder') }}">{{ old('description', $project->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Timeline & Budget --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.timeline_and_budget') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.start_date') }} <span class="text-red-500">*</span></label>
                    <input type="date" id="start_date" name="start_date"
                        value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('start_date') border-red-300 @enderror">
                    @error('start_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.end_date') }}</label>
                    <input type="date" id="end_date" name="end_date"
                        value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('end_date') border-red-300 @enderror">
                    @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="budget" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.budget') }} ({{ currency_symbol() }})</label>
                    <input type="text" id="budget" name="budget" x-currency
                        value="{{ old('budget', $project->budget) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="0">
                </div>

                <div>
                    <label for="estimated_budget" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.estimated_budget') }} ({{ currency_symbol() }})</label>
                    <input type="text" id="estimated_budget" name="estimated_budget" x-currency
                        value="{{ old('estimated_budget', $project->estimated_budget) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="0">
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.notes') }}</h3>
            <textarea id="notes" name="notes" rows="3"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                placeholder="{{ __('messages.project_notes_placeholder') }}">{{ old('notes', $project->notes) }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => __('messages.cancel'), 'type' => 'secondary', 'href' => route('projects.index')])
            @include('components.button', [
                'label' => $isEdit ? __('messages.update_project') : __('messages.create_project'),
                'type' => 'primary',
                'buttonType' => 'submit',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            ])
        </div>
    </form>
@endsection
