{{-- Record Officer-Specific Report Content --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-file-earmark"></i> Record Officer Activity Report</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Patient Visits</h6>
                    <h3 class="mb-0 text-primary">{{ $reportData['patient_visits'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Bills Created</h6>
                    <h3 class="mb-0 text-primary">{{ $reportData['bills_created'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Service Requests</h6>
                    <h3 class="mb-0 text-info">{{ $reportData['service_requests'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Investigation Requests</h6>
                    <h3 class="mb-0 text-warning">{{ $reportData['investigation_requests'] ?? 0 }}</h3>
                </div>
            </div>
        </div>

        {{-- Patient Visits Table --}}
        @if(!empty($reportData['patient_visits_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-calendar-check"></i> Patient Visits</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Visit Type</th>
                                <th>Department</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['patient_visits_details'] ?? [] as $visit)
                                <tr>
                                    <td>{{ $visit['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $visit['visit_type'] ?? 'N/A' }}</td>
                                    <td>{{ $visit['department'] ?? 'N/A' }}</td>
                                    <td>{{ $visit['time'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No patient visits recorded</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Bills Created Table --}}
        @if(!empty($reportData['bills_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-receipt"></i> Bills Created</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Bill Amount</th>
                                <th>Status</th>
                                <th>Date Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['bills_details'] ?? [] as $bill)
                                <tr>
                                    <td>{{ $bill['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $bill['amount'] ?? '0' }}</td>
                                    <td><span class="badge bg-secondary">{{ $bill['status'] ?? 'Pending' }}</span></td>
                                    <td>{{ $bill['date_created'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No bills created</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Service Requests Table --}}
        @if(!empty($reportData['service_requests_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-tools"></i> Service Requests</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Service Type</th>
                                <th>Status</th>
                                <th>Date Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['service_requests_details'] ?? [] as $service)
                                <tr>
                                    <td>{{ $service['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $service['type'] ?? 'N/A' }}</td>
                                    <td><span class="badge bg-secondary">{{ $service['status'] ?? 'Pending' }}</span></td>
                                    <td>{{ $service['date_created'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No service requests recorded</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>