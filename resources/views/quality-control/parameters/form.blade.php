@extends('layouts.app')

@section('title', $parameter->exists ? __('messages.edit_parameter_title') : __('messages.create_parameter_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('qc.parameters.index') }}" class="hover:text-gray-700">{{ __('messages.qc_parameters_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $parameter->exists ? __('messages.edit_btn') : __('messages.create_btn') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            {{ $parameter->exists ? __('messages.edit_parameter_title') : __('messages.create_parameter_title') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $parameter->exists ? __('messages.edit_parameter_subtitle') : __('messages.create_parameter_subtitle') }}
        </p>
    </div>
@endsection

@section('content')
    <form action="{{ $parameter->exists ? route('qc.parameters.update', $parameter) : route('qc.parameters.store') }}"
          method="POST" class="max-w-2xl space-y-6">
        @csrf
        @if($parameter->exists) @method('PUT') @endif

        {{-- Parameter Details --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-5">
            <h3 class="text-base font-semibold text-gray-900">{{ __('messages.parameter_details_section') }}</h3>

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.parameter_name_label') }} <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $parameter->name) }}" required
                    placeholder="{{ __('messages.parameter_name_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Type --}}
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.parameter_type_label') }} <span class="text-red-500">*</span></label>
                <select id="type" name="type" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @foreach(\App\Models\QcParameter::typeOptions() as $t)
                        <option value="{{ $t }}" @selected(old('type', $parameter->type) === $t)>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
                @error('type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Unit --}}
            <div>
                <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.parameter_unit_label') }}</label>
                <input type="text" id="unit" name="unit" value="{{ old('unit', $parameter->unit) }}"
                    placeholder="{{ __('messages.parameter_unit_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                @error('unit') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Min / Max (for numeric type) --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="min_value" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.min_value_label') }}</label>
                    <input type="number" id="min_value" name="min_value" value="{{ old('min_value', $parameter->min_value) }}" step="any"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('min_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="max_value" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.max_value_label') }}</label>
                    <input type="number" id="max_value" name="max_value" value="{{ old('max_value', $parameter->max_value) }}" step="any"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('max_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description_label') }}</label>
                <textarea id="description" name="description" rows="3"
                    placeholder="{{ __('messages.parameter_description_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">{{ old('description', $parameter->description) }}</textarea>
                @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Active --}}
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                    @checked(old('is_active', $parameter->is_active))
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                <label for="is_active" class="text-sm text-gray-700">{{ __('messages.qc_active_checkbox') }}</label>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', [
                'label' => __('messages.cancel_btn'),
                'type' => 'secondary',
                'href' => route('qc.parameters.index'),
            ])
            @include('components.button', [
                'label' => $parameter->exists ? __('messages.update_parameter_btn') : __('messages.create_parameter_btn'),
                'type' => 'primary',
                'buttonType' => 'submit',
            ])
        </div>
    </form>
@endsection
