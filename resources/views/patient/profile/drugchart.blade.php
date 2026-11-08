@if($patient->currentVisit()->prescriptions()->count() > 0)
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-capsule-pill me-2"></i>Drug Chart</h5>
    @if(auth()->user()->hasRole('nurse') || auth()->user()->hasRole('midwife'))
        <a href="{{ route('patient.drugchart.record', $patient) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil-square me-1"></i> Manage
        </a>
    @endif
</div>
@foreach($patient->currentVisit()->prescriptions as $prescription)
@foreach($prescription->prescriptionItems as $pItem)
<div class="row">
    <div class="col-md-5">
        <div class="row mb-3">
            <p class="mb-0 text-muted">
                Drug:
            <strong class="text-success">{{$pItem->medicine->name}}</strong>
            </p>
            <hr>
            
            <div class="col-md-3">
                <label class="form-label text-muted">Prescribed By</label>
                <p class="h6">{{ $prescription->prescribedBy->name ?? ''}}</p>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted">Dosage</label>
                <p class="h6">{{ $pItem->dosage }}</p>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted">Period</label>
                <p class="h6">{{ $pItem->period }}</p>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted">Duration</label>
                <p class="h6">{{ $pItem->duration }}</p>
            </div>
            <!-- medication status -->
            <div class="col-md-3">
                <label class="form-label text-muted">Status</label>
                <p class="badge badge-success text-white">{{ $pItem->medication_status }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <p class="text-muted text-center">{{$pItem->medicine->name}} Drug Chart</p>
        @if($pItem->drugCharts()->count() > 0)
           <table class="table">
            <thead>
                <tr>
                    <th>Dispense By</th>
                    <th>Dosage</th>
                    <th>Route</th>
                    <th>Date</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pItem->drugCharts as $drugChart)
                    @if($drugChart->comment == null)
                    <tr>
                        <td>{{ $drugChart->dispensedBy->name ?? ''}}</td>
                        <td>{{ $pItem->dosage }}</td>
                        <td>{{ $drugChart->route }}</td>
                        <td>{{ date('d M, Y', strtotime($drugChart->date)) }}</td>
                        <td>{{ date('h:i:s A', strtotime($drugChart->time)) }}</td>
                    </tr>
                    @else
                    <tr>
                        <td colspan="5" class="text-center text-danger">{{ $drugChart->comment ?? 'No reason provided' }}</td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
           </table>
            
                
            
        @else
            <div class="alert alert-warning"> No Drug Chart found</div>
        @endif
        @if(auth()->user()->hasRole('nurse') || auth()->user()->hasRole('midwife'))
            <a href="{{ route('patient.drugchart.record', $patient) }}" class="btn btn-sm btn-outline-primary">Edit in Drug Chart</a>
        @endif
    </div>
</div>

@endforeach
@endforeach
@else
<div class="alert alert-warning">No prescription of medication in the last visit of this patient</div>
@endif
