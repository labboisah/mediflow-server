@extends('layouts.modern')

@section('title', 'Edit Ward')
@section('page-title', 'Edit Ward')
@section('page-subtitle', 'Update ward name, price, and capacity.')

@section('content')
    <x-ui.page title="Edit Ward" subtitle="Update ward name, price, and capacity.">
        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-7" title="{{ $ward->name }}">
                <form method="POST" action="{{ route($routePrefix.'.wards.update', $ward) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input label="Name" name="name" value="{{ old('name', $ward->name) }}" placeholder="Enter ward name" required />
                        <x-ui.input label="Price Per Day" name="price" type="number" step="0.01" value="{{ old('price', $ward->price) }}" placeholder="0.00" required />
                        <x-ui.input class="md:col-span-2" label="Bed Capacity" name="capacity" type="number" min="1" value="{{ old('capacity', $ward->capacity) }}" placeholder="Enter bed capacity" required />
                    </div>

                    <div class="rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                        Saving capacity recreates the ward bed list, matching the existing workflow.
                    </div>

                    <div class="flex flex-wrap gap-3 border-t border-med-line pt-5">
                        <x-ui.button type="submit">
                            <i class="bi bi-check-circle"></i>
                            Save Changes
                        </x-ui.button>

                        <a href="{{ route($routePrefix.'.wards.index') }}">
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
