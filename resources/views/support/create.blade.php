@extends('layouts.app')

@section('title', __('messages.new_ticket'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('support.index') }}" class="hover:text-gray-700">{{ __('messages.support_tickets') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.new_ticket') }}</span>
@endsection

@section('page-header')
    <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.new_ticket') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('messages.new_ticket_subtitle') }}</p>
@endsection

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <form action="{{ route('support.store') }}" method="POST">
                @csrf
                <div class="space-y-5">
                    {{-- Title --}}
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">{{ __('messages.ticket_title') }} <span class="text-red-500">*</span></label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}" required
                               class="mt-1 block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                               placeholder="{{ __('messages.ticket_title_placeholder') }}">
                        @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">{{ __('messages.ticket_category') }} <span class="text-red-500">*</span></label>
                        <select name="category" id="category" required
                                class="mt-1 block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">-- {{ __('messages.select') }} --</option>
                            @foreach(\App\Models\SupportTicket::categories() as $cat)
                                <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ __('messages.cat_' . $cat) }}</option>
                            @endforeach
                        </select>
                        @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Priority --}}
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700">{{ __('messages.ticket_priority') }} <span class="text-red-500">*</span></label>
                        <select name="priority" id="priority" required
                                class="mt-1 block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">-- {{ __('messages.select') }} --</option>
                            @foreach(\App\Models\SupportTicket::priorities() as $prio)
                                <option value="{{ $prio }}" {{ old('priority') === $prio ? 'selected' : '' }}>{{ __('messages.priority_' . $prio) }}</option>
                            @endforeach
                        </select>
                        @error('priority') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">{{ __('messages.ticket_description') }} <span class="text-red-500">*</span></label>
                        <textarea name="description" id="description" rows="6" required
                                  class="mt-1 block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                  placeholder="{{ __('messages.ticket_description_placeholder') }}">{{ old('description') }}</textarea>
                        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3 justify-end">
                    <a href="{{ route('support.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
                    @include('components.button', [
                        'label' => __('messages.submit_ticket'),
                        'type' => 'primary',
                        'buttonType' => 'submit',
                    ])
                </div>
            </form>
        </div>
    </div>
@endsection
