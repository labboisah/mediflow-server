<div>
    <x-ui.page title="Pharmacy Inventory" subtitle="Medicine stock summary, stock import, and printable inventory reports.">
        <x-slot:actions>
            <x-ui.button type="button" variant="secondary" wire:click="downloadTemplate"><i class="bi bi-download"></i>Template</x-ui.button>
            <x-ui.button type="button" variant="secondary" wire:click="exportStock"><i class="bi bi-file-earmark-spreadsheet"></i>Export Stock</x-ui.button>
            <x-ui.button type="button" variant="secondary" wire:click="downloadStockPdf"><i class="bi bi-file-earmark-pdf"></i>Stock PDF</x-ui.button>
            <x-ui.button type="button" variant="secondary" wire:click="exportFinance"><i class="bi bi-cash-stack"></i>Export Finance</x-ui.button>
            @if(auth()->user()?->hasRole('head_of_department'))
                <a href="{{ route('pharmacy.stocks.reconciliation') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink shadow-sm transition hover:bg-med-canvas"><i class="bi bi-clipboard-check"></i>Reconciliation</a>
            @endif
            <a href="{{ route('pharmacy.batches.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink shadow-sm transition hover:bg-med-canvas"><i class="bi bi-layers"></i>Batches</a>
            <a href="{{ route('pharmacy.stocks.create') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-med-primaryDark"><i class="bi bi-plus-circle"></i>Add Stock</a>
        </x-slot:actions>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-ui.card><p class="text-sm font-medium text-med-muted">Medicines / Batches</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['medicines']) }} / {{ number_format($summary['batches']) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">Available Quantity</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['quantity']) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">Purchase Value</p><p class="mt-2 text-2xl font-bold text-med-ink">&#8358;{{ number_format($summary['purchase_value'], 2) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">Retail Value</p><p class="mt-2 text-2xl font-bold text-med-ink">&#8358;{{ number_format($summary['retail_value'], 2) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">Sales / Purchases</p><p class="mt-2 text-xl font-bold text-med-ink">&#8358;{{ number_format($summary['sales'], 2) }}</p><p class="mt-1 text-sm text-med-muted">Purchases: &#8358;{{ number_format($summary['purchases'], 2) }}</p></x-ui.card>
        </div>

        <x-ui.card title="Filters">
            <div class="grid gap-4 xl:grid-cols-12">
                <div class="xl:col-span-4"><x-ui.input label="Search" type="search" wire:model.live.debounce.400ms="search" placeholder="Medicine, generic, or company" /></div>
                <div class="xl:col-span-2"><x-ui.select label="Expiry" wire:model.live="expiryStatus"><option value="">All</option><option value="valid">Valid</option><option value="expiring">Expiring in 60 days</option><option value="expired">Expired</option></x-ui.select></div>
                <div class="xl:col-span-2"><x-ui.input label="From" type="date" wire:model.live="from" /></div>
                <div class="xl:col-span-2"><x-ui.input label="To" type="date" wire:model.live="to" /></div>
                <div class="flex items-end xl:col-span-2"><x-ui.button type="button" variant="secondary" class="w-full" wire:click="resetFilters"><i class="bi bi-arrow-counterclockwise"></i>Reset</x-ui.button></div>
            </div>
        </x-ui.card>

        <x-ui.card title="Import Medicine Stock" subtitle="Upload CSV stock records from an old system.">
            <form wire:submit="importStock" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-med-ink">CSV File</span>
                        <input type="file" wire:model="importFile" accept=".csv,text/csv" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-med-canvas file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-med-ink">
                        @error('importFile') <span class="mt-1 block text-sm text-med-danger">{{ $message }}</span> @enderror
                    </label>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="importFile,importStock"><i class="bi bi-upload"></i>Import Stock</x-ui.button>
                </div>
                <div x-data="{ uploading: false, progress: 0 }" x-on:livewire-upload-start="uploading = true; progress = 0" x-on:livewire-upload-finish="uploading = false; progress = 100" x-on:livewire-upload-error="uploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress">
                    <div x-show="uploading" class="h-3 overflow-hidden rounded-full bg-med-canvas"><div class="h-full rounded-full bg-med-primary transition-all" x-bind:style="`width: ${progress}%`"></div></div>
                    <p wire:loading wire:target="importStock" class="mt-2 text-sm text-med-muted">Processing uploaded stock records...</p>
                </div>
            </form>
            @if(! empty($importSummary))
                <div class="mt-4 rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                    Processed {{ $importSummary['processed'] }} rows, imported {{ $importSummary['imported'] }}, skipped {{ $importSummary['skipped'] }}.
                    @if(! empty($importSummary['errors']))<p class="mt-2">{{ implode(' ', array_slice($importSummary['errors'], 0, 5)) }}</p>@endif
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="Inventory">
            <x-ui.table class="shadow-none">
                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Medicine</th><th class="px-4 py-3">Batches</th><th class="px-4 py-3">Received</th><th class="px-4 py-3">Remaining</th><th class="px-4 py-3">Avg Purchase</th><th class="px-4 py-3">Next Selling</th><th class="px-4 py-3">Retail Value</th><th class="px-4 py-3">Nearest Expiry</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                <tbody class="divide-y divide-med-line bg-white">
                    @forelse($stocks as $medicine)
                        @php
                            $medicineBatches = $medicine->batches;
                            $quantityReceived = (int) $medicineBatches->sum('quantity_received');
                            $quantityRemaining = (int) $medicineBatches->sum('quantity_remaining');
                            $purchaseValue = (float) $medicineBatches->sum(fn($batch) => $batch->quantity_remaining * $batch->purchase_price);
                            $retailValue = (float) $medicineBatches->sum(fn($batch) => $batch->quantity_remaining * $batch->selling_price);
                            $averagePurchase = $quantityRemaining > 0 ? $purchaseValue / $quantityRemaining : 0;
                            $nextBatch = $medicineBatches->where('quantity_remaining', '>', 0)->where('expiry_date', '>=', today()->toDateString())->sortBy('expiry_date')->first();
                            $nearestExpiry = $medicineBatches->where('quantity_remaining', '>', 0)->sortBy('expiry_date')->first()?->expiry_date;
                            $expired = $nearestExpiry ? \Carbon\Carbon::parse($nearestExpiry)->isPast() : false;
                            $expiring = $nearestExpiry && ! $expired && \Carbon\Carbon::parse($nearestExpiry)->lte(today()->addDays(60));
                        @endphp
                        <tr>
                            <td class="px-4 py-4"><p class="font-semibold text-med-ink">{{ $medicine->name }}</p><p class="text-sm text-med-muted">{{ $medicine->medicineType?->name }} {{ $medicine->strength }}</p></td>
                            <td class="px-4 py-4 text-med-muted">{{ number_format($medicineBatches->count()) }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ number_format($quantityReceived) }}</td>
                            <td class="px-4 py-4"><x-ui.badge variant="success">{{ number_format($quantityRemaining) }}</x-ui.badge></td>
                            <td class="px-4 py-4 text-med-muted">&#8358;{{ number_format($averagePurchase, 2) }}</td>
                            <td class="px-4 py-4 text-med-muted">&#8358;{{ number_format($nextBatch?->selling_price ?? 0, 2) }}</td>
                            <td class="px-4 py-4 font-semibold text-med-ink">&#8358;{{ number_format($retailValue, 2) }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $nearestExpiry ?? 'N/A' }}</td>
                            <td class="px-4 py-4">@if($quantityRemaining <= 0)<x-ui.badge>Out</x-ui.badge>@elseif($expired)<x-ui.badge variant="danger">Expired</x-ui.badge>@elseif($expiring)<x-ui.badge variant="warning">Expiring</x-ui.badge>@else<x-ui.badge variant="info">Valid</x-ui.badge>@endif</td>
                            <td class="px-4 py-4 text-right"><a href="{{ route('pharmacy.batches.index', ['medicine' => $medicine->id]) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">View Batches</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-8"><x-ui.empty-state title="No Medicine Stock" message="No medicine stock matched the current filters." /></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
            <div class="mt-5">{{ $stocks->links() }}</div>
        </x-ui.card>
    </x-ui.page>
</div>
