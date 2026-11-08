@extends('layouts.modern')

@section('content')
    <x-ui.page title="Medicines" subtitle="Manage medicine catalog records and current available stock.">
        <x-slot:actions>
            <a href="{{ route('pharmacy.medicines.create') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-med-primaryDark"><i class="bi bi-plus-circle"></i>Add Medicine</a>
        </x-slot:actions>

        <x-ui.card title="Medicine Catalog" subtitle="{{ $medicines->count() }} medicine{{ $medicines->count() === 1 ? '' : 's' }} registered.">
            <x-ui.table class="shadow-none">
                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Generic</th><th class="px-4 py-3">Form</th><th class="px-4 py-3">Manufacturer</th><th class="px-4 py-3">Stock</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                <tbody class="divide-y divide-med-line bg-white">
                    @forelse($medicines as $medicine)
                        <tr>
                            <td class="px-4 py-4 font-semibold text-med-ink">{{ $medicine->name }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $medicine->medicineType?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $medicine->generic_name ?? 'N/A' }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $medicine->form ?? 'N/A' }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $medicine->manufacturer ?? 'N/A' }}</td>
                            <td class="px-4 py-4"><x-ui.badge variant="success">{{ number_format($medicine->availableQuantity()) }}</x-ui.badge></td>
                            <td class="px-4 py-4 text-right"><a href="{{ route('pharmacy.batches.index', ['medicine' => $medicine->id]) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">Batches</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state title="No Medicines" message="Medicines will appear here after they are registered." /></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </x-ui.page>
@endsection
