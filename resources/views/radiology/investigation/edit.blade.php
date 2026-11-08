@extends('layouts.modern')

@section('title', 'Edit Radiology Investigation')

@section('content')
    <x-ui.page title="Edit Radiology Investigation" subtitle="Update investigation name, code, and price.">
        <form action="{{ route('radiology.investigations.update', $investigation->id) }}" method="POST">
            @csrf
            @method('PUT')

            <x-ui.card title="{{ $investigation->name }}">
                <div class="grid gap-4 md:grid-cols-3">
                    <x-ui.input label="Investigation Name" name="name" value="{{ old('name', $investigation->name) }}" required />
                    <x-ui.input label="Investigation Code" name="code" value="{{ old('code', $investigation->code) }}" />
                    <x-ui.input label="Price" name="price" type="number" step="0.01" value="{{ old('price', $investigation->price) }}" />
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <a href="{{ route('radiology.investigations.index') }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Cancel</a>
                    <x-ui.button type="submit">Update Investigation</x-ui.button>
                </div>
            </x-ui.card>
        </form>
    </x-ui.page>
@endsection
