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
           @if($patient->currentVisit()->admissions()->count() > 0)
            <div class="col-md-8 offset-2 ">
                <div class="card-body">
                    <h2>Record Fluid Balance Chart</h2>
                    <form action="{{ route('patient.fluidbalance.register', $patient) }}" method="POST">
                        @csrf
                        <div class="row">

                            <div class="col-md-6">
                                <p class="text-muted">INPUT (ML)</p>

                                <div class="form-group mb-2">
                                    <label for="date">Date</label>
                                    <input type="date" class="form-control" name="date" value="{{ date('Y-m-d') }}" placeholder="Select date">
                                </div>
                                <div class="form-group mb-2">
                                    <label for="time">Time</label>
                                    <input type="time" class="form-control" name="time" value="{{ date('H:i') }}" placeholder="Select time">
                                </div>

                                <div class="form-group mb-2">
                                    <label for="date">Type In</label>
                                    <input type="text" class="form-control" name="type_in" placeholder="e.g. IV fluid, oral fluids">
                                </div>
                                
                                <div class="form-group mb-2">
                                    <label for="time">Tube In</label>
                                    <input type="text" class="form-control" name="tube_in" placeholder="e.g. nasogastric tube">
                                </div>

                                <div class="form-group mb-2">
                                    <label for="oral">Oral</label>
                                    <input type="text" id="oral" class="form-control" name="oral" placeholder="e.g. 250 ml">
                                </div>

                                <div class="form-group mb-2">
                                    <label for="time">IV</label>
                                    <input type="text" class="form-control" name="IV" placeholder="e.g. 500 ml">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <p class="text-muted">OUTPUT (ML)</p>

                                <div class="form-group mb-2">
                                    <label for="date">Type Out</label>
                                    <input type="text" class="form-control" name="type_out" placeholder="e.g. urine, vomit, wound drainage">
                                </div>
                                
                                <div class="form-group mb-2">
                                    <label for="time">Tube/Vomit</label>
                                    <input type="text" class="form-control" name="tube_out" placeholder="e.g. drainage tubing, vomit">
                                </div>

                                <div class="form-group mb-2">
                                    <label for="oral">Urine</label>
                                    <input type="text" class="form-control" name="urine">
                                </div>

                                <div class="form-group mb-2">
                                    <label for="time">Faces</label>
                                    <input type="text" class="form-control" name="faces">
                                </div>

                                <div class="form-group mb-2">
                                    <label for="time">Recorded By</label>
                                    <input type="text" class="form-control" name="recorded_by" value="{{auth()->user()->name}}" disabled>
                                </div>
                                <div class="form-group mb-2">
                                    <label for="time"></label>
                                    <button type="submit" class="btn btn-primary">Add Fluid Chart</button>
                                </div>
                            </div>
                        </div>
                        

                        
                    </form>
                </div>
            </div>
            @else
                <div class="alert alert-warning">No admission to record Fluid Balance Chart <a class="btn btn-outline-secondary" href="{{route('patient.show',$patient)}}"><i class="bi bi-arrow-left me-2"></i>Back to Patient Profile</a></div>
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
