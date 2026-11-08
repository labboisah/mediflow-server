<!-- display patient vital signs -->

    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Continuation Sheet</h5>
        @if(auth()->user()->hasRole('doctor'))
            <a href="{{ route('patient.continuation.create', $patient) }}" class="btn btn-sm btn-light">
                <i class="bi bi-pencil-square me-1"></i> Manage
            </a>
        @endif
    </div>
    <div class="card-body">
        @php
            $continuation = $patient->continuations()
                ->with('writtenBy')
                ->latest('date')
                ->latest('time')
                ->latest('id')
                ->first();
        @endphp

        @if($continuation)
            <div class="row mb-3">
                <p class="mb-0 text-muted">
                    Written By:
                    <strong class="text-success">{{ $continuation->writtenBy->name ?? '' }}</strong>
                </p>
                <p class="h6">{{ $continuation->note }}</p>
                <hr>
                <div class="col-md-3">
                    <label class="form-label text-muted">Time</label>
                    <p class="h6">{{ $continuation->time }}</p>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted">Date</label>
                    <p class="h6">{{ date('M d, Y', strtotime($continuation->date)) }}</p>
                </div>
                @if(auth()->user()->hasRole('doctor'))
                    <div class="col-md-12">
                        <a href="{{ route('patient.continuation.create', $patient) }}" class="btn btn-sm btn-outline-primary">Edit in Continuation Sheet</a>
                    </div>
                @endif
            </div>
        @else
            <div class="alert alert-warning">No Continuation Sheet Recorded</div>
        @endif
    </div>
