@extends('layouts.modern')

@section('title', 'Profile')
@section('page-title', 'Profile')
@section('page-subtitle', 'Manage your account information, password, and account settings.')

@section('content')
    <x-ui.page title="Profile" subtitle="Manage your account information, password, and account settings.">
        <div class="grid gap-6 lg:grid-cols-3">
            <x-ui.card title="Profile Information" class="lg:col-span-1">
                @include('profile.partials.update-profile-information-form')
            </x-ui.card>

            <x-ui.card title="Password" class="lg:col-span-1">
                @include('profile.partials.update-password-form')
            </x-ui.card>

            <x-ui.card title="Account" class="lg:col-span-1">
                @include('profile.partials.delete-user-form')
            </x-ui.card>
        </div>
    </x-ui.page>
@endsection
