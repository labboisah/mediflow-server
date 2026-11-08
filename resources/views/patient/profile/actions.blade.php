
    
    <div class="card-body">
        <!-- midwife specific role -->
        @if(auth()->user()->hasRole('midwife'))
            <div class="row">
                @foreach([
                    ['route' => 'midwife.anc-management', 'label' => 'Antenatal Care', 'icon' => 'bi-heart-pulse-fill', 'class' => 'btn-outline-primary'],
                    ['route' => 'midwife.labour-management', 'label' => 'Labour', 'icon' => 'bi-activity', 'class' => 'btn-outline-warning'],
                    ['route' => 'midwife.delivery-management', 'label' => 'Delivery', 'icon' => 'bi-hospital-fill', 'class' => 'btn-outline-danger'],
                    ['route' => 'midwife.newborn-management', 'label' => 'Newborn', 'icon' => 'bi-bandaid-fill', 'class' => 'btn-outline-info'],
                    ['route' => 'midwife.postnatal-management', 'label' => 'Postnatal', 'icon' => 'bi-journal-medical', 'class' => 'btn-outline-secondary'],
                    ['route' => 'midwife.child-follow-up-management', 'label' => 'Child Follow-up', 'icon' => 'bi-arrow-repeat', 'class' => 'btn-outline-dark'],
                ] as $maternityAction)
                    <div class="col-md-2">
                        <div class="d-grid gap-2 mb-3">
                            <a href="{{ route($maternityAction['route'], $patient) }}" class="btn {{ $maternityAction['class'] }}">
                                <i class="bi {{ $maternityAction['icon'] }} me-2"></i>{{ $maternityAction['label'] }}
                            </a>
                        </div>
                    </div>
                @endforeach

                <div class="col-md-2">
                    <div class="d-grid gap-2 mb-3">
                        <a href="{{ route('midwife.patient.progress', $patient) }}" class="btn btn-outline-primary">
                            <i class="bi bi-calendar-check me-2"></i>
                            View Progress
                        </a>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="d-grid gap-2 mb-3">
                        <a href="{{ route('patient.admission.create', $patient) }}" class="btn btn-outline-success">
                            <i class="bi bi-file-earmark-medical me-2"></i>Admit the Patient
                        </a>
                    </div>
                </div>
            </div>
        @endif
            <!-- nurse specific role -->
        @if(auth()->user()->hasRole('nurse') || auth()->user()->hasRole('midwife'))
            <div class="row">
                <div class="col-md-2">
                    <div class="d-grid gap-2 mb-3">
                        <a href="{{ route('patient.vitalsign.create', $patient) }}" class="btn btn-outline-danger">
                            <i class="bi bi-hospital me-2"></i>Record Vital Signs
                        </a>
                    </div>
                </div>
                
                <div class="col-md-2">   
                    <div class="d-grid gap-2 mb-3">
                        <a href="{{ route('patient.observation.record', $patient) }}" class="btn btn-outline-danger">
                            <i class="bi bi-heart-pulse me-2"></i>Record Observations
                        </a>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="d-grid gap-2 mb-3">
                        <a href="{{ route('patient.drugchart.record', $patient) }}" class="btn btn-outline-success">
                            <i class="bi bi-heart-pulse me-2"></i>Record Drug Chart
                        </a>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="d-grid gap-2 mb-3">
                        <a href="{{ route('patient.fluidbalance.record', $patient) }}" class="btn btn-outline-success">
                            <i class="bi bi-heart-pulse me-2"></i>Record Fluid Balance
                        </a>
                    </div>
                </div>

                @if(auth()->user()->hasRole('nurse'))
                
                <div class="col-md-2">
                    <div class="d-grid gap-2 mb-3">
                        <a href="{{ route('patient.visit.referred-to-doctor', $patient) }}" class="btn btn-outline-success">
                            <i class="bi bi-file-earmark-medical me-2"></i>Referrer to Doctor
                        </a>
                    </div>
                </div>
                @endif
               
            </div>
            
        </div>
