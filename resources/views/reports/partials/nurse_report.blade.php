{{-- Nurse-Specific Report Content --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="bi bi-heart-pulse"></i> Nurse Activity Report</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Patients Attended</h6>
                    <h3 class="mb-0 text-danger">{{ $reportData['patients_attended'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Vital Signs Recorded</h6>
                    <h3 class="mb-0 text-danger">{{ $reportData['vital_signs_recorded'] ?? 0 }}</h3>
                </div>
            </div>
        </div>

        {{-- Vital Signs Table --}}
        @if(!empty($reportData['vital_signs_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-thermometer"></i> Vital Signs Details</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Blood Pressure</th>
                                <th>Temperature</th>
                                <th>Pulse</th>
                                <th>Respiration</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['vital_signs_details'] ?? [] as $vital)
                                <tr>
                                    <td>{{ $vital['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $vital['blood_pressure'] ?? 'N/A' }}</td>
                                    <td>{{ $vital['temperature'] ?? 'N/A' }}°C</td>
                                    <td>{{ $vital['pulse'] ?? 'N/A' }} bpm</td>
                                    <td>{{ $vital['respiration'] ?? 'N/A' }} /min</td>
                                    <td>{{ $vital['time'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No vital signs recorded</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>