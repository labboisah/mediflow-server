@php
    $chartLabels = collect($chartPayload['labels'])->take(8)->values();
    $chartValues = collect($chartPayload['values'])->take(8)->map(fn ($value) => (float) $value)->values();
    $maxChartValue = max($chartValues->max() ?: 1, 1);
    $chartColors = ['#198754', '#0d6efd', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d'];
    $chartTotal = max($chartValues->sum(), 1);
    $currentOffset = 0;
    $linePoints = $chartValues->map(function ($value, $index) use ($chartValues, $maxChartValue) {
        $count = max($chartValues->count() - 1, 1);
        $x = 10 + (($index / $count) * 280);
        $y = 110 - (($value / $maxChartValue) * 90);

        return round($x, 2) . ',' . round($y, 2);
    })->implode(' ');
@endphp

<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Financial / Billing Report</h1>
            <p class="text-muted mb-0">Filter billing activity, review totals, and switch visual breakdowns.</p>
        </div>

        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>
                Print
            </button>

            <a class="btn btn-success" href="{{ $exportUrl }}">
                <i class="bi bi-download me-1"></i>
                CSV
            </a>

            <a class="btn btn-danger" href="{{ $pdfUrl }}">
                <i class="bi bi-file-earmark-pdf me-1"></i>
                PDF
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">Bills</p>
                <h4 class="mb-0">{{ number_format($summary['bill_count']) }}</h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">Total Billed</p>
                <h4 class="text-primary mb-0">{{ number_format($summary['total_billed'], 2) }}</h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">Total Collected</p>
                <h4 class="text-success mb-0">{{ number_format($summary['total_collected'], 2) }}</h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">Open Bills</p>
                <h4 class="text-warning mb-0">{{ number_format($summary['open_bills']) }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">Other Revenue</p>
                <h4 class="text-success mb-0">{{ number_format($summary['total_revenue'], 2) }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">Expenses</p>
                <h4 class="text-danger mb-0">{{ number_format($summary['total_expense'], 2) }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">Net Position</p>
                <h4 class="{{ $summary['net_position'] >= 0 ? 'text-success' : 'text-danger' }} mb-0">{{ number_format($summary['net_position'], 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Filters</h5>
        </div>

        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="search" class="form-control" wire:model.live.debounce.400ms="search" placeholder="Bill number, patient, service">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model.live="status">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                @if($canFilterUsers)
                    <div class="col-md-2">
                        <label class="form-label">Issued By</label>
                        <select class="form-select" wire:model.live="issuedBy">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="todayOnly" wire:model.live="todayOnly">
                        <label class="form-check-label" for="todayOnly">Today</label>
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" class="form-control" wire:model.live="dateFrom" @disabled($todayOnly)>
                </div>

                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" class="form-control" wire:model.live="dateTo" @disabled($todayOnly)>
                </div>

                <div class="col-md-12">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetFilters">
                        <i class="bi bi-x-circle me-1"></i>
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex flex-wrap gap-3 justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $chartPayload['title'] }}</h5>

                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" wire:model.live="chartBreakdown">
                            <option value="services">Services</option>
                            <option value="users">Users</option>
                            <option value="departments">Departments</option>
                            <option value="payments">Payment Methods</option>
                            <option value="statuses">Bill Statuses</option>
                        </select>

                        <select class="form-select form-select-sm" wire:model.live="chartType">
                            <option value="bar">Bar</option>
                            <option value="doughnut">Doughnut</option>
                            <option value="line">Line</option>
                        </select>
                    </div>
                </div>

                <div class="card-body">
                    @if($chartValues->isEmpty())
                        <div class="text-center text-muted py-5">No chart data for the selected filters.</div>
                    @elseif($chartType === 'doughnut')
                        @php
                            $segments = [];
                            foreach ($chartValues as $index => $value) {
                                $start = $currentOffset;
                                $size = ($value / $chartTotal) * 100;
                                $currentOffset += $size;
                                $segments[] = $chartColors[$index % count($chartColors)] . ' ' . $start . '% ' . $currentOffset . '%';
                            }
                        @endphp

                        <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                            <div style="width: 220px; height: 220px; border-radius: 50%; background: conic-gradient({{ implode(', ', $segments) }});"></div>

                            <div class="flex-grow-1">
                                @foreach($chartLabels as $index => $label)
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span><span class="d-inline-block rounded me-2" style="width: 12px; height: 12px; background: {{ $chartColors[$index % count($chartColors)] }}"></span>{{ $label }}</span>
                                        <strong>{{ number_format($chartValues[$index], 2) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif($chartType === 'line')
                        <svg viewBox="0 0 300 130" class="w-100" role="img" aria-label="{{ $chartPayload['title'] }}">
                            <line x1="10" y1="110" x2="290" y2="110" stroke="#dee2e6" />
                            <line x1="10" y1="20" x2="10" y2="110" stroke="#dee2e6" />
                            <polyline points="{{ $linePoints }}" fill="none" stroke="#198754" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                            @foreach($chartValues as $index => $value)
                                @php
                                    $count = max($chartValues->count() - 1, 1);
                                    $x = 10 + (($index / $count) * 280);
                                    $y = 110 - (($value / $maxChartValue) * 90);
                                @endphp
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="#0d6efd" />
                            @endforeach
                        </svg>

                        <div class="row g-2 mt-3">
                            @foreach($chartLabels as $index => $label)
                                <div class="col-md-6 d-flex justify-content-between small">
                                    <span>{{ $label }}</span>
                                    <strong>{{ number_format($chartValues[$index], 2) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="d-grid gap-3">
                            @foreach($chartLabels as $index => $label)
                                @php $width = max(4, ($chartValues[$index] / $maxChartValue) * 100); @endphp
                                <div>
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ $label }}</span>
                                        <strong>{{ number_format($chartValues[$index], 2) }}</strong>
                                    </div>
                                    <div class="progress" style="height: 18px;">
                                        <div class="progress-bar" style="width: {{ $width }}%; background: {{ $chartColors[$index % count($chartColors)] }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Report Table</h5>

                    <select class="form-select form-select-sm w-auto" wire:model.live="reportBy">
                        <option value="services">Services</option>
                        <option value="users">Users</option>
                        <option value="departments">Departments</option>
                        <option value="payments">Payment Methods</option>
                        <option value="statuses">Bill Statuses</option>
                    </select>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($breakdownRows as $row)
                                    <tr>
                                        <td>{{ $row->label }}</td>
                                        <td class="text-end">{{ number_format($row->count ?? $row->quantity ?? 0) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($row->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No breakdown data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Bill Details</h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bill Number</th>
                            <th>Patient</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Due</th>
                            <th>Status</th>
                            <th>Issued By</th>
                            <th>Issued Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bills as $bill)
                            @php
                                $patientName = $bill->walkinPatient?->name
                                    ?? $bill->patientVisit?->patient?->name()
                                    ?? 'N/A';
                            @endphp
                            <tr>
                                <td>
                                    @if(auth()->user()->hasRole('medical_director'))
                                        {{ $bill->bill_number }}
                                    @else
                                        <a href="{{ auth()->user()->hasRole('administrator') ? route('admin.bills.show', $bill) : route('accountant.bills.show', $bill) }}">
                                            {{ $bill->bill_number }}
                                        </a>
                                    @endif
                                </td>
                                <td>{{ $patientName }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($bill->service_description, 35) }}</td>
                                <td class="text-end">{{ number_format($bill->amount, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($bill->due_amount, 2) }}</td>
                                <td>
                                    @if($bill->status === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif($bill->status === 'partial')
                                        <span class="badge bg-warning">Partial</span>
                                    @elseif($bill->status === 'pending')
                                        <span class="badge bg-danger">Pending</span>
                                    @else
                                        <span class="badge bg-secondary">Cancelled</span>
                                    @endif
                                </td>
                                <td>{{ $bill->issuedBy->name ?? 'N/A' }}</td>
                                <td>{{ $bill->issued_date?->format('M d, Y') ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No bills found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($bills->hasPages())
            <div class="card-footer">
                {{ $bills->links() }}
            </div>
        @endif
    </div>
</div>
