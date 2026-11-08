@extends('layouts.modern')

@section('title', 'Edit.Blade')
@section('page-title', 'Edit.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Edit Vital Signs - ' . ($patientVisitVitalSign->vitalSignsRequest->patientVisit->patient->demographic->full_name ?? 'Patient'))

@section('header')
<div class="d-flex align-items-center gap-3">
    <i class="bi bi-heart-pulse text-danger" style="font-size: 2rem;"></i>
    <div>
        <h1 class="h3 mb-1">Edit Vital Signs</h1>
        <p class="mb-0 text-muted">Patient: <strong>{{ $patientVisitVitalSign->vitalSignsRequest->patientVisit->patient->demographic->full_name ?? 'Unknown' }}</strong></p>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Edit Vital Signs Record</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('nurse.patients.vitalsigns.update', $patientVisitVitalSign) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="body_temperature" class="form-label">Body Temperature (°C)</label>
                                <input type="number" id="body_temperature" name="body_temperature" step="0.1" min="35" max="42"
                                    class="form-control @error('body_temperature') is-invalid @enderror"
                                    placeholder="36.5 - 37.5" value="{{ old('body_temperature', $patientVisitVitalSign->body_temperature) }}">
                                <small class="text-muted">Normal: 36.5-37.5°C</small>
                                @error('body_temperature')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="heart_rate" class="form-label">Heart Rate (bpm)</label>
                                <input type="number" id="heart_rate" name="heart_rate" min="30" max="200"
                                    class="form-control @error('heart_rate') is-invalid @enderror"
                                    placeholder="60 - 100" value="{{ old('heart_rate', $patientVisitVitalSign->heart_rate) }}">
                                <small class="text-muted">Normal: 60-100 bpm</small>
                                @error('heart_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Blood Pressure (mmHg) <span class="text-muted">(optional for children)</span></label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" id="blood_pressure_systolic" name="blood_pressure_systolic" min="50" max="250"
                                            class="form-control @error('blood_pressure_systolic') is-invalid @enderror"
                                            placeholder="120" value="{{ old('blood_pressure_systolic', $patientVisitVitalSign->blood_pressure_systolic) }}">
                                        <small class="text-muted">Systolic (e.g., 120)</small>
                                    </div>
                                    <div class="col-6">
                                        <input type="number" id="blood_pressure_diastolic" name="blood_pressure_diastolic" min="30" max="150"
                                            class="form-control @error('blood_pressure_diastolic') is-invalid @enderror"
                                            placeholder="80" value="{{ old('blood_pressure_diastolic', $patientVisitVitalSign->blood_pressure_diastolic) }}">
                                        <small class="text-muted">Diastolic (e.g., 80)</small>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">Normal: 120/80 mmHg. Optional for children.</small>
                                @error('blood_pressure_systolic')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('blood_pressure_diastolic')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="respiratory_rate" class="form-label">Respiratory Rate (per min)</label>
                                <input type="number" id="respiratory_rate" name="respiratory_rate" min="10" max="50"
                                    class="form-control @error('respiratory_rate') is-invalid @enderror"
                                    placeholder="12 - 20" value="{{ old('respiratory_rate', $patientVisitVitalSign->respiratory_rate) }}">
                                <small class="text-muted">Normal: 12-20 per minute</small>
                                @error('respiratory_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="oxygen_saturation" class="form-label">Oxygen Saturation (%)</label>
                                <input type="number" id="oxygen_saturation" name="oxygen_saturation" step="0.1" min="50" max="100"
                                    class="form-control @error('oxygen_saturation') is-invalid @enderror"
                                    placeholder="95 - 100" value="{{ old('oxygen_saturation', $patientVisitVitalSign->oxygen_saturation) }}">
                                <small class="text-muted">Normal: 95-100%</small>
                                @error('oxygen_saturation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="blood_glucose" class="form-label">Blood Glucose (mg/dL)</label>
                                <input type="number" id="blood_glucose" name="blood_glucose" step="0.01" min="0"
                                    class="form-control @error('blood_glucose') is-invalid @enderror"
                                    placeholder="70 - 100" value="{{ old('blood_glucose', $patientVisitVitalSign->blood_glucose) }}">
                                <small class="text-muted">Normal fasting: 70-100 mg/dL</small>
                                @error('blood_glucose')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="weight" class="form-label">Weight (kg)</label>
                                <input type="number" id="weight" name="weight" step="0.1" min="0"
                                    class="form-control @error('weight') is-invalid @enderror"
                                    placeholder="e.g., 70.0" value="{{ old('weight', $patientVisitVitalSign->weight) }}">
                                @error('weight')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="height" class="form-label">Height (cm)</label>
                                <input type="number" id="height" name="height" step="0.1" min="0"
                                    class="form-control @error('height') is-invalid @enderror"
                                    placeholder="e.g., 170" value="{{ old('height', $patientVisitVitalSign->height) }}">
                                @error('height')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="recorded_date" class="form-label">Recording Date</label>
                        <input type="date" id="recorded_date" name="recorded_date"
                            class="form-control @error('recorded_date') is-invalid @enderror"
                            value="{{ old('recorded_date', $patientVisitVitalSign->recorded_date) }}">
                        @error('recorded_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Clinical Notes</label>
                        <textarea id="notes" name="notes" rows="3"
                            class="form-control @error('notes') is-invalid @enderror"
                            placeholder="Any additional observations or notes...">{{ old('notes', $patientVisitVitalSign->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-check-circle me-2"></i> Update Vital Signs
                        </button>
                        <a href="{{ route('nurse.patient.show', $patientVisitVitalSign->vitalSignsRequest->patientVisit->patient) }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reference Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Normal Ranges</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info small mb-0">
                    <p class="mb-2"><strong>Temperature:</strong> 36.5-37.5°C</p>
                    <p class="mb-2"><strong>Heart Rate:</strong> 60-100 bpm</p>
                    <p class="mb-2"><strong>Blood Pressure:</strong> 120/80 mmHg. Optional for children.</p>
                    <p class="mb-2"><strong>Respiratory Rate:</strong> 12-20 per minute</p>
                    <p class="mb-2"><strong>Oxygen Saturation:</strong> 95-100%</p>
                    <p class="mb-0"><strong>Blood Glucose:</strong> 70-100 mg/dL (fasting)</p>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-person me-2"></i>Patient Info</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Hospital Number:</strong><br>
                    <span class="badge bg-primary">{{ $patientVisitVitalSign->vitalSignsRequest->patientVisit->patient->hospital_number }}</span>
                </p>
                <p class="mb-0">
                    <strong>Name:</strong><br>
                    {{ $patientVisitVitalSign->vitalSignsRequest->patientVisit->patient->demographic->full_name ?? 'N/A' }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

