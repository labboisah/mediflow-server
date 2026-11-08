    <div class="row g-4">
        <!-- display todays date and clock reading current time -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h5>
                        <small class="text-muted">Welcome back, {{ auth()->user()->name }}! Here's a quick overview of today's activity.</small>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3">
                        <a href="{{ route('admin.patient-register.index') }}" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                            Patient Register
                        </a>
                        <div class="text-muted">
                            <i class="bi bi-calendar-event me-1"></i> {{ \Carbon\Carbon::now()->format('F j, Y') }}
                            <span class="mx-2">|</span>
                            <i class="bi bi-clock me-1"></i> <span id="current-time">{{ \Carbon\Carbon::now()->format('h:i:s A') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small text-muted">Patients</div>
                            <h3 class="mb-0">{{App\Models\Patient::all()->count()}}</h3>
                        </div>
                        <div class="text-success fs-3"> <i class="bi bi-people-fill"></i> </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- walkin patients -->

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small text-muted">Walk-in Patients</div>
                            <h3 class="mb-0">{{App\Models\WalkinPatient::all()->count()}}</h3>
                        </div>
                        <div class="text-primary fs-3"> <i class="bi bi-folder-fill"></i> </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small text-muted">Visits</div>
                            <h3 class="mb-0">{{ App\Models\PatientVisit::count() }}</h3>
                        </div>
                        <div class="text-warning fs-3"> <i class="bi bi-clipboard-data-fill"></i> </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small text-muted">Bills Issued</div>
                            <h3 class="mb-0">{{ App\Models\Bill::count() }}</h3>
                        </div>
                        <div class="text-danger fs-3"> <i class="bi bi-receipt-cutoff"></i> </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- payments -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small text-muted">Payments Recorded</div>
                            <h3 class="mb-0">{{ App\Models\Payment::count() }}</h3>
                        </div>
                        <div class="text-danger fs-3"> <i class="bi bi-currency-dollar"></i> </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $recentActivities = App\Models\AuditLog::with('actor')->limit(10)->latest()->get();
    @endphp

    <div class="row mt-4">
        <div class="col-12 col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Recent Hospital Activities</h5>

                    @if($recentActivities->isEmpty())
                        <div class="text-center text-muted py-4">
                            No activity to show yet. When the system has records, recent actions will appear here.
                        </div>
                    @else
                        <div class="list-group list-group-flush" style="max-height: 420px; overflow-y: auto;">
                            @foreach($recentActivities as $activity)
                                @php
                                    $actionLabel = ucwords(str_replace(['.', '_'], [' ', ' '], $activity->action));
                                    $modelLabel = $activity->model_type ? class_basename($activity->model_type) : null;
                                @endphp
                                <div class="list-group-item border-0 px-0 py-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-semibold">{{ $activity->actor?->name ?? 'System' }}</div>
                                            <div class="small text-muted">
                                                {{ $actionLabel }}
                                                @if($modelLabel)
                                                    on {{ $modelLabel }}
                                                    @if($activity->model_id)
                                                        #{{ $activity->model_id }}
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-end text-muted small">
                                            {{ $activity->created_at->format('M j, Y h:i A') }}
                                            <div>{{ $activity->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
