@extends('layouts.app')

@section('content')
<div class="max-w-2xl">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
            {{ $category->exists ? __('messages.edit_category') : __('messages.create_category') }}
        </h1>
        <p class="mt-2 text-gray-600">
            {{ $category->exists ? __('messages.update_category_information') : __('messages.add_new_product_category') }}
        </p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <form method="POST" action="{{ $category->exists ? route('inventory.categories.update', $category) : route('inventory.categories.store') }}">
            @csrf
            @if($category->exists)
                @method('PUT')
            @endif

            <!-- Basic Info Section -->
            <div class="space-y-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 border-b pb-3">{{ __('messages.basic_information') }}</h2>

                <!-- Category Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.category_name') }}</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $category->name ?? '') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                        placeholder="{{ __('messages.category_name_placeholder') }}">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug -->
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.slug_label') }}</label>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $category->slug ?? '') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('slug') border-red-500 @enderror"
                        placeholder="{{ __('messages.slug_placeholder') }}">
                    <p class="mt-1 text-xs text-gray-500">{{ __('messages.auto_generate_slug') }}</p>
                    @error('slug')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.description_label') }}</label>
                    <textarea id="description" name="description" rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                        placeholder="{{ __('messages.description_placeholder') }}">{{ old('description', $category->description ?? '') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Hierarchy Section -->
            <div class="space-y-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 border-b pb-3">{{ __('messages.category_hierarchy') }}</h2>

                <!-- Parent Category -->
                <div>
                    <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.parent_category') }}</label>
                    <select id="parent_id" name="parent_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('parent_id') border-red-500 @enderror">
                        <option value="">{{ __('messages.parent_category_none') }}</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id ?? null) == $parent->id ? 'selected' : '' }}>
                                {{ str_repeat('— ', $parent->depth ?? 0) }}{{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">{{ __('messages.parent_category_instruction') }}</p>
                    @error('parent_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <a href="{{ route('inventory.categories.index') }}" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    {{ __('messages.cancel') }}
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    {{ $category->exists ? __('messages.update_category_btn') : __('messages.create_category_btn') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
