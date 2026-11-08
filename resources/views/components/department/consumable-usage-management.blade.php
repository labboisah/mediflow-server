<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Stock Usage</h1>
            <p class="text-muted mb-0">Assign consumables to users and monitor usage for {{ auth()->user()->department?->name ?? 'your department' }}.</p>
        </div>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Report
        </button>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Used Quantity</p>
                    <h3 class="h4 mb-0">{{ number_format($summary['quantity'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Usage Records</p>
                    <h3 class="h4 mb-0">{{ number_format($summary['records']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Below Reorder</p>
                    <h3 class="h4 mb-0 text-warning">{{ number_format($summary['lowStock']) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4 no-print">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Assign Consumable</h2>
                </div>
                <div class="card-body">
                    <form wire:submit="assign">
                        <div class="mb-3">
                            <label class="form-label">Consumable</label>
                            <select class="form-select @error('consumableId') is-invalid @enderror" wire:model="consumableId">
                                <option value="">Select consumable</option>
                                @foreach($consumables as $consumable)
                                    <option value="{{ $consumable->id }}">
                                        {{ $consumable->name }} - {{ number_format($consumable->current_quantity, 2) }} {{ $consumable->unit }}
                                    </option>
                                @endforeach
                            </select>
                            @error('consumableId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign To</label>
                            <select class="form-select @error('assignedTo') is-invalid @enderror" wire:model="assignedTo">
                                <option value="">Select user</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('assignedTo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('quantity') is-invalid @enderror" wire:model="quantity">
                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Usage Date</label>
                            <input type="date" class="form-control @error('usageDate') is-invalid @enderror" wire:model="usageDate">
                            @error('usageDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Purpose</label>
                            <input type="text" class="form-control @error('purpose') is-invalid @enderror" wire:model="purpose">
                            @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea rows="3" class="form-control @error('notes') is-invalid @enderror" wire:model="notes"></textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Assign
                            </button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="resetAssignment">Clear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="print-header d-none d-print-block mb-3">
                        <h2 class="h4 mb-1">{{ config('app.name') }} Consumable Usage Report</h2>
                        <p class="mb-0">{{ auth()->user()->department?->name }} | {{ $dateFrom }} to {{ $dateTo }}</p>
                        <p class="mb-0">Generated by {{ auth()->user()->name }} on {{ now()->format('M d, Y h:i A') }}</p>
                    </div>

                    <div class="row g-2 align-items-end mb-3 no-print">
                        <div class="col-md-3">
                            <label class="form-label">From</label>
                            <input type="date" class="form-control" wire:model.live="dateFrom">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To</label>
                            <input type="date" class="form-control" wire:model.live="dateTo">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">User</label>
                            <select class="form-select" wire:model.live="userFilter">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Consumable</label>
                            <select class="form-select" wire:model.live="consumableFilter">
                                <option value="">All Consumables</option>
                                @foreach($consumables as $consumable)
                                    <option value="{{ $consumable->id }}">{{ $consumable->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h3 class="h6 mb-3">Usage Summary</h3>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Consumable</th>
                                    <th class="text-end">Usage Count</th>
                                    <th class="text-end">Quantity Used</th>
                                    <th class="text-end">Current Stock</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reportRows as $row)
                                    <tr>
                                        <td>{{ $row->consumable?->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($row->usage_count) }}</td>
                                        <td class="text-end">{{ number_format($row->total_quantity, 2) }}</td>
                                        <td class="text-end">{{ number_format($row->consumable?->current_quantity ?? 0, 2) }}</td>
                                        <td>
                                            @if($row->consumable?->isBelowReorderLevel())
                                                <span class="badge bg-warning">Reorder</span>
                                            @else
                                                <span class="badge bg-success">Available</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No usage summary for this duration.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h3 class="h6 mb-3">Usage Details</h3>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Consumable</th>
                                    <th>Assigned To</th>
                                    <th class="text-end">Quantity</th>
                                    <th>Purpose</th>
                                    <th>Assigned By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($usages as $usage)
                                    <tr wire:key="usage-{{ $usage->id }}">
                                        <td>{{ $usage->usage_date?->format('M d, Y') }}</td>
                                        <td>{{ $usage->consumable?->name ?? 'N/A' }}</td>
                                        <td>{{ $usage->assignedTo?->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($usage->quantity, 2) }}</td>
                                        <td>{{ $usage->purpose ?? 'N/A' }}</td>
                                        <td>{{ $usage->assignedBy?->name ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No usage records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="no-print">
                        {{ $usages->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .hospital-navbar,
        .admin-sidebar,
        .breadcrumb,
        .no-print {
            display: none !important;
        }

        .admin-layout,
        .admin-content {
            display: block !important;
            width: 100% !important;
        }

        body {
            background: #fff !important;
        }

        .card {
            box-shadow: none !important;
            border: 0 !important;
        }
    }
</style>
