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
            <h1 class="h3 mb-1">My Activities</h1>
            <p class="text-muted mb-0">Review activities recorded under your user account.</p>
        </div>

        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>
                Print
            </button>

            <a class="btn btn-danger" href="{{ $pdfUrl }}">
                <i class="bi bi-file-earmark-pdf me-1"></i>
                PDF
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">User</p>
                <h5 class="mb-0">{{ $user->name }}</h5>
            </div>
        </div>

        <div class="col-md-4">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">Total Activities</p>
                <h4 class="text-success mb-0">{{ number_format($summary['total']) }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="border rounded p-3 bg-white h-100">
                <p class="text-muted small mb-1">Activity Types</p>
                <h4 class="text-primary mb-0">{{ number_format($summary['activity_types']) }}</h4>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Filters</h5>
        </div>

        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Activity Type</label>
                    <select class="form-select" wire:model.live="activityType">
                        <option value="">All Activities</option>
                        @foreach($activityTypes as $activityTypeOption)
                            <option value="{{ $activityTypeOption['key'] }}">{{ $activityTypeOption['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="myActivityTodayOnly" wire:model.live="todayOnly">
                        <label class="form-check-label" for="myActivityTodayOnly">Today</label>
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

                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters">
                        <i class="bi bi-x-circle me-1"></i>
                        Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex flex-wrap gap-3 justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $chartPayload['title'] }}</h5>

                    <select class="form-select form-select-sm w-auto" wire:model.live="chartType">
                        <option value="bar">Bar</option>
                        <option value="doughnut">Doughnut</option>
                        <option value="line">Line</option>
                    </select>
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

                        <div class="d-flex flex-column align-items-center gap-3">
                            <div style="width: 200px; height: 200px; border-radius: 50%; background: conic-gradient({{ implode(', ', $segments) }});"></div>

                            <div class="w-100">
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
                                <div class="col-md-12 d-flex justify-content-between small">
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

        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Activity Breakdown</h5>
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

        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Activity Details</h5>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Activity</th>
                                    <th>Details</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activities as $activity)
                                    <tr>
                                        <td>{{ $activity->activity_label }}</td>
                                        <td>{{ $activity->subject ?? 'Record #' . $activity->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($activity->occurred_at)->format('M d, Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No activities found for the selected period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($activities->hasPages())
                    <div class="card-footer">
                        {{ $activities->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
