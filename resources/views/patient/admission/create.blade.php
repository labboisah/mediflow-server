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
                    <h2>Admit Patient</h2>
                    <form action="{{ route('patient.admission.store', $patient) }}" method="POST">
                        @csrf
                        <div class="form-group mb-2">
                            <label for="date">Date</label>
                            <input type="date" class="form-control" name="date" value={{date(now())}}>
                        </div>
                        <div class="form-group mb-2">
                            <label for="time">Time</label>
                            <input type="time" class="form-control" name="time" value={{date("h:i:s A")}}>
                        </div>
                        <div class="form-group mb-2">
                            <label for="note">Note</label>
                            <textarea name="note" class="form-control" id="" cols="100%" rows="3"></textarea>
                        </div>
                        <div class="form-group mb-2">
                            <label for="ward_id">Ward</label>
                            <select name="ward_id" id="ward_id" class="form-control" required>
                                <option value="">Select Ward</option>    
                                @foreach(App\Models\Ward::all() as $ward)
                                    <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label for="bed_id">Bed Number</label>
                            <select name="bed_id" id="bed_id" class="form-control" required>
                                <option value="">Select Bed</option>
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label for="days">For How Long?</label>
                            <select name="days" id="days" class="form-control" required>
                            @for($days=1; $days<= 14; $days++)    
                                <option value="{{$days}}">{{$days}} Days</option>
                            @endfor
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Admit Patient</button>
                    </form>
                </div>
            </div>
        </div>
        
    </div>

    {{-- AJAX SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const wardSelect = document.getElementById('ward_id');
    const bedSelect = document.getElementById('bed_id');

    const ajaxBaseUrl = "{{ url('/ajax/beds') }}";

    wardSelect.addEventListener('change', function () {

        const wardId = this.value;

        bedSelect.innerHTML =
            '<option value="" selected>Select Bed</option>';
        
        if (!wardId) return;

        fetch(`${ajaxBaseUrl}/${wardId}`, {
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
                bedSelect.innerHTML =
                    '<option disabled>No bed found</option>';
                return;
            }

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.bed_no;
                bedSelect.appendChild(option);
            });
        })
        .catch(error => {

            console.error('Error:', error);

            bedSelect.innerHTML =
                '<option disabled>Error loading beds</option>';
        });
    });
});
</script>
@endsection
