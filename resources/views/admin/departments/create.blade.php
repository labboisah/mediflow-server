@extends('layouts.modern')

@section('title', 'Create Department')
@section('page-title', 'Create Department')
@section('page-subtitle', 'Add a department for staff assignment, services, and reporting.')

@section('content')
    <x-ui.page title="Create Department" subtitle="Add a department for staff assignment, services, and reporting.">
        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-7" title="Department Details">
                <form method="POST" action="{{ route('admin.departments.store') }}" class="space-y-5">
                    @csrf

                    <x-ui.input label="Name" name="name" value="{{ old('name') }}" placeholder="Enter department name" required />

                    <div class="flex flex-wrap gap-3 border-t border-med-line pt-5">
                        <x-ui.button type="submit">
                            <i class="bi bi-check-circle"></i>
                            Register
                        </x-ui.button>

                        <a href="{{ route('admin.departments.index') }}">
                            <x-ui.button type="button" variant="secondary">
                                <i class="bi bi-arrow-left"></i>
                                Back
                            </x-ui.button>
                        </a>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </x-ui.page>
@endsection
