@extends('layouts.modern')

@section('title', 'Record.Blade')
@section('page-title', 'Record.Blade')
@section('page-subtitle', 'Mediflow workspace.')
@section('header')
<div class="d-flex align-items-center gap-3">
    <i class="bi bi-person-vcard text-success" style="font-size: 2rem;"></i>
    <div>
        <h1 class="h3 mb-1">{{ $patient->demographic->full_name ?? 'Patient Details' }}</h1>
        <p class="mb-0 text-muted">
            Hospital Number:
            <strong class="text-success">{{ $patient->hospital_number }}</strong>
        </p>
    </div>
</div>
@endsection
@section('content')
    <div class="container">
        <div class="row">
            @if($patient->currentVisit()->prescriptions()->count()>0)
            @foreach($patient->currentVisit()->prescriptions as $prescription)
            @foreach($prescription->prescriptionItems->filter(fn($item) => $item->isStarted()) as $item)
            <div class="col-md-4 ">
                <div class="card-body">
                    <h2>{{$item->medicine->name}}</h2>
                    <form action="{{ route('patient.drugchart.register', $patient) }}" method="POST">
                        @csrf
                        <input type="hidden" value="{{$item->id}}" name="prescription_item_id">
                        <div class="form-group mb-2">
                            <label for="date">Dosage</label>
                            <input type="text" class="form-control" name="dosage" required placeholder="Example: {{$item->dosage}}">
                        </div>
                        
                        <div class="form-group mb-2">
                            <label for="time">Route</label>
                            <input type="text" class="form-control" name="route" placeholder="Example Oral, Injection">
                        </div>

                        <div class="form-group mb-2">
                            <label for="time">Time Dispense</label>
                            <input type="time" class="form-control" name="time" value={{date("h:i:s A")}}>
                        </div>

                        <div class="form-group mb-2">
                            <label for="comment">Reason for Not Dispensing</label>
                            <textarea class="form-control" name="comment" placeholder="Add reason for not dispensing..."></textarea>
                        </div>

                        <div class="form-group mb-2">
                            <label for="time">Dispensed By</label>
                            <input type="text" class="form-control" name="dispensed_by" value="{{auth()->user()->name}}" disabled>
                        </div>

                        <button type="submit" class="btn btn-primary">Add Drug Chart</button>
                    </form>
                </div>
            </div>
            @endforeach
            @endforeach
            @else
            <div class="alert alert-warning">No Prescription for the last visit of this patient  <a class="btn btn-outline-secondary" href="{{route('patient.show',$patient)}}"><i class="bi bi-arrow-left me-2"></i>Back to Patient Profile</a></div>
            @endif
            @if($patient->currentVisit()->prescriptions->flatMap->prescriptionItems->filter(fn($item) => $item->isStarted())->isEmpty())
                <div class="alert alert-warning">No started medication is available for drug chart. Ask the doctor to start a medication first.</div>
            @endif
        </div>
        
    </div>

    {{-- AJAX SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const medicineTypeSelect = document.getElementById('medicine_type_id');
    const medicineSelect = document.getElementById('medicine_id');

    const ajaxBaseUrl = "{{ url('/ajax/medicines') }}";

    medicineTypeSelect.addEventListener('change', function () {

        const medicineTypeId = this.value;

        medicineSelect.innerHTML =
            '<option value="" selected>Select Medicine</option>';
        
        if (!medicineTypeId) return;

        fetch(`${ajaxBaseUrl}/${medicineTypeId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error');
            }
            return response.json();
        })
        .then(data => {

            if (data.length === 0) {
                medicineSelect.innerHTML =
                    '<option disabled>No Medicine found</option>';
                return;
            }

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                medicineSelect.appendChild(option);
            });
        })
        .catch(error => {

            console.error('Error:', error);

            medicineSelect.innerHTML =
                '<option disabled>Error loading medicines</option>';
        });
    });
});
</script>
@endsection

