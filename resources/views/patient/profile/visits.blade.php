
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Previous Visits History</h5>
    </div>
    <div class="card-body">
        <div class="workflow-steps">
            
            @if($patient->visits()->exists())
                @foreach($patient->visits()->limit(5)->latest()->get() as $visit)
                    <div class="step completed">
                        <div class="step-marker">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="step-label">Visit on {{ $visit->visit_date->format('M d, Y') }}
                            <br>
                            <small class="text-muted">Type: {{ $visit->visit_type }}</small>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="step text->warning">
                    <div class="step-marker">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div class="step-label ">No previous visits recorded</div>
                </div>
            @endif    
        </div>
    </div>
