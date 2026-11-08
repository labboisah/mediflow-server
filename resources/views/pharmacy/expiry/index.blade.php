@extends('layouts.modern')

@section('content')
    <x-ui.page title="Expiring Medicines" subtitle="Review medicine batches that need expiry attention.">
        <x-ui.card title="Expiry Watchlist" subtitle="{{ $batches->count() }} batch{{ $batches->count() === 1 ? '' : 'es' }} listed.">
            <x-ui.table class="shadow-none">
                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Medicine</th><th class="px-4 py-3">Batch</th><th class="px-4 py-3">Quantity</th><th class="px-4 py-3">Expiry Date</th></tr></thead>
                <tbody class="divide-y divide-med-line bg-white">
                    @forelse($batches as $batch)
                        <tr><td class="px-4 py-4 font-semibold text-med-ink">{{ $batch->medicine?->name ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $batch->batch_number }}</td><td class="px-4 py-4 text-med-muted">{{ number_format($batch->quantity_remaining) }}</td><td class="px-4 py-4"><x-ui.badge :variant="$batch->expiryBadgeVariant()">{{ $batch->expiry_date }}</x-ui.badge></td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8"><x-ui.empty-state title="No Expiring Medicine" message="Expiry alerts will appear here when stock requires attention." /></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </x-ui.page>
@endsection
