@extends('layouts.modern')

@section('title', 'Record.Blade')
@section('page-title', 'Record.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Record Vital Signs - ' . ($patient->demographic->full_name ?? 'Patient'))

@section('header')
<div class="d-flex align-items-center gap-3">
    <i class="bi bi-heart-pulse text-danger" style="font-size: 2rem;"></i>
    <div>
        <h1 class="h3 mb-1">Observation</h1>
        <p class="mb-0 text-muted">Patient: <strong>{{ $patient->demographic->full_name ?? 'Unknown' }}</strong></p>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Observation Recording</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('patient.observation.register', $patient) }}" method="POST">
                    @csrf

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="temperature" class="form-label">Temperature (°C)</label>
                                <input type="number" id="temperature" name="temperature" step="0.1" min="35" max="42"
                                    class="form-control @error('body_temperature') is-invalid @enderror"
                                    placeholder="36.5 - 37.5" value="{{ old('temperature') }}">
                                <small class="text-muted">Normal: 36.5-37.5°C</small>
                                @error('temperature')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mate_pulse" class="form-label">Mate Pulse (bpm)</label>
                                <input type="number" id="mate_pulse" name="mate_pulse" min="30" max="200"
                                    class="form-control @error('mate_pulse') is-invalid @enderror"
                                    placeholder="60 - 100" value="{{ old('mate_pulse') }}">
                                <small class="text-muted">Normal: 60-100 bpm</small>
                                @error('mate_pulse')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Blood Pressure (mmHg)</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" id="blood_pressure_systolic" name="blood_pressure_systolic" min="50" max="250"
                                            class="form-control @error('blood_pressure_systolic') is-invalid @enderror"
                                            placeholder="120" value="{{ old('blood_pressure_systolic') }}">
                                        <small class="text-muted">Systolic (e.g., 120)</small>
                                    </div>
                                    <div class="col-6">
                                        <input type="number" id="blood_pressure_diastolic" name="blood_pressure_diastolic" min="30" max="150"
                                            class="form-control @error('blood_pressure_diastolic') is-invalid @enderror"
                                            placeholder="80" value="{{ old('blood_pressure_diastolic') }}">
                                        <small class="text-muted">Diastolic (e.g., 80)</small>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">Normal: 120/80 mmHg</small>
                                @error('blood_pressure_systolic')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('blood_pressure_diastolic')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="respiratory_rate" class="form-label">Respiratory Rate (per min)</label>
                                <input type="number" id="respiratory_rate" name="respiratory_rate" min="10" max="50"
                                    class="form-control @error('respiratory_rate') is-invalid @enderror"
                                    placeholder="12 - 20" value="{{ old('respiratory_rate') }}">
                                <small class="text-muted">Normal: 12-20 per minute</small>
                                @error('respiratory_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="drop_rate" class="form-label">Drops Rate (per min)</label>
                                <input type="number" id="drop_rate" name="drop_rate" min="10" max="50"
                                    class="form-control @error('drop_rate') is-invalid @enderror"
                                    placeholder="12 - 20" value="{{ old('drop_rate') }}">
                                <small class="text-muted">Normal: 12-20 per minute</small>
                                @error('drop_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="constraction" class="form-label">Constraction (%)</label>
                                <input type="text" id="constraction" name="constraction" step="0.1" min="50" max="100"
                                    class="form-control @error('constraction') is-invalid @enderror"
                                    placeholder="95 - 100" value="{{ old('constraction') }}">
                                <small class="text-muted">Normal: 95-100%</small>
                                @error('constraction')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fits" class="form-label">Fits</label>
                                <input type="number" id="fits" name="fits" step="0.01" min="0"
                                    class="form-control @error('fits') is-invalid @enderror"
                                    placeholder="100" value="{{ old('fits') }}">
                                <small class="text-muted">Normal fasting: 70-100 mg/dL</small>
                                @error('fits')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    

                    <div class="mb-3">
                        <label for="date" class="form-label">Recording Date</label>
                        <input type="date" id="date" name="date"
                            class="form-control @error('date') is-invalid @enderror"
                            value="{{ old('date', date('Y-m-d')) }}">
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="time" class="form-label">Time</label>
                        <input type="time" id="time" name="time"
                            class="form-control @error('time') is-invalid @enderror"
                            value="{{ old('time', date('Y-m-d')) }}">
                        @error('time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="remark" class="form-label">Remark</label>
                        <textarea id="remark" name="remark" rows="3"
                            class="form-control @error('remark') is-invalid @enderror"
                            placeholder="Any additional observations or remark...">{{ old('remark') }}</textarea>
                        @error('remark')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-check-circle me-2"></i> Record Observation
                        </button>
                        <a href="{{ route('nurse.patient.show', $patient) }}" class="btn btn-secondary">
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
                    <p class="mb-2"><strong>Blood Pressure:</strong> 120/80 mmHg</p>
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
                    <span class="badge bg-primary">{{ $patient->hospital_number }}</span>
                </p>
                <p class="mb-0">
                    <strong>Name:</strong><br>
                    {{ $patient->demographic->full_name ?? 'N/A' }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

