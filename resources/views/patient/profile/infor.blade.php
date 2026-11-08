<div class="card-header bg-success text-white">
    <h5 class="mb-0"><i class="bi bi-person-fill me-2"></i>Personal Information</h5>
</div>
<div class="card-body">
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label text-muted">Hospital Number</label>
            <p class="h5"><span class="badge bg-primary">{{ $patient->hospital_number }}</span></p>
        </div>
        <div class="col-md-6">
            <label class="form-label text-muted">Full Name</label>
            <p class="h5">{{ $patient->demographic->full_name ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label text-muted">Gender</label>
            <p class="h6">{{ $patient->demographic->gender ?? 'N/A' }}</p>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted">Date of Birth</label>
            <p class="h6">{{ $patient->demographic->date_of_birth ? $patient->demographic->date_of_birth->format('M d, Y') : 'N/A' }}</p>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted">Age</label>
            <p class="h6">{{ $patient->demographic->age ?? 'N/A' }} years</p>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted">Marital Status</label>
            <p class="h6">{{ $patient->demographic->marital_status ?? 'N/A' }}</p>
        </div>
    </div>

    <hr>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label text-muted">Occupation</label>
            <p class="h6">{{ $patient->demographic->occupation ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label text-muted">LGA</label>
            <p class="h6">{{ $patient->demographic->lga->id ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label text-muted">Phone Number</label>
            <p class="h6"><a href="tel:{{ $patient->demographic->phone_number }}">{{ $patient->demographic->phone_number ?? 'N/A' }}</a></p>
        </div>
        <div class="col-md-6">
            <label class="form-label text-muted">Email</label>
            <p class="h6"><a href="mailto:{{ $patient->demographic->email }}">{{ $patient->demographic->email ?? 'N/A' }}</a></p>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label text-muted">Address</label>
        <p class="h6">{{ $patient->demographic->address ?? 'N/A' }}</p>
    </div>
    @if(auth()->user()->hasRole('record'))
        <div class="d-flex gap-2 pt-3 border-top">
            <a href="{{ route('record.patients.edit.form', $patient) }}" class="btn btn-success">
                <i class="bi bi-pencil-square me-2"></i>Edit Patient Information
            </a>
            <a href="{{ route('record.patients.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to List
            </a>
        </div>
    @endif
    <hr>

    <div class="mb-3">
        <label class="form-label text-muted">Registration Date</label>
        <p class="h6">{{ $patient->registration_date->format('M d, Y \a\t H:i') }}</p>
    </div>
    <div class="mb-3">
        <label class="form-label text-muted">Patient Type</label>
        <p>
            @if($patient->is_walkIn)
                <span class="badge bg-warning text-dark"><i class="bi bi-person-check me-1"></i>Walk-In Patient</span>
            @else
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Scheduled Patient</span>
            @endif
        </p>
    </div>
</div>