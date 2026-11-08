@extends('layouts.modern')

@section('content')
    <x-ui.page title="Receive Stock" subtitle="Create a new medicine batch and add it to inventory.">
        <x-slot:actions>
            <a href="{{ route('pharmacy.stocks.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink shadow-sm transition hover:bg-med-canvas"><i class="bi bi-arrow-left"></i>Inventory</a>
        </x-slot:actions>

        <form method="POST" action="{{ route('pharmacy.stocks.store') }}">
            @csrf
            <x-ui.card title="Batch Details">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <x-ui.select label="Medicine" name="medicine_id" required><option value="">Select medicine</option>@foreach($medicines as $medicine)<option value="{{ $medicine->id }}" @selected(old('medicine_id') == $medicine->id)>{{ $medicine->name }}</option>@endforeach</x-ui.select>
                    <x-ui.input label="Batch Number" name="batch_number" value="{{ old('batch_number') }}" placeholder="Leave blank to auto-generate" />
                    <x-ui.input label="Quantity" name="quantity_received" type="number" min="1" value="{{ old('quantity_received') }}" required />
                    <x-ui.input label="Purchase Price" name="purchase_price" type="number" step="0.01" min="0" value="{{ old('purchase_price') }}" required />
                    <x-ui.input label="Selling Price" name="selling_price" type="number" step="0.01" min="0" value="{{ old('selling_price') }}" required />
                    <x-ui.input label="Manufacture Date" name="manufacture_date" type="date" value="{{ old('manufacture_date') }}" />
                    <x-ui.input label="Expiry Date" name="expiry_date" type="date" value="{{ old('expiry_date') }}" />
                </div>
                <div class="mt-6 flex justify-end"><x-ui.button type="submit"><i class="bi bi-check-circle"></i>Save Batch</x-ui.button></div>
            </x-ui.card>
        </form>
    </x-ui.page>
@endsection
