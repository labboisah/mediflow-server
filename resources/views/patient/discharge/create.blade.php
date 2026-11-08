@extends('layouts.modern')

@section('title', 'Create.Blade')
@section('page-title', 'Create.Blade')
@section('page-subtitle', 'Mediflow workspace.')
@php 
$patient = $admission->patientVisit->patient;
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
            <div class="col-md-6 offset-3">
                <div class="card-body shadow p-4">
                    <h5>Discharge Patient</h5>
                    <form action="{{ route('patient.discharge.store', $admission) }}" method="POST">
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
                            <label for="time">Discharge Note</label>
                            <textarea name="reason" class="form-control" id="" cols="100%" rows="3"></textarea>
                        </div>
                        <div class="form-group mb-2">
                            <label for="date">Next Appointment Date</label>
                            <input type="date" class="form-control" name="next_appointment_date" value={{date(now())}}>
                        </div>

                        <button type="submit" class="btn btn-primary">Dischage Patient</button>
                    </form>
                </div>
            </div>
        </div>
        
    </div>
@endsection
