@extends('layouts.modern')

@section('title', 'Edit Bed')
@section('page-title', 'Edit Bed '.$bed->bed_no)
@section('page-subtitle', 'Update the bed number for '.$bed->ward->name.'.')

@section('content')
    <x-ui.page :title="'Edit Bed '.$bed->bed_no" :subtitle="'Update the bed number for '.$bed->ward->name.'.'">
        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-7" title="Bed Details">
                <form method="POST" action="{{ route($routePrefix.'.beds.update', $bed) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <x-ui.input label="Bed No" name="bed_no" value="{{ old('bed_no', $bed->bed_no) }}" placeholder="Enter bed number" required />

                    <div class="flex flex-wrap gap-3 border-t border-med-line pt-5">
                        <x-ui.button type="submit">
                            <i class="bi bi-check-circle"></i>
                            Save Changes
                        </x-ui.button>

                        <a href="{{ route($routePrefix.'.beds.index', $bed->ward) }}">
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
