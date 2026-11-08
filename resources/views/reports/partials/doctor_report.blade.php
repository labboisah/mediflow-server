{{-- Doctor-Specific Report Content --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-stethoscope"></i> Doctor Activity Report</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Consultations</h6>
                    <h3 class="mb-0 text-primary">{{ $reportData['consultations'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Investigation Requests</h6>
                    <h3 class="mb-0 text-info">{{ $reportData['investigation_requests'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Service Requests</h6>
                    <h3 class="mb-0 text-warning">{{ $reportData['service_requests'] ?? 0 }}</h3>
                </div>
            </div>
        </div>

        {{-- Consultations Table --}}
        @if(!empty($reportData['consultations_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-list-check"></i> Consultations Details</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Diagnosis</th>
                                <th>Treatment</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['consultations_details'] ?? [] as $consultation)
                                <tr>
                                    <td>{{ $consultation['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $consultation['diagnosis'] ?? 'N/A' }}</td>
                                    <td>{{ $consultation['treatment'] ?? 'N/A' }}</td>
                                    <td>{{ $consultation['time'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No consultations recorded</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Investigation Requests Table --}}
        @if(!empty($reportData['investigation_requests_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-beaker"></i> Investigation Requests Details</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Investigation Type</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['investigation_requests_details'] ?? [] as $investigation)
                                <tr>
                                    <td>{{ $investigation['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $investigation['type'] ?? 'N/A' }}</td>
                                    <td><span class="badge bg-secondary">{{ $investigation['status'] ?? 'Pending' }}</span></td>
                                    <td>{{ $investigation['time'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No investigation requests recorded</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>