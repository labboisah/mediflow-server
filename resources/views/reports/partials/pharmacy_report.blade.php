{{-- Pharmacy-Specific Report Content --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="bi bi-shop"></i> Pharmacy Activity Report</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Prescriptions Dispensed</h6>
                    <h3 class="mb-0 text-success">{{ $reportData['prescriptions_dispensed'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Total Medicines Issued</h6>
                    <h3 class="mb-0 text-success">{{ $reportData['medicines_issued'] ?? 0 }}</h3>
                </div>
            </div>
        </div>

        {{-- Prescriptions Dispensed Table --}}
        @if(!empty($reportData['dispensed_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-receipt"></i> Prescriptions Dispensed</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Medicine</th>
                                <th>Quantity</th>
                                <th>Dosage</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['dispensed_details'] ?? [] as $dispensed)
                                <tr>
                                    <td>{{ $dispensed['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $dispensed['medicine'] ?? 'N/A' }}</td>
                                    <td>{{ $dispensed['quantity'] ?? 'N/A' }}</td>
                                    <td>{{ $dispensed['dosage'] ?? 'N/A' }}</td>
                                    <td><span class="badge bg-success">Dispensed</span></td>
                                    <td>{{ $dispensed['time'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No prescriptions dispensed</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>