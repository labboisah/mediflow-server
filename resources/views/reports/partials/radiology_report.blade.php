{{-- Radiology-Specific Report Content --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="bi bi-image"></i> Radiology Activity Report</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Radiographs Completed</h6>
                    <h3 class="mb-0 text-secondary">{{ $reportData['radiographs_completed'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Reports Written</h6>
                    <h3 class="mb-0 text-secondary">{{ $reportData['radiology_reports'] ?? 0 }}</h3>
                </div>
            </div>
        </div>

        {{-- Completed Radiographs Table --}}
        @if(!empty($reportData['radiographs_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-check-circle"></i> Completed Radiographs</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Radiograph Type</th>
                                <th>Body Part</th>
                                <th>Date Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['radiographs_details'] ?? [] as $radiograph)
                                <tr>
                                    <td>{{ $radiograph['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $radiograph['type'] ?? 'N/A' }}</td>
                                    <td>{{ $radiograph['body_part'] ?? 'N/A' }}</td>
                                    <td>{{ $radiograph['date_completed'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No radiographs completed</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Radiology Reports Table --}}
        @if(!empty($reportData['radiology_reports_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-file-earmark-text"></i> Radiology Reports</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Radiograph Type</th>
                                <th>Findings</th>
                                <th>Date Written</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['radiology_reports_details'] ?? [] as $report)
                                <tr>
                                    <td>{{ $report['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $report['type'] ?? 'N/A' }}</td>
                                    <td>{{ $report['findings'] ?? 'N/A' }}</td>
                                    <td>{{ $report['date_written'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No reports written</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>