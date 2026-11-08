@extends('layouts.modern')

@section('title', 'Edit Department')
@section('page-title', 'Edit Department')
@section('page-subtitle', 'Update the department name used across staff, services, and reports.')

@section('content')
    <x-ui.page title="Edit Department" subtitle="Update the department name used across staff, services, and reports.">
        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-7" title="Department Details">
                <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <x-ui.input label="Name" name="name" value="{{ old('name', $department->name) }}" placeholder="Enter department name" required />

                    <div class="flex flex-wrap gap-3 border-t border-med-line pt-5">
                        <x-ui.button type="submit">
                            <i class="bi bi-check-circle"></i>
                            Save Changes
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
