@extends('layouts.modern')

@section('title', 'Edit Laboratory Parameter')

@section('content')
    <x-ui.page title="Edit Investigation Parameter" subtitle="Update {{ $parameter->name }} for {{ $investigation->name }}.">
        <x-slot:actions><a href="{{ route('lab.investigations.parameters.index', $investigation) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas"><i class="bi bi-arrow-left"></i>Back to Parameters</a></x-slot:actions>
        <form action="{{ route('lab.investigations.parameters.update', [$investigation, $parameter]) }}" method="POST">@csrf @method('PUT')<x-ui.card title="Parameter Details"><div class="grid gap-4 md:grid-cols-3"><x-ui.input label="Parameter Name" name="name" value="{{ old('name', $parameter->name) }}" required /><x-ui.input label="Parameter Unit" name="unit" value="{{ old('unit', $parameter->unit) }}" required /><x-ui.input label="Reference Range" name="reference_range" value="{{ old('reference_range', $parameter->reference_range) }}" required /></div><div class="mt-6 flex justify-end"><x-ui.button type="submit">Save Parameter</x-ui.button></div></x-ui.card></form>
    </x-ui.page>
@endsection
