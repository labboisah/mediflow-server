@extends('layouts.modern')

@section('title', 'Dashboard.Blade')
@section('page-title', 'Dashboard.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Record Officer Dashboard')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <!-- vital signs -->
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle p-3 me-3" style="background-color: #e7f1ff;">
                            <i class="bi bi-heart-pulse text-danger" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Referred Patients</h6>
                            <h3 class="h4 mb-0">{{ App\Models\VitalSign::whereDate('recorded_date', today())->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle p-3 me-3" style="background-color: #e8f8f5;">
                            <i class="bi bi-file-earmark-text text-success" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Admissions Today</h6>
                            <h3 class="h4 mb-0">{{ App\Models\PatientVisit::whereDate('visit_date', today())->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle p-3 me-3" style="background-color: #fff8e1;">
                            <i class="bi bi-file-medical text-warning" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Discharge Today</h6>
                            <h3 class="h4 mb-0">{{ App\Models\Discharge::whereDate('created_at', today())->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle p-3 me-3" style="background-color: #e0f7fa;">
                            <i class="bi bi-person-check text-info" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Investigation Results</h6>
                            <h3 class="h4 mb-0">{{ App\Models\Patient::where('is_walkIn', true)->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning-fill text-warning me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        
                        
                        <div class="col-12 col-sm-6 col-lg-3">
                            <a href="{{ route('patient.search') }}" class="btn btn-outline-success w-100 py-3 text-start">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-search me-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <div class="fw-bold">Search Patient</div>
                                        <small class="text-muted">Find by phone/number</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recently Registered Patients -->
    @if(App\Models\VitalSign::whereDate('created_at', today())->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history text-info me-2"></i>Recent Request for Vital Signs Checks
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 datatable">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-hash me-2"></i>Hospital Number</th>
                                        <th><i class="bi bi-person me-2"></i>Name</th>
                                        <th><i class="bi bi-telephone me-2"></i>Phone</th>
                                        <th><i class="bi bi-calendar-check me-2"></i>Requested Date</th>
                                        <th class="text-center no-export">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(App\Models\VitalSign::whereDate('created_at', today())->latest('created_at')->limit(5)->get() as $vitalSign)
                                        <tr class="align-middle">
                                            <td>
                                                <span class="badge bg-primary">{{ $vitalSign->patientVisit->patient->hospital_number }}</span>
                                            </td>
                                            <td>{{ $vitalSign->patientVisit->patient->demographic->full_name ?? 'N/A' }}</td>
                                            <td>{{ $vitalSign->patientVisit->patient->demographic->phone_number ?? 'N/A' }}</td>
                                            <td>{{ $vitalSign->created_at->format('M d, Y') }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('patient.show', $vitalSign->patientVisit->patient) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye me-1"></i>View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card mb-4 border-start border-4 border-info shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-info-circle text-info" style="font-size: 2rem;"></i>
                <p class="mb-0 mt-2 text-muted">No pending vital signs requests for today.</p>
            </div>
        </div>
    @endif

    @if(App\Models\VitalSign::whereDate('created_at', today())->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history text-info me-2"></i>Fital Signs Recorded Today
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 datatable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Hospital Number</th>
                                        <th>Date</th>
                                        <th>Temperature</th>
                                        <th>Blood Pressure</th>
                                        <th>Heart Rate</th>
                                        <th>Respiratory Rate</th>
                                        <th>O₂ Saturation</th>
                                        <th>Blood Glucose</th>
                                        <th>Recorded By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(App\Models\VitalSign::whereDate('created_at', today())->latest('created_at')->limit(5)->get() as $vitalSign)
                                        <tr class="align-middle">
                                            <td>{{ $vitalSign->patientVisit->patient->demographic->full_name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-primary">{{ $vitalSign->patientVisit->patient->hospital_number ?? 'N/A' }}</span>
                                            </td>
                                            <td>{{ $vitalSign->recorded_date->format('M d, Y') }}</td>
                                            <td>{{ $vitalSign->body_temperature ?? 'N/A' }}</td>
                                            <td>{{ $vitalSign->blood_pressure_systolic ?? 'N/A' }} / {{ $vitalSign->blood_pressure_diastolic ?? 'N/A' }}</td>
                                            <td>{{ $vitalSign->heart_rate ?? 'N/A' }}</td>
                                            <td>{{ $vitalSign->respiratory_rate ?? 'N/A' }}</td>
                                            <td>{{ $vitalSign->oxygen_saturation ?? 'N/A' }}%</td>
                                            <td>{{ $vitalSign->blood_glucose ?? 'N/A' }}</td>
                                            <td>{{ $vitalSign->recordedBy->name ?? 'N/A' }}</td>
                                            
                                            <td class="text-center">
                                                <a href="{{ route('patient.show', $vitalSign->patientVisit->patient) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye me-1"></i>View
                                                </a>
                                                <a href="{{ route('patient.vitalsign.edit', $vitalSign->id) }}" class="btn btn-sm btn-outline-warning ms-1">
                                                    <i class="bi bi-pencil me-1"></i>Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>


@endsection

