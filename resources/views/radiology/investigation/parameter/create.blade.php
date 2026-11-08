@extends('layouts.modern')

@section('title', 'Create Radiology Parameter')

@section('content')
    <x-ui.page title="Create Investigation Parameter" subtitle="Add a result field for {{ $investigation->name }}.">
        <form action="{{ route('radiology.investigations.parameters.store', $investigation) }}" method="POST">
            @csrf

            <x-ui.card title="Parameter Details">
                <div class="grid gap-4 md:grid-cols-3">
                    <x-ui.input label="Parameter Name" name="name" value="{{ old('name') }}" required />
                    <x-ui.input label="Parameter Unit" name="unit" value="{{ old('unit') }}" required />
                    <x-ui.input label="Reference Range" name="reference_range" value="{{ old('reference_range') }}" required />
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <a href="{{ route('radiology.investigations.parameters.index', $investigation) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Cancel</a>
                    <x-ui.button type="submit">Create Parameter</x-ui.button>
                </div>
            </x-ui.card>
        </form>
    </x-ui.page>
@endsection
