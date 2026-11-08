{{-- Midwife-Specific Report Content --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="bi bi-hospital"></i> Midwife Activity Report</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Antenatal Care Records</h6>
                    <h3 class="mb-0 text-success">{{ $reportData['antenatal_records'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Deliveries Managed</h6>
                    <h3 class="mb-0 text-success">{{ $reportData['deliveries'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Postnatal Exams</h6>
                    <h3 class="mb-0 text-success">{{ $reportData['postnatal_exams'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Newborn Exams</h6>
                    <h3 class="mb-0 text-success">{{ $reportData['newborn_exams'] ?? 0 }}</h3>
                </div>
            </div>
        </div>

        {{-- Antenatal Care Table --}}
        @if(!empty($reportData['antenatal_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-clipboard-check"></i> Antenatal Care Records</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Gestational Weeks</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['antenatal_details'] ?? [] as $antenatal)
                                <tr>
                                    <td>{{ $antenatal['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $antenatal['gestational_weeks'] ?? 'N/A' }} weeks</td>
                                    <td>
                                        @switch($antenatal['status'] ?? 'normal')
                                            @case('normal')
                                                <span class="badge bg-success">Normal</span>
                                                @break
                                            @case('complicated')
                                                <span class="badge bg-warning">Complicated</span>
                                                @break
                                            @case('high_risk')
                                                <span class="badge bg-danger">High Risk</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>{{ $antenatal['time'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No antenatal records created</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Deliveries Table --}}
        @if(!empty($reportData['deliveries_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-person-check"></i> Deliveries Managed</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Delivery Type</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['deliveries_details'] ?? [] as $delivery)
                                <tr>
                                    <td>{{ $delivery['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $delivery['delivery_type'] ?? 'N/A' }}</td>
                                    <td><span class="badge bg-secondary">{{ $delivery['status'] ?? 'Completed' }}</span></td>
                                    <td>{{ $delivery['time'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No deliveries recorded</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>