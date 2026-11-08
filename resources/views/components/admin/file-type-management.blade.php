<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-folder2-open me-2 text-success"></i>File Type Management</h1>
            <p class="text-muted mb-0">Manage patient registration file types and opening fees.</p>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Active File Types</div>
                    <div class="fs-4 fw-bold">{{ number_format($activeCount) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Deleted File Types</div>
                    <div class="fs-4 fw-bold">{{ number_format($deletedCount) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">{{ $editingFileTypeId ? 'Edit File Type' : 'New File Type' }}</h2>
                    @if($editingFileTypeId)
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetForm">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="save">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name" placeholder="Opening Personal File">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror" wire:model="price" placeholder="0.00">
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-success w-100" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">
                                <i class="bi bi-save"></i> {{ $editingFileTypeId ? 'Update File Type' : 'Save File Type' }}
                            </span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-7">
                            <label class="form-label">Search</label>
                            <input type="search" class="form-control" wire:model.live.debounce.400ms="search" placeholder="Search file type">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Status</label>
                            <select class="form-select" wire:model.live="status">
                                <option value="active">Active</option>
                                <option value="deleted">Deleted</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th class="text-end">Price</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fileTypes as $fileType)
                                    <tr wire:key="file-type-{{ $fileType->id }}">
                                        <td class="fw-semibold">{{ $fileType->name }}</td>
                                        <td class="text-end">&#8358;{{ number_format((float) $fileType->price, 2) }}</td>
                                        <td>
                                            @if($fileType->trashed())
                                                <span class="badge bg-secondary">Deleted</span>
                                            @else
                                                <span class="badge bg-success">Active</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($fileType->trashed())
                                                <button type="button" class="btn btn-sm btn-outline-success" wire:click="restore({{ $fileType->id }})" wire:confirm="Restore this file type?">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                </button>
                                            @else
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-warning" wire:click="edit({{ $fileType->id }})">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" wire:click="delete({{ $fileType->id }})" wire:confirm="Delete this file type? It can be restored later.">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No file type found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    {{ $fileTypes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
