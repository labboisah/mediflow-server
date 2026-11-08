{{-- Pharmacist-Specific Report Content --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="bi bi-capsule"></i> Pharmacist Activity Report</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Prescriptions Written</h6>
                    <h3 class="mb-0 text-warning">{{ $reportData['prescriptions'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Medicines Dispensed</h6>
                    <h3 class="mb-0 text-warning">{{ $reportData['medicines_dispensed'] ?? 0 }}</h3>
                </div>
            </div>
        </div>

        {{-- Prescriptions Table --}}
        @if(!empty($reportData['prescriptions_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-receipt"></i> Prescriptions Details</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Medicine</th>
                                <th>Dosage</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['prescriptions_details'] ?? [] as $prescription)
                                <tr>
                                    <td>{{ $prescription['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $prescription['medicine'] ?? 'N/A' }}</td>
                                    <td>{{ $prescription['dosage'] ?? 'N/A' }}</td>
                                    <td>{{ $prescription['quantity'] ?? 'N/A' }}</td>
                                    <td><span class="badge bg-secondary">{{ $prescription['status'] ?? 'Active' }}</span></td>
                                    <td>{{ $prescription['time'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No prescriptions recorded</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Medicines Dispensed Table --}}
        @if(!empty($reportData['medicines_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-box-seam"></i> Medicines Dispensed</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Medicine</th>
                                <th>Quantity Dispensed</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['medicines_details'] ?? [] as $medicine)
                                <tr>
                                    <td>{{ $medicine['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $medicine['medicine'] ?? 'N/A' }}</td>
                                    <td>{{ $medicine['quantity'] ?? 'N/A' }}</td>
                                    <td>{{ $medicine['time'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No medicines dispensed</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>