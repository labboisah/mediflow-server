@extends('layouts.modern')

@section('content')
    <x-ui.page title="Add Medicine" subtitle="Register a medicine before receiving stock batches.">
        <x-slot:actions>
            <a href="{{ route('pharmacy.medicines.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink shadow-sm transition hover:bg-med-canvas"><i class="bi bi-arrow-left"></i>Medicines</a>
        </x-slot:actions>

        <form method="POST" action="{{ route('pharmacy.medicines.store') }}">
            @csrf
            <x-ui.card title="Medicine Details">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <x-ui.select label="Medicine Type" name="medicine_type_id" required><option value="">Select medicine type</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected(old('medicine_type_id') == $type->id)>{{ $type->name }}</option>@endforeach</x-ui.select>
                    <x-ui.input label="Name" name="name" value="{{ old('name') }}" required />
                    <x-ui.input label="Generic Name" name="generic_name" value="{{ old('generic_name') }}" />
                    <x-ui.select label="Form" name="form"><option value="">Select form</option>@foreach(['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream'] as $form)<option value="{{ $form }}" @selected(old('form') === $form)>{{ $form }}</option>@endforeach</x-ui.select>
                    <x-ui.input label="Strength" name="strength" value="{{ old('strength') }}" />
                    <x-ui.input label="Manufacturer" name="manufacturer" value="{{ old('manufacturer') }}" />
                </div>
                <div class="mt-6 flex justify-end"><x-ui.button type="submit"><i class="bi bi-check-circle"></i>Save Medicine</x-ui.button></div>
            </x-ui.card>
        </form>
    </x-ui.page>
@endsection