@endif
<div class="row">
    <div class="col-md-8">
        <div class="row">
        @if(auth()->user()->hasRole('record'))
            <div class="col-md-2">
                <div class="d-grid gap-2">
                    <a href="{{ route('record.patients.edit.form', $patient) }}" class="btn btn-outline-info">
                        <i class="bi bi-pencil-square me-2"></i>Edit Patient Information
                    </a>
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="d-grid gap-2">
                    <a href="{{ route('record.patients.index', $patient) }}" class="btn btn-outline-success">
                        <i class="bi bi-eye me-2"></i>View All Patients
                    </a>
                </div> 
            </div>
            <div class="col-md-2">
                <div class="d-grid gap-2">
                    <a href="{{ route('record.visits.create.form', $patient) }}" class="btn btn-outline-success">
                        <i class="bi bi-hospital me-2"></i>Record Visit
                    </a>
                </div> 
            </div>
        @endif

        @if(auth()->user()->hasRole('accountant'))
            <div class="col-md-2">    
                <div class="d-grid gap-2">
                    <a href="{{ route('accountant.bills.create', $patient) }}" class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-medical me-2"></i>Generate Bill
                    </a>
                </div>
            </div>
            @if($patient->payment()['pending'] > 0)
         
            <div class="col-md-2">
                <div class="d-grid gap-2">
                    <a href="{{ route('accountant.payments.create', $patient) }}" class="btn btn-outline-success">
                        <i class="bi bi-cash-coin me-2"></i>Record Payment of N{{number_format($patient->payment()['pending'],2 )}}
                    </a>
                </div>
            </div>
            @endif
        @endif  
        </div>
    </div>
    @if(auth()->user()->hasRole('record') || auth()->user()->hasRole('accountant'))
    <div class="col-md-4">
        <div class="workflow-steps">
            <div class="step completed">
                <div class="step-marker">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="step-label">Patient Registration</div>
            </div>
            
            <div class="step @if($patient->currentVisit() && $patient->currentVisit()->exists()) completed @endif">
                <div class="step-marker">
                    @if($patient->currentVisit() && $patient->currentVisit()->exists())
                        <i class="bi bi-check-circle"></i>
                    @else
                        <i class="bi bi-circle"></i>
                    @endif
                </div>
                <div class="step-label">
                    Record Visit
                </div>
            </div>
            
            <div class="step @if($patient->currentVisit() && $patient->currentVisit()->bills()->exists()) completed @endif">
                <div class="step-marker">
                    @if($patient->currentVisit() && $patient->payment()['pending'] <= 0)
                        <i class="bi bi-check-circle"></i>
                    @else
                        <i class="bi bi-circle"></i>
                    @endif
                </div>
                <div class="step-label">
                    Generate Bill
                </div>
            </div>
            <!--  -->
            <div class="step @if($patient->currentVisit() && $patient->currentVisit()->bills()->exists() && $patient->payment()['pending'] <= 0) completed @endif">
                <div class="step-marker">
                    @if($patient->currentVisit() && $patient->currentVisit()->bills()->exists() && $patient->payment()['pending'] <= 0)
                        <i class="bi bi-check-circle"></i>
                    @else
                        <i class="bi bi-circle"></i>
                    @endif
                </div>
                <div class="step-label">
                    Record Payment
                </div>
            </div>
        </div>
    </div>
    @endif
</div>



@if(auth()->user()->hasRole('doctor'))
<div class="row">
    @if($patient->currentVisit()->admissions()->count() == 0)
    <div class="col-md-2">
        <div class="d-grid gap-2 mb-3">
            <a href="{{ route('patient.admission.create', $patient) }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-medical me-2"></i>Admit Patient
            </a>
        </div>
    </div>
    @endif
    <div class="col-md-2">
        <div class="d-grid gap-2 mb-3">
            <a href="{{ route('patient.continuation.create', $patient) }}" class="btn btn-outline-success">
                <i class="bi bi-pencil me-2"></i>Record Continuation Sheet
            </a>
        </div>
    </div>
    <div class="col-md-2">
        <div class="d-grid gap-2 mb-3">
            <a href="{{ route('patient.visit.referred-to-nurse', $patient) }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-medical me-2"></i>Referred Back to Nurse
            </a>
        </div>
    </div>
   
    @if($admission = $patient->currentVisit()->confirmAdmission())
    <div class="col-md-2">
        <div class="d-grid gap-2 mb-3">
            <a href="{{ route('patient.discharge.create', $admission) }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-medical me-2"></i>Discharge Patient
            </a>
        </div>
    </div>
    @endif
    <div class="col-md-2">
        <div class="d-grid gap-2 mb-3">
            <a href="{{ route('patient.prescription.create', $patient) }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-medical me-2"></i>Prescribe Medication
            </a>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->hasRole('doctor') || auth()->user()->hasRole('nurse') || auth()->user()->hasRole('midwife'))
    <div class="col-md-2">
        <div class="d-grid gap-2 mb-3">
            <a href="{{ route('patient.investigation.create', $patient) }}" class="btn btn-outline-danger">
                <i class="bi bi-file-medical me-2"></i>Send Investigation Request
            </a>
        </div>
    </div>
@endif
</div>
