<div>
    <x-ui.page title="Medicine Batches" subtitle="Editable batch records that feed the stock inventory summary.">
        <x-slot:actions>
            <a href="{{ route('pharmacy.stocks.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink shadow-sm transition hover:bg-med-canvas"><i class="bi bi-box-seam"></i>Stock</a>
            <a href="{{ route('pharmacy.stocks.create') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-med-primaryDark"><i class="bi bi-plus-circle"></i>Add Stock</a>
        </x-slot:actions>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.card><p class="text-sm font-medium text-med-muted">Batches</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['batches']) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">Available Quantity</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['quantity']) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">Purchase Value</p><p class="mt-2 text-2xl font-bold text-med-ink">&#8358;{{ number_format($summary['purchase_value'], 2) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">Retail Value</p><p class="mt-2 text-2xl font-bold text-med-ink">&#8358;{{ number_format($summary['retail_value'], 2) }}</p></x-ui.card>
        </div>

        <x-ui.card title="Filters">
            <div class="grid gap-4 xl:grid-cols-12">
                <div class="xl:col-span-3"><x-ui.input label="Search" type="search" wire:model.live.debounce.400ms="search" placeholder="Medicine or batch" /></div>
                <div class="xl:col-span-3"><x-ui.select label="Medicine" wire:model.live="medicineId"><option value="">All medicines</option>@foreach($medicines as $medicine)<option value="{{ $medicine->id }}">{{ $medicine->name }}</option>@endforeach</x-ui.select></div>
                <div class="xl:col-span-2"><x-ui.select label="Expiry" wire:model.live="expiryStatus"><option value="">All</option><option value="valid">Valid</option><option value="expiring">Expiring in 60 days</option><option value="expired">Expired</option></x-ui.select></div>
                <div class="xl:col-span-2"><x-ui.input label="From" type="date" wire:model.live="from" /></div>
                <div class="xl:col-span-2"><x-ui.input label="To" type="date" wire:model.live="to" /></div>
                <div class="xl:col-span-12"><x-ui.button type="button" variant="secondary" wire:click="resetFilters"><i class="bi bi-arrow-counterclockwise"></i>Reset Filters</x-ui.button></div>
            </div>
        </x-ui.card>

        @if($editingBatchId)
            <x-ui.card title="Edit Batch" subtitle="Update quantity, pricing, and expiry information.">
                <form wire:submit.prevent="updateBatch" class="space-y-5">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <x-ui.select label="Medicine" wire:model="batchForm.medicine_id" name="batchForm.medicine_id"><option value="">Select medicine</option>@foreach($medicines as $medicine)<option value="{{ $medicine->id }}">{{ $medicine->name }}</option>@endforeach</x-ui.select>
                        <x-ui.input label="Batch No" wire:model="batchForm.batch_number" name="batchForm.batch_number" />
                        <x-ui.input label="Received" type="number" min="0" wire:model="batchForm.quantity_received" name="batchForm.quantity_received" />
                        <x-ui.input label="Remaining" type="number" min="0" wire:model="batchForm.quantity_remaining" name="batchForm.quantity_remaining" />
                        <x-ui.input label="Purchase Price" type="number" step="0.01" min="0" wire:model="batchForm.purchase_price" name="batchForm.purchase_price" />
                        <x-ui.input label="Selling Price" type="number" step="0.01" min="0" wire:model="batchForm.selling_price" name="batchForm.selling_price" />
                        <x-ui.input label="Manufacture Date" type="date" wire:model="batchForm.manufacture_date" name="batchForm.manufacture_date" />
                        <x-ui.input label="Expiry Date" type="date" wire:model="batchForm.expiry_date" name="batchForm.expiry_date" />
                    </div>
                    <div class="flex flex-wrap justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" wire:click="cancelEdit">Cancel</x-ui.button>
                        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updateBatch"><i class="bi bi-check-circle"></i>Update Batch</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif

        <x-ui.card title="Batches">
            <x-ui.table class="shadow-none">
                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Medicine</th><th class="px-4 py-3">Batch No</th><th class="px-4 py-3">Received</th><th class="px-4 py-3">Remaining</th><th class="px-4 py-3">Purchase</th><th class="px-4 py-3">Selling</th><th class="px-4 py-3">Retail Value</th><th class="px-4 py-3">Expiry</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                <tbody class="divide-y divide-med-line bg-white">
                    @forelse($batches as $batch)
                        @php
                            $expired = \Carbon\Carbon::parse($batch->expiry_date)->isPast();
                            $expiring = ! $expired && \Carbon\Carbon::parse($batch->expiry_date)->lte(today()->addDays(60));
                        @endphp
                        <tr wire:key="medicine-batch-row-{{ $batch->id }}">
                            <td class="px-4 py-4"><p class="font-semibold text-med-ink">{{ $batch->medicine?->name ?? 'N/A' }}</p><p class="text-sm text-med-muted">{{ $batch->medicine?->medicineType?->name }} {{ $batch->medicine?->strength }}</p></td>
                            <td class="px-4 py-4 text-med-muted">{{ $batch->batch_number }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ number_format($batch->quantity_received) }}</td>
                            <td class="px-4 py-4"><x-ui.badge variant="success">{{ number_format($batch->quantity_remaining) }}</x-ui.badge></td>
                            <td class="px-4 py-4 text-med-muted">&#8358;{{ number_format($batch->purchase_price, 2) }}</td>
                            <td class="px-4 py-4 text-med-muted">&#8358;{{ number_format($batch->selling_price, 2) }}</td>
                            <td class="px-4 py-4 font-semibold text-med-ink">&#8358;{{ number_format($batch->quantity_remaining * $batch->selling_price, 2) }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $batch->expiry_date }}</td>
                            <td class="px-4 py-4">@if($expired)<x-ui.badge variant="danger">Expired</x-ui.badge>@elseif($expiring)<x-ui.badge variant="warning">Expiring</x-ui.badge>@else<x-ui.badge variant="info">Valid</x-ui.badge>@endif</td>
                            <td class="px-4 py-4 text-right"><button type="button" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark" wire:click="editBatch({{ $batch->id }})">Edit</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-8"><x-ui.empty-state title="No Medicine Batches" message="No medicine batches matched the current filters." /></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
            <div class="mt-5">{{ $batches->links() }}</div>
        </x-ui.card>
    </x-ui.page>
</div>
