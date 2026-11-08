{{-- Lab Technician-Specific Report Content --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-beaker"></i> Lab Technician Activity Report</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Investigations Completed</h6>
                    <h3 class="mb-0 text-info">{{ $reportData['investigations_completed'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Investigations Pending</h6>
                    <h3 class="mb-0 text-warning">{{ $reportData['investigations_pending'] ?? 0 }}</h3>
                </div>
            </div>
        </div>

        {{-- Completed Investigations Table --}}
        @if(!empty($reportData['investigations_completed_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-check-circle"></i> Completed Investigations</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Investigation Type</th>
                                <th>Result</th>
                                <th>Date Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['investigations_completed_details'] ?? [] as $investigation)
                                <tr>
                                    <td>{{ $investigation['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $investigation['type'] ?? 'N/A' }}</td>
                                    <td>{{ $investigation['result'] ?? 'N/A' }}</td>
                                    <td>{{ $investigation['date_completed'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No investigations completed</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Pending Investigations Table --}}
        @if(!empty($reportData['investigations_pending_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-hourglass-split"></i> Pending Investigations</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Investigation Type</th>
                                <th>Status</th>
                                <th>Date Requested</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['investigations_pending_details'] ?? [] as $investigation)
                                <tr>
                                    <td>{{ $investigation['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $investigation['type'] ?? 'N/A' }}</td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                    <td>{{ $investigation['date_requested'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No pending investigations</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>