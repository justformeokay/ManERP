@extends('layouts.app')

@section('content')
<div class="space-y-6">
    {{-- Tab Navigation: Products & Categories --}}
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex gap-6">
            <a href="{{ route('inventory.products.index') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition">{{ __('messages.products') }}</a>
            <a href="{{ route('inventory.categories.index') }}" class="border-primary-500 text-primary-600 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition">{{ __('messages.categories') }}</a>
        </nav>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ __('messages.products_categories') }}</h1>
            <p class="mt-2 text-gray-600">{{ __('messages.manage_categories') }}</p>
        </div>
        <a href="{{ route('inventory.categories.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            {{ __('messages.add_category') }}
        </a>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white px-6 py-4 rounded-lg border border-gray-200">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" placeholder="{{ __('messages.search_categories_placeholder') }}" value="{{ request('search') }}" 
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                {{ __('messages.search') }}
            </button>
            @if(request('search'))
                <a href="{{ route('inventory.categories.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    {{ __('messages.clear') }}
                </a>
            @endif
        </form>
    </div>

    <!-- Categories Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        @if($categories->count())
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">{{ __('messages.category_name_header') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">{{ __('messages.parent_header') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">{{ __('messages.subcategories_header') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">{{ __('messages.products_header') }}</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">{{ __('messages.actions_header') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($category->children_count > 0)
                                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    @else
                                        <div class="w-4"></div>
                                    @endif
                                    <span class="font-medium text-gray-900">{{ $category->name }}</span>
                                    @if($category->description)
                                        <span class="ml-2 text-sm text-gray-500">{{ Str::limit($category->description, 30) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($category->parent)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $category->parent->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($category->children_count > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $category->children_count }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="text-gray-600">{{ $category->products_count ?? 0 }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('inventory.categories.edit', $category) }}" 
                                        class="inline-flex items-center px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition">
                                        {{ __('messages.edit_btn') }}
                                    </a>
                                    <form method="POST" action="{{ route('inventory.categories.destroy', $category) }}" class="inline" 
                                        onsubmit="return confirm('{{ __('messages.delete_category_confirm') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 transition">
                                            {{ __('messages.delete_btn') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $categories->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('messages.no_data') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.manage_categories') }}</p>
                <div class="mt-6">
                    <a href="{{ route('inventory.categories.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        {{ __('messages.create_category_btn') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
