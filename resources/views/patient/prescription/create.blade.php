@extends('layouts.modern')

@section('title', 'Create.Blade')
@section('page-title', 'Create.Blade')
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
            <div class="col-md-6 offset-3">
                <div class="card-body shadow p-4">
                    <h2>Prescribe Medication</h2>
                    <form action="{{ route('patient.prescription.store', $patient) }}" method="POST">
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
                            <label for="medicine_name">Medicine</label>
                            <input type="text" name="medicine_name" id="medicine_name" class="form-control" list="medicine_options" required placeholder="Type or select medicine">
                            <datalist id="medicine_options"></datalist>
                            <small class="text-muted">Existing medicines show generic name, company, stock, and price.</small>
                        </div>
                        <div class="form-group mb-2">
                            <label for="treatment_diagnosis">Treatment / Infection / Disease</label>
                            <textarea name="treatment_diagnosis" id="treatment_diagnosis" class="form-control" rows="2" required></textarea>
                        </div>

                        <div class="form-group mb-2">
                            <label for="ward_id">Route</label>
                            <select name="route_id" id="route_id" class="form-control" required>
                                <option value="">Select Route of Medication</option>
                                @foreach(App\Models\Route::all() as $route)
                                <option value="{{$route->id}}">{{$route->name}}</option>
                                @endforeach
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
        </div>
        
    </div>

    {{-- AJAX SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const medicineTypeSelect = document.getElementById('medicine_type_id');
    const medicineOptions = document.getElementById('medicine_options');

    const ajaxBaseUrl = "{{ url('/ajax/medicines') }}";

    // Load medicines when type changes
    medicineTypeSelect.addEventListener('change', function () {

        const medicineTypeId = this.value;

        medicineOptions.innerHTML = '';

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

            if (!data.length) {
                return;
            }

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.name;
                option.label = `${item.name} - ${item.available ? 'Available' : 'Not available'} - ₦${Number(item.selling_price || 0).toFixed(2)}`;
                medicineOptions.appendChild(option);
            });

        })
        .catch(error => {

            console.error(error);

        });
    });

});
</script>
@endsection

