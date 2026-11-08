@extends('layouts.modern')

@section('title', 'Show.Blade')
@section('page-title', 'Show.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Investigation Result')

@section('header')
    <div class="d-flex justify-content-between align-items-center">
    <h1 class="h3 d-flex align-items-center mb-0">
        <i class="bi bi-clipboard2 me-2 text-primary"></i>
        <span class="text-muted">Investigation Result for  {{$investigationRequest->patientVisit->patient->hospital_number ?? 'N/A' }}</span>
    </h1>
    <a href="{{ route('nurse.patient.show', $investigationRequest->patientVisit->patient) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Patient
    </a>
</div>
@endsection

@section('content')

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            
            <div class="card-body shadow p-4">
               
                <h5 class="mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-eyedropper me-2 text-primary"></i>
                    <b><em>{{ $investigationRequest->investigation?->investigationType?->name ?? 'External investigation' }}</em></b> Investigation Details
                </h5>
                <hr style="height: 3px; background-color: green;">
                @if($investigationRequest->investigationResults->count() > 0)
                @foreach($investigationRequest->investigationResults as $result)
                <div>    
                    <p class="mb-1"><b>{{$result->parameter->name}}:</b> {{ $result->value }}</p>
                    <p class="text-muted mb-3">Range: {{$result->parameter->reference_range ?? 'N/A'}}</p>
                </div>
                @endforeach
                @else
                <p class="text-muted">No results available for this investigation request.</p>
                @endif

                <hr style="height: 3px; background-color: orange;">
                <table>
                    <tr>
                        <td><b>Patient Name:</b></td><td> {{ $investigationRequest->patientVisit->patient->demographic->full_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><b>Investigation:</b></td><td> {{ $investigationRequest->requested_name ?? $investigationRequest->investigation?->name ?? 'N/A'}}</td>
                    </tr>
                    <tr>
                        <td><b>Requested By:</b></td><td> {{ $investigationRequest->requestedBy->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td width="200"><b>Requested At:</b></td><td> {{ $investigationRequest->created_at }}</td>
                    </tr>
                     <tr>
                        <td><b>Completed At:</b></td><td> {{ $investigationRequest->completed_at ?? 'Not completed yet' }}</td>
                    </tr>
                    <tr>
                        <td><b>Performed By:</b></td><td> {{ $investigationRequest->performedBy->name ?? 'N/A' }}</td>
                    </tr>
                    
                    <tr>
                        <td><b>Status:</b></td><td> {{ $investigationRequest->status }}</td>
                    </tr>
                    <tr>
                        <td><b>Clinical Notes:</b></td><td> {{ $investigationRequest->clinical_diagnoses }}</td>
                    </tr>
                    <tr>
                        <td><b>Specimen:</b></td><td> {{ $investigationRequest->specimen }}</td>
                    </tr>
                    <tr>
                        <td><b>Clinical Notes:</b></td><td>{{ $investigationRequest->clinical_diagnoses }}</td>  
                    </tr>
                </table>
                 
            </div>
        </div>
    </div>
</div>
@endsection         


