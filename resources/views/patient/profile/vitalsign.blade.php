<!-- display patient vital signs -->

    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Vital Signs</h5>
        @if(auth()->user()->hasRole('nurse') || auth()->user()->hasRole('midwife'))
            <a href="{{ route('patient.vitalsign.create', $patient) }}" class="btn btn-sm btn-light">
                <i class="bi bi-pencil-square me-1"></i> Manage
            </a>
        @endif
    </div>
    <div class="card-body">
        @if($patient->visits()->exists())
            @foreach($patient->visits as $visit)
            @foreach($visit->vitalSigns as $vitalSign)
                <div class="row mb-3">
                     <p class="mb-0 text-muted">
                        Visit on:
                    <strong class="text-success">{{ date('M d, Y',strtotime($patient->currentVisit()->visit_date))  ?? 'No Visit Recorded' }} @ {{ date('h:s A',strtotime($patient->currentVisit()->created_at))}}</strong>
                    </p>
                    <hr>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Recorded By</label>
                        <p class="h6">{{ $vitalSign->recordedBy->name }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Date</label>
                        <p class="h6">{{ $vitalSign->recorded_date->format('M d, Y') }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Temperature</label>
                        <p class="h6">{{ $vitalSign->body_temperature ?? 'N/A' }}°C</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Blood Pressure</label>
                        <p class="h6">{{ $vitalSign->blood_pressure_systolic ?? 'N/A' }}/{{ $vitalSign->blood_pressure_diastolic ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Heart Rate</label>
                        <p class="h6">{{ $vitalSign->heart_rate ?? 'N/A' }} bpm</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Respiratory Rate</label>
                        <p class="h6">{{ $vitalSign->respiratory_rate ?? 'N/A' }} </p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Oxygen Saturation</label>
                        <p class="h6">{{ $vitalSign->oxygen_saturation ?? 'N/A' }} </p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Blood Glucose</label>
                        <p class="h6">{{ $vitalSign->blood_glucose ?? 'N/A' }} </p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Weight</label>
                        <p class="h6">{{ $vitalSign->weight ?? 'N/A' }} </p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Height</label>
                        <p class="h6">{{ $vitalSign->height ?? 'N/A' }} </p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Remark</label>
                        <p class="h6">{{ $vitalSign->notes ?? 'N/A' }} </p>
                    </div>
                    @if(auth()->user()->hasRole('nurse') || auth()->user()->hasRole('midwife'))
                        <div class="col-md-12">
                            <a href="{{ route('patient.vitalsign.create', $patient) }}" class="btn btn-sm btn-outline-primary">Edit in Vital Signs</a>
                        </div>
                    @endif
                </div>
                <hr>
            @endforeach
            @endforeach
        @else
            <p>No vital signs recorded yet.</p>
        @endif
    </div>
