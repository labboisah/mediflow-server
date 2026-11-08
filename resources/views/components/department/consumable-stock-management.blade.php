<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Consumable Stock</h1>
            <p class="text-muted mb-0">Add and correct stock purchases for {{ auth()->user()->department?->name ?? 'your department' }}.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">{{ $editingId ? 'Edit Stock' : 'Add Stock' }}</h2>
                </div>
                <div class="card-body">
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Consumable</label>
                            <select class="form-select @error('consumableId') is-invalid @enderror" wire:model="consumableId">
                                <option value="">Select consumable</option>
                                @foreach($consumables as $consumable)
                                    <option value="{{ $consumable->id }}">{{ $consumable->name }} ({{ $consumable->unit }})</option>
                                @endforeach
                            </select>
                            @error('consumableId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('quantity') is-invalid @enderror" wire:model="quantity">
                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit Price</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('unitPrice') is-invalid @enderror" wire:model="unitPrice">
                            @error('unitPrice') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control @error('purchaseDate') is-invalid @enderror" wire:model="purchaseDate">
                            @error('purchaseDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reference</label>
                            <input type="text" class="form-control @error('reference') is-invalid @enderror" wire:model="reference">
                            @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> {{ $editingId ? 'Update' : 'Save' }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="resetForm">Clear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <label class="form-label">Search</label>
                    <input type="search" class="form-control mb-3" wire:model.live.debounce.400ms="search" placeholder="Consumable or reference">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Consumable</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stocks as $stock)
                                    <tr wire:key="stock-{{ $stock->id }}">
                                        <td>{{ $stock->consumable?->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($stock->quantity, 2) }}</td>
                                        <td class="text-end">&#8358;{{ number_format($stock->unit_price, 2) }}</td>
                                        <td class="text-end">&#8358;{{ number_format($stock->quantity * $stock->unit_price, 2) }}</td>
                                        <td>{{ $stock->purchase_date?->format('M d, Y') ?? 'N/A' }}</td>
                                        <td>{{ $stock->reference ?? 'N/A' }}</td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-warning" wire:click="edit({{ $stock->id }})">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" wire:click="delete({{ $stock->id }})" wire:confirm="Delete this stock entry? This will reduce current quantity.">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No stock records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $stocks->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
