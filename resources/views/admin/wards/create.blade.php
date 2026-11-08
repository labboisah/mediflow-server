@extends('layouts.modern')

@section('title', 'Create Ward')
@section('page-title', 'Create Ward')
@section('page-subtitle', 'Add a ward and automatically create beds from its capacity.')

@section('content')
    <x-ui.page title="Create Ward" subtitle="Add a ward and automatically create beds from its capacity.">
        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-7" title="Ward Details">
                <form method="POST" action="{{ route($routePrefix.'.wards.store') }}" class="space-y-5">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input label="Name" name="name" value="{{ old('name') }}" placeholder="Enter ward name" required />
                        <x-ui.input label="Price Per Day" name="price" type="number" step="0.01" value="{{ old('price') }}" placeholder="0.00" required />
                        <x-ui.input class="md:col-span-2" label="Bed Capacity" name="capacity" type="number" min="1" value="{{ old('capacity') }}" placeholder="Enter bed capacity" required />
                    </div>

                    <div class="flex flex-wrap gap-3 border-t border-med-line pt-5">
                        <x-ui.button type="submit">
                            <i class="bi bi-check-circle"></i>
                            Register
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
