@php
    $chartLabels = collect($chartPayload['labels'])->take(10)->values();
    $chartValues = collect($chartPayload['values'])->take(10)->map(fn ($value) => (int) $value)->values();
    $maxChartValue = max($chartValues->max() ?: 1, 1);
    $chartColors = ['#198754', '#0d6efd', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d', '#0dcaf0', '#6610f2'];
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
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Activities Report</h1>
            <p class="text-muted mb-0">{{ $department->name }} department activity by services, investigations, and users.</p>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <select class="form-select" onchange="if (this.value) window.location.href = this.value">
                @foreach($departments as $departmentOption)
                    <option value="{{ route('reports.activities.show', $departmentOption) }}" @selected($departmentOption->id === $department->id)>
                        {{ $departmentOption->name }}
                    </option>
                @endforeach
            </select>

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
                <p class="text-muted small mb-1">Total Activities</p>
                <h4 class="mb-0">{{ number_format($summary['total']) }}</h4>
            </div>
        </div>

        @if($department->services->isNotEmpty())
            <div class="col-md-3">
                <div class="border rounded p-3 bg-white h-100">
                    <p class="text-muted small mb-1">Services</p>
                    <h4 class="text-success mb-0">{{ number_format($summary['service_count']) }}</h4>
                </div>
            </div>
        @endif

        @if($department->investigationTypes->isNotEmpty())
            <div class="col-md-3">
                <div class="border rounded p-3 bg-white h-100">
                    <p class="text-muted small mb-1">Investigations</p>
                    <h4 class="text-primary mb-0">{{ number_format($summary['investigation_count']) }}</h4>
                </div>
            </div>
        @endif

        <div class="col-md-3">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">Clinical/User Notes</p>
                <h4 class="text-info mb-0">{{ number_format($summary['clinical_count']) }}</h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">Active Users</p>
                <h4 class="text-warning mb-0">{{ number_format($summary['active_users']) }}</h4>
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
                    <input type="search" class="form-control" wire:model.live.debounce.400ms="search" placeholder="User, service, investigation, status">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Activity Type</label>
                    <select class="form-select" wire:model.live="activityType">
                        <option value="">All Activities</option>
                        @foreach($activityTypes as $activityTypeOption)
                            <option value="{{ $activityTypeOption['key'] }}">{{ $activityTypeOption['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">User</label>
                    <select class="form-select" wire:model.live="userId">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="activityTodayOnly" wire:model.live="todayOnly">
                        <label class="form-check-label" for="activityTodayOnly">Today</label>
                    </div>
                </div>

                <div class="col-md-1">
                    <label class="form-label">From</label>
                    <input type="date" class="form-control" wire:model.live="dateFrom" @disabled($todayOnly)>
                </div>

                <div class="col-md-1">
                    <label class="form-label">To</label>
                    <input type="date" class="form-control" wire:model.live="dateTo" @disabled($todayOnly)>
                </div>

                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters">
                        <i class="bi bi-x-circle"></i>
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
                            <option value="types">Activity Types</option>
                            <option value="users">Users</option>
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
                                        <strong>{{ number_format($chartValues[$index]) }}</strong>
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
                                    <strong>{{ number_format($chartValues[$index]) }}</strong>
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
                                        <strong>{{ number_format($chartValues[$index]) }}</strong>
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
                <div class="card-header bg-light">
                    <h5 class="mb-0">Activity Types</h5>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Activity</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($typeBreakdown as $row)
                                    <tr>
                                        <td>{{ $row->label }}</td>
                                        <td class="text-end fw-bold">{{ number_format($row->count) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">No activity found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($department->services->isNotEmpty() || $department->investigationTypes->isNotEmpty())
        <div class="row g-4 mb-4">
            @if($department->services->isNotEmpty())
                <div class="{{ $department->investigationTypes->isNotEmpty() ? 'col-lg-6' : 'col-lg-12' }}">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Department Services</h5>
                        </div>

                        <div class="card-body">
                            @foreach($department->services as $service)
                                <span class="badge text-bg-light border me-1 mb-2">{{ $service->name }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if($department->investigationTypes->isNotEmpty())
                <div class="{{ $department->services->isNotEmpty() ? 'col-lg-6' : 'col-lg-12' }}">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Investigation Types</h5>
                        </div>

                        <div class="card-body">
                            @foreach($department->investigationTypes as $type)
                                <div class="mb-3">
                                    <div class="fw-semibold">{{ $type->name }}</div>
                                    <div class="mt-1">
                                        @forelse($type->investigations as $investigation)
                                            <span class="badge text-bg-light border me-1 mb-2">{{ $investigation->name }}</span>
                                        @empty
                                            <span class="text-muted small">No investigations configured.</span>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">User Breakdown</h5>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th class="text-end">Activities</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($userBreakdown as $row)
                                    <tr>
                                        <td>{{ $row->label }}</td>
                                        <td class="text-end fw-bold">{{ number_format($row->count) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">No users found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Recorded Activities</h5>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Activity</th>
                                    <th>Details</th>
                                    <th>User</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentActivities as $activity)
                                    <tr>
                                        <td>{{ $activity->activity_label }}</td>
                                        <td>{{ $activity->subject ?? 'Record #' . $activity->id }}</td>
                                        <td>{{ $activity->user_name ?? 'Unknown' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($activity->occurred_at)->format('M d, Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recorded activities found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($recentActivities->hasPages())
                    <div class="card-footer">
                        {{ $recentActivities->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
