@extends('layouts.app')

@section('title', 'Edit Account')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.coa.index') }}" class="hover:text-gray-700">Chart of Accounts</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Edit {{ $account->code }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Account</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $account->code }} — {{ $account->name }}</p>
        </div>
        @include('components.button', ['label' => '← Back', 'type' => 'ghost', 'href' => route('accounting.coa.index')])
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        <form method="POST" action="{{ route('accounting.coa.update', $account) }}" class="space-y-6">
            @csrf @method('PUT')
            @include('accounting.coa._form')

            <div class="flex justify-end gap-3">
                @include('components.button', ['label' => 'Cancel', 'type' => 'ghost', 'href' => route('accounting.coa.index')])
                @include('components.button', ['label' => 'Update Account', 'type' => 'primary', 'buttonType' => 'submit'])
            </div>
        </form>
    </div>
@endsection
