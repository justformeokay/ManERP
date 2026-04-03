@extends('layouts.app')

@section('title', __('messages.add_employee'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('hr.employees.index') }}" class="hover:text-gray-700">{{ __('messages.employees') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.create') }}</span>
@endsection

@section('page-header')
    <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.add_employee') }}</h1>
@endsection

@section('content')
    <form method="POST" action="{{ route('hr.employees.store') }}">
        @csrf
        @include('hr.employees._form')
    </form>
@endsection
