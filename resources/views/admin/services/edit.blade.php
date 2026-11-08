@extends('layouts.modern')

@section('title', 'Edit Service')
@section('page-title', 'Edit Service')
@section('page-subtitle', 'Update service pricing, category, department, and billing availability.')

@section('content')
    <x-ui.page title="Edit Service" subtitle="Update service pricing, category, department, and billing availability.">
        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-8" title="{{ $service->name }}">
                <form action="{{ route('admin.services.update', $service) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input label="Service Code" name="code" value="{{ old('code', $service->code) }}" placeholder="e.g., GH-001" required />
                        <x-ui.input label="Service Name" name="name" value="{{ old('name', $service->name) }}" placeholder="e.g., General Consultation" required />
                        <x-ui.input label="Price" name="price" type="number" step="0.01" value="{{ old('price', $service->price) }}" placeholder="0.00" required />

                        <x-ui.select label="Category" name="category" required>
                            <option value="">Select category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" @selected(old('category', $service->category) == $cat)>{{ $cat }}</option>
                            @endforeach
                        </x-ui.select>

                        <div class="md:col-span-2">
                            <x-ui.select label="Department" name="department_id">
                                <option value="">Select department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id', $service->department_id) == $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        <div class="md:col-span-2">
                            <x-ui.textarea label="Description" name="description" rows="3" placeholder="Service description">{{ old('description', $service->description) }}</x-ui.textarea>
                        </div>
                    </div>

                    <label class="flex items-center gap-3 rounded-md border border-med-line bg-white px-4 py-3 text-sm font-medium text-med-muted">
                        <input class="h-4 w-4 rounded border-med-line text-med-primary" type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active))>
                        Active and available for billing
                    </label>

                    <div class="flex flex-wrap gap-3 border-t border-med-line pt-5">
                        <x-ui.button type="submit">
                            <i class="bi bi-check-circle"></i>
                            Update Service
                        </x-ui.button>

                        <a href="{{ route('admin.services.show', $service) }}">
                            <x-ui.button type="button" variant="secondary">Cancel</x-ui.button>
                        </a>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </x-ui.page>
@endsection
