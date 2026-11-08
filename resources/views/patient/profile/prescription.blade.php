@if($patient->currentVisit()->prescriptions()->count() > 0)
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Prescription of Medication</h4>
        @if(auth()->user()->hasRole('doctor'))
            <a href="{{ route('patient.prescription.create', $patient) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-circle me-1"></i> New Prescription
            </a>
        @endif
    </div>
    @foreach($patient->currentVisit()->prescriptions as $prescription)
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <p class="text-muted mb-0">Prescribed by {{ $prescription->prescribedBy?->name ?? 'N/A' }} @ {{ $prescription->created_at }}</p>
            @if(auth()->user()->hasRole('doctor'))
                <div class="d-flex gap-1">
                    <a href="{{ route('patient.prescription.create', ['patient' => $patient, 'prescription' => $prescription->id]) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('patient.prescription.destroy', $prescription) }}" onsubmit="return confirm('Delete this prescription and its medicine records?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash me-1"></i> Delete
                        </button>
                    </form>
                </div>
            @endif
        </div>
        <p><b>Treatment / Infection / Disease:</b> {{ $prescription->treatment_diagnosis ?? 'N/A' }}</p>
        <hr>
        @foreach($prescription->prescriptionItems as $item)
        <p><b>{{$loop->iteration}}. Medicine : {{$item->medicine->name}}</b></p>
        <p>Company : {{ $item->medicine->manufacturer ?? 'N/A' }}</p>
        <p>Availability : {{ $item->medicine->availabilityLabel() }}</p>
        <p>Amount : &#8358;{{ number_format($item->medicine->latestSellingPrice(), 2) }}</p>
        <p>Dosage : {{$item->dosage}}</p>
        <p>Period : {{$item->period}}</p>
        <p>For : {{$item->duration}} Days</p>
        <p>
            Status :
            <span class="badge bg-{{ $item->isStarted() ? 'success' : 'secondary' }}">
                {{ $item->isStarted() ? 'Started' : 'Stopped' }}
            </span>
            @if($item->medication_status_changed_at)
                <small class="text-muted">since {{ $item->medication_status_changed_at->format('M d, Y h:i A') }}</small>
            @endif
        </p>
        @if(auth()->user()->hasRole('doctor'))
            @if($item->isStarted())
                <form method="POST" action="{{ route('patient.prescription.item.stop', $item) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-pause-circle me-1"></i> Stop Medication
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('patient.prescription.item.start', $item) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-play-circle me-1"></i> Start Medication
                    </button>
                </form>
            @endif
        @endif
        <hr>
        @endforeach
        <p class="fw-bold">Prescription Amount: &#8358;{{ number_format($prescription->prescriptionItems->sum(fn($item) => $item->medicine?->latestSellingPrice() ?? 0), 2) }}</p>
    </div>
    @endforeach
@else
@if(auth()->user()->hasRole('doctor'))
    <a href="{{ route('patient.prescription.create', $patient) }}" class="btn btn-sm btn-outline-primary mb-2">
        <i class="bi bi-plus-circle me-1"></i> Create Prescription
    </a>
@endif
<p class="text-muted">No Prescription record found for this patient.</p>
@endif
