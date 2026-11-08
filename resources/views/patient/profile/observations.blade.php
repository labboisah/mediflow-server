<!-- display patient vital signs -->

    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Observation</h5>
        @if(auth()->user()->hasRole('nurse') || auth()->user()->hasRole('midwife'))
            <a href="{{ route('patient.observation.record', $patient) }}" class="btn btn-sm btn-light">
                <i class="bi bi-pencil-square me-1"></i> Manage
            </a>
        @endif
    </div>
    <div class="card-body">
       
        
                @foreach($patient->currentVisit()->observations as $observation)
                <div class="row mb-3">
                    <p class="mb-0 text-muted">
                        Visit on:
                    <strong class="text-success">{{ date('M d, Y',strtotime($patient->currentVisit()->visit_date))  ?? 'No Visit Recorded' }} @ {{ date('h:s A',strtotime($patient->currentVisit()->created_at))}}</strong>
                    </p>
                    <hr>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Recorded By</label>
                        <p class="h6">{{ $observation->recordedBy->name }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Time</label>
                        <p class="h6">{{ $observation->time }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Date</label>
                        <p class="h6">{{ date('M d, Y', strtotime($observation->date)) }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Temperature</label>
                        <p class="h6">{{ $observation->temperature ?? 'N/A' }}°C</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Blood Pressure</label>
                        <p class="h6">{{ $observation->blood_pressure_systolic ?? 'N/A' }}/{{ $observation->blood_pressure_diastolic ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Mate Pulse</label>
                        <p class="h6">{{ $observation->mate_pulse ?? 'N/A' }} bpm</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Respiration</label>
                        <p class="h6">{{ $observation->respiration ?? 'N/A' }} </p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Drop Rate</label>
                        <p class="h6">{{ $observation->drop_rate ?? 'N/A' }} </p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Constraction</label>
                        <p class="h6">{{ $observation->constraction ?? 'N/A' }} </p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Fits</label>
                        <p class="h6">{{ $observation->fits ?? 'N/A' }} </p>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-muted">Remark</label>
                        <p class="h6">{{ $observation->remark ?? 'N/A' }} </p>
                    </div>
                    @if(auth()->user()->hasRole('nurse') || auth()->user()->hasRole('midwife'))
                        <div class="col-md-12">
                            <a href="{{ route('patient.observation.record', $patient) }}" class="btn btn-sm btn-outline-primary">Edit in Observations</a>
                        </div>
                    @endif
                </div>
                <hr>
            
            @endforeach
        
    </div>
