<div>
    <x-ui.page title="Stock Reconciliation" subtitle="Compare physical pharmacy stock with system records and keep a referenced audit trail.">
        <x-slot:actions>
            <a href="{{ route('pharmacy.stocks.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink shadow-sm transition hover:bg-med-canvas"><i class="bi bi-arrow-left"></i>Inventory</a>
            <x-ui.button type="button" variant="secondary" wire:click="fillVisibleWithSystemCounts"><i class="bi bi-copy"></i>Fill System Qty</x-ui.button>
            <x-ui.button type="button" variant="danger" wire:click="clearCounts"><i class="bi bi-x-circle"></i>Clear</x-ui.button>
        </x-slot:actions>

        <div class="grid gap-4 md:grid-cols-3">
            <x-ui.card><p class="text-sm font-medium text-med-muted">Batches In View</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['batches']) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">System Quantity</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['quantity']) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">Entered Counts</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ collect($physicalCounts)->filter(fn($value) => trim((string) $value) !== '')->count() }}</p></x-ui.card>
        </div>

        <x-ui.card title="Filters">
            <div class="grid gap-4 xl:grid-cols-12">
                <div class="xl:col-span-5"><x-ui.input label="Search" type="search" wire:model.live.debounce.400ms="search" placeholder="Medicine, generic, company, or batch" /></div>
                <div class="xl:col-span-3"><x-ui.select label="Expiry" wire:model.live="expiryStatus"><option value="">All</option><option value="valid">Valid</option><option value="expiring">Expiring in 60 days</option><option value="expired">Expired</option></x-ui.select></div>
                <div class="xl:col-span-4"><x-ui.input label="Check Note" wire:model="notes" name="notes" placeholder="Optional note for this stock check" /></div>
            </div>
        </x-ui.card>

        @error('physicalCounts')
            <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-med-danger">{{ $message }}</div>
        @enderror

        <form wire:submit="saveReconciliation">
            <x-ui.card title="Physical Counts">
                <x-ui.table class="shadow-none">
                    <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Medicine</th><th class="px-4 py-3">Batch</th><th class="px-4 py-3">Expiry</th><th class="px-4 py-3 text-right">System Qty</th><th class="px-4 py-3 text-right">Physical Qty</th><th class="px-4 py-3 text-right">Variance</th><th class="px-4 py-3">Line Note</th></tr></thead>
                    <tbody class="divide-y divide-med-line bg-white">
                        @forelse($batches as $batch)
                            @php
                                $physical = trim((string) ($physicalCounts[$batch->id] ?? ''));
                                $variance = $physical === '' ? null : ((int) $physical - (int) $batch->quantity_remaining);
                            @endphp
                            <tr wire:key="reconcile-batch-{{ $batch->id }}">
                                <td class="px-4 py-4"><p class="font-semibold text-med-ink">{{ $batch->medicine?->name ?? 'N/A' }}</p><p class="text-sm text-med-muted">{{ $batch->medicine?->medicineType?->name }} {{ $batch->medicine?->strength }}</p></td>
                                <td class="px-4 py-4 text-med-muted">{{ $batch->batch_number }}</td>
                                <td class="px-4 py-4 text-med-muted">{{ $batch->expiry_date }}</td>
                                <td class="px-4 py-4 text-right text-med-muted">{{ number_format($batch->quantity_remaining) }}</td>
                                <td class="px-4 py-4"><input type="number" min="0" class="mf-focus block w-32 rounded-md border border-med-line px-3 py-2 text-right text-sm text-med-ink shadow-sm" wire:model.live="physicalCounts.{{ $batch->id }}">@error('physicalCounts.' . $batch->id)<span class="mt-1 block text-xs text-med-danger">{{ $message }}</span>@enderror</td>
                                <td class="px-4 py-4 text-right">@if($variance === null)<span class="text-med-muted">-</span>@elseif($variance === 0)<x-ui.badge variant="success">0</x-ui.badge>@elseif($variance > 0)<x-ui.badge variant="info">+{{ number_format($variance) }}</x-ui.badge>@else<x-ui.badge variant="danger">{{ number_format($variance) }}</x-ui.badge>@endif</td>
                                <td class="px-4 py-4"><input type="text" class="mf-focus block min-w-44 rounded-md border border-med-line px-3 py-2 text-sm text-med-ink shadow-sm" wire:model="itemNotes.{{ $batch->id }}" placeholder="Reason if different">@error('itemNotes.' . $batch->id)<span class="mt-1 block text-xs text-med-danger">{{ $message }}</span>@enderror</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state title="No Stock Batch" message="No stock batch matched the current filters." /></td></tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <div>{{ $batches->links() }}</div>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="saveReconciliation"><i class="bi bi-check2-circle"></i><span wire:loading.remove wire:target="saveReconciliation">Save Reconciliation</span><span wire:loading wire:target="saveReconciliation">Saving...</span></x-ui.button>
                </div>
            </x-ui.card>
        </form>

        <x-ui.card title="Recent Stock Reconciliations">
            <x-ui.table class="shadow-none">
                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Checked By</th><th class="px-4 py-3 text-right">Batches</th><th class="px-4 py-3 text-right">System</th><th class="px-4 py-3 text-right">Physical</th><th class="px-4 py-3 text-right">Variance</th></tr></thead>
                <tbody class="divide-y divide-med-line bg-white">
                    @forelse($summary['recent'] as $reconciliation)
                        <tr>
                            <td class="px-4 py-4 font-semibold text-med-ink">{{ $reconciliation->reference }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $reconciliation->checked_date?->format('M d, Y') }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $reconciliation->checkedBy?->name ?? 'System' }}</td>
                            <td class="px-4 py-4 text-right text-med-muted">{{ number_format($reconciliation->items_count) }}</td>
                            <td class="px-4 py-4 text-right text-med-muted">{{ number_format($reconciliation->total_system_quantity) }}</td>
                            <td class="px-4 py-4 text-right text-med-muted">{{ number_format($reconciliation->total_physical_quantity) }}</td>
                            <td class="px-4 py-4 text-right">@if($reconciliation->total_variance === 0)<x-ui.badge variant="success">0</x-ui.badge>@elseif($reconciliation->total_variance > 0)<x-ui.badge variant="info">+{{ number_format($reconciliation->total_variance) }}</x-ui.badge>@else<x-ui.badge variant="danger">{{ number_format($reconciliation->total_variance) }}</x-ui.badge>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state title="No Reconciliation Recorded" message="Recent pharmacy stock checks will appear here." /></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </x-ui.page>
</div>
