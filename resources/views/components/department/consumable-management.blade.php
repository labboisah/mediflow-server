<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Consumables</h1>
            <p class="text-muted mb-0">Manage consumables for {{ auth()->user()->department?->name ?? 'your department' }}.</p>
        </div>
        <span class="badge bg-{{ $lowStockCount > 0 ? 'warning' : 'success' }}">
            {{ number_format($lowStockCount) }} below reorder level
        </span>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">{{ $editingId ? 'Edit Consumable' : 'Add Consumable' }}</h2>
                </div>
                <div class="card-body">
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit</label>
                            <input type="text" class="form-control @error('unit') is-invalid @enderror" wire:model="unit" placeholder="pcs, box, bottle">
                            @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('reorderLevel') is-invalid @enderror" wire:model="reorderLevel">
                            @error('reorderLevel') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
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
                    <input type="search" class="form-control mb-3" wire:model.live.debounce.400ms="search" placeholder="Consumable name">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Unit</th>
                                    <th class="text-end">Current Quantity</th>
                                    <th class="text-end">Reorder Level</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($consumables as $item)
                                    <tr wire:key="consumable-{{ $item->id }}">
                                        <td class="fw-semibold">{{ $item->name }}</td>
                                        <td>{{ $item->unit }}</td>
                                        <td class="text-end">{{ number_format($item->current_quantity, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->reorder_level, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $item->isBelowReorderLevel() ? 'warning' : 'success' }}">
                                                {{ $item->isBelowReorderLevel() ? 'Reorder' : 'Available' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-warning" wire:click="edit({{ $item->id }})">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" wire:click="delete({{ $item->id }})" wire:confirm="Delete this consumable?">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No consumables found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $consumables->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
