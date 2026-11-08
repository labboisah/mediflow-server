@extends('layouts.modern')

@section('title', 'Index.Blade')
@section('page-title', 'Index.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Nursing Patient Management')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-0">Patient Management</h1>
            <p class="text-muted">Manage patients under nursing care</p>
        </div>
    </div>  
    @if(count($requests)== 0)
        <div class="alert alert-info">No Nursing Patients records found.</div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 datatable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Hospital Number</th>
                                <th>Patient Name</th>
                                <th>Service</th>
                                <th>Phone Number</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Marital Status</th>
                                <th>Next of Kin Name</th>
                                <th>Next of Kin Contact</th>
                                <th>Next of Kin Address</th>
                                <th>Next of Kin Relationship</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $request)
                                <!-- request information -->
                                
                                @if($request->patientVisit && $request->patientVisit->status == 'Active')
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $request->patientVisit->patient->hospital_number ?? 'N/A' }}</td>
                                    <td>{{ $request->patientVisit->patient->name() }}</td>
                                    <td>{{ $request->service->name ?? 'N/A' }}</td>
                                    <td>{{ $request->patientVisit->patient->demographic->phone_number ?? 'N/A' }}</td>
                                    <td>{{ $request->patientVisit->patient->age() ?? 'N/A' }}</td>
                                    <td>{{ $request->patientVisit->patient->demographic->gender ?? 'N/A' }}</td>
                                    <td>{{ $request->patientVisit->patient->demographic->marital_status ?? 'N/A' }}</td>
                                    <td>{{ $request->patientVisit->patient->nextOfKin->name ?? 'N/A' }}</td>
                                    <td>{{ $request->patientVisit->patient->nextOfKin->telephone ?? 'N/A' }}</td>
                                    <td>{{ $request->patientVisit->patient->nextOfKin->contact_address ?? 'N/A' }}</td>
                                    <td>{{ $request->patientVisit->patient->nextOfKin->relationship ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('nurse.patient.show', $request->patientVisit->patient) }}" class="btn btn-sm btn-info"><i class="bi bi-eye"></i> View Patient Profile</a>
                                        @if($request->status != 'completed')
                                        <a href="{{ route('nurse.patient.complete', $request) }}" onclick="return confirm('Are you sure you want to mark this request as completed?');" class="btn btn-sm btn-success"><i class="bi bi-check-circle"></i> Mark as Completed</a>
                                        @endif
                                        <!-- close visit -->
                                        <a href="{{ route('nurse.patient.close-visit', $request->patientVisit) }}" onclick="return confirm('Are you sure you want to close this visit?');" class="btn btn-sm btn-warning"><i class="bi bi-x-circle"></i> Close Visit</a>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif 
    

@endsection
