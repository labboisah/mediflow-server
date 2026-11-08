@extends('layouts.modern')

@section('title', 'Edit Laboratory Investigation')

@section('content')
    <x-ui.page title="Edit Investigation" subtitle="Update investigation name and code.">
        <x-slot:actions><a href="{{ route('lab.investigations.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas"><i class="bi bi-arrow-left"></i>Back to List</a></x-slot:actions>
        <form action="{{ route('lab.investigations.update', $investigation->id) }}" method="POST">@csrf @method('PUT')<x-ui.card title="{{ $investigation->name }}"><div class="grid gap-4 md:grid-cols-3"><x-ui.input label="Investigation Name" name="name" value="{{ old('name', $investigation->name) }}" required /><x-ui.input label="Investigation Code" name="code" value="{{ old('code', $investigation->code) }}" /><x-ui.input label="Price" name="price" type="number" step="0.01" value="{{ old('price', $investigation->price) }}" disabled /></div><div class="mt-6 flex justify-end"><x-ui.button type="submit">Update Investigation</x-ui.button></div></x-ui.card></form>
    </x-ui.page>
@endsection
