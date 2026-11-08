@extends('layouts.modern')

@section('title', 'Show.Blade')
@section('page-title', 'Show.Blade')
@section('page-subtitle', 'Mediflow workspace.')
@php 
$patient = $prescription->patientVisit->patient;
@endphp
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
            <div class="col-md-5">
                <div class="card-body shadow p-4">
                    <h2>Prescribe Medication</h2>
                    <form action="{{ route('patient.prescription.add', $prescription) }}" method="POST">
                        @csrf
                        
                        <div class="form-group mb-2">
                            <label for="ward_id">Medicine Type</label>
                            <select name="medicine_type_id" id="medicine_type_id" class="form-control" required>
                                <option value="">Select Type of Medicine</option>
                                @foreach(App\Models\MedicineType::all() as $type)
                                <option value="{{$type->id}}">{{$type->name}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label for="medicine_id">Medicine</label>
                            <select name="medicine_id" id="medicine_id" class="form-control" required>
                                <option value="">Select Medicine</option>
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label for="date">Dosage</label>
                            <input type="text" class="form-control" name="dosage" required placeholder="Pls, specify value in g, mm, or ml">
                        </div>
                        <div class="form-group mb-2">
                            <label for="period">Period</label>
                            <select name="period" id="period" class="form-control">
                                @foreach([1,2,3,4,5,6,8,12] as $hour)
                                <option value="{{$hour}} hourly">{{$hour}} Hourly</option>
                                @endforeach

                                @foreach([1,2,3,4,5,6,7] as $day)
                                <option value="{{$day}} daily">{{$day}} Daily</option>
                                @endforeach

                                @foreach([1,2,3,4] as $week)
                                <option value="{{$week}} weekly">{{$week}} Weekly</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label for="time">Duration (Days)</label>
                            <input type="number" class="form-control" name="duration">
                        </div>

                        <button type="submit" class="btn btn-primary">Save Prescription</button>
                    </form>
                </div>
            </div>
            <div class="col-md-7">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Dosage</th>
                            <th>Period</th>
                            <th>Duration</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prescription->prescriptionItems as $pit)
                        <tr>
                            <td>{{$pit->medicine->name}}</td>
                            <td>{{$pit->dosage}}</td>
                            <td>{{$pit->period}}</td>
                            <td>{{$pit->duration}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <a href="{{route('patient.prescription.submit', $prescription)}}" class="btn btn-success">Submit to Pharmacy</a>
            </div>
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
