@extends('layouts.modern')

@section('title', 'Edit Investigation')
@section('page-title', 'Edit Investigation')
@section('page-subtitle', 'Update investigation name, price, and diagnostic type.')

@section('content')
    <x-ui.page title="Edit Investigation" subtitle="Update investigation name, price, and diagnostic type.">
        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-7" title="{{ $investigation->name }}">
                <form method="POST" action="{{ route('admin.investigations.update', $investigation) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input label="Name" name="name" value="{{ old('name', $investigation->name) }}" placeholder="Enter investigation name" required />
                        <x-ui.input label="Price" name="price" type="number" step="0.01" value="{{ old('price', $investigation->price) }}" placeholder="0.00" required />

                        <div class="md:col-span-2">
                            <x-ui.select label="Investigation Type" name="investigation_type" required>
                                <option value="">Select investigation type</option>
                                @foreach($investigationTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('investigation_type', $investigation->investigation_type_id) == $type->id)>
                                        {{ $type->department?->name ? $type->department->name . ' - ' : '' }}{{ $type->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 border-t border-med-line pt-5">
                        <x-ui.button type="submit">
                            <i class="bi bi-check-circle"></i>
                            Save Changes
                        </x-ui.button>

                        <a href="{{ route('admin.investigations.index') }}">
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
