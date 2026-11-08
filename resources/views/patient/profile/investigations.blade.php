
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>Investigation Requests</h5>
        @if(auth()->user()->hasRole('doctor') || auth()->user()->hasRole('nurse'))
            <a href="{{ route('patient.investigation.create', $patient) }}" class="btn btn-sm btn-light">
                <i class="bi bi-pencil-square me-1"></i> Manage
            </a>
        @endif
    </div>
    <div class="card-body">
        @php
            $investigationRequests = $patient->currentVisit()->investigationRequests()
                ->with(['investigation.investigationType', 'investigationResults.parameter', 'requestedBy', 'performedBy', 'bill'])
                ->latest()
                ->get();
        @endphp

        @if($investigationRequests->count() > 0)
            @foreach($investigationRequests as $investigationRequest)
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

                @if(isset($investigationRequest->result_image) && $investigationRequest->result_image)
                    <div class="mb-3">
                        <h6>Attached Image</h6>
                        <a href="{{ asset('storage/' . $investigationRequest->result_image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $investigationRequest->result_image) }}" alt="Result Image" class="img-fluid border" style="max-width:400px;">
                        </a>
                    </div>
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
                        <td><b>Amount:</b></td><td>&#8358;{{ number_format((float) ($investigationRequest->bill?->due_amount ?? $investigationRequest->investigation?->price ?? 0), 2) }}</td>
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
                @if(auth()->user()->hasRole('doctor') || auth()->user()->hasRole('nurse'))
                    <a href="{{ route('patient.investigation.create', ['patient' => $patient, 'request' => $investigationRequest->id]) }}" class="btn btn-sm btn-outline-primary mt-2">Edit in Investigation Request</a>
                @endif
            @endforeach
        @else
            <p class="text-muted">No pending investigation requests.</p>
        @endif
    </div>
