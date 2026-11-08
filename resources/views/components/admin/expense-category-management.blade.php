<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Expense Categories</h1>
            <p class="text-muted mb-0">Manage categories used to classify hospital expenses.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                Back to Expenses
            </a>
            <button type="button" class="btn btn-primary" wire:click="resetForm">
                <i class="bi bi-plus-circle me-1"></i>
                Add Category
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        @if($editingCategoryId)
                            Edit Category
                        @else
                            New Category
                        @endif
                    </h5>
                    @if($editingCategoryId)
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetForm">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    @endif
                </div>

                <div class="card-body">
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   wire:model="name" placeholder="e.g. Personnel Cost">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">
                                    <i class="bi bi-check-circle me-1"></i>
                                    @if($editingCategoryId) Save Changes @else Add Category @endif
                                </span>
                                <span wire:loading wire:target="save">Saving...</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="resetForm">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Expense Categories</h5>
                    <div class="col-md-5 ps-0">
                        <input type="search" class="form-control form-control-sm"
                               wire:model.live.debounce.400ms="search" placeholder="Search categories...">
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th class="text-center">Expenses</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenseCategories as $expenseCategory)
                                    <tr class="{{ $editingCategoryId === $expenseCategory->id ? 'table-warning' : '' }}">
                                        <td class="text-muted small">{{ $expenseCategory->id }}</td>
                                        <td>{{ $expenseCategory->name }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ number_format($expenseCategory->expenses_count) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                                    wire:click="edit({{ $expenseCategory->id }})">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    wire:click="delete({{ $expenseCategory->id }})"
                                                    wire:confirm="Delete '{{ addslashes($expenseCategory->name) }}'? This cannot be undone."
                                                    @if($expenseCategory->expenses_count > 0) disabled title="Has linked expenses" @endif>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No expense categories found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($expenseCategories->hasPages())
                    <div class="card-footer bg-light">
                        {{ $expenseCategories->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
