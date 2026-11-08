    @php
        $currentVisit = $patient->currentVisit();
    @endphp

    <div class="col-lg-12">

        <div class="card border-0 shadow-sm mb-4 patient-profile-card">
            @if($currentVisit)
                <div class="patient-visit-watermark">
                    {{ strtoupper(substr($currentVisit->visit_type, 0, strpos($currentVisit->visit_type, ' '))) }}
                </div>
            @endif

            <!-- Tabs -->
            <div class="card-header bg-white border-bottom">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">

                    <li class="nav-item">
                        <button class="nav-link"
                                data-bs-toggle="tab"
                                data-bs-target="#bio"
                                type="button">
                            <i class="bi bi-person-fill me-1"></i> Bio Data
                        </button>
                    </li>

                
                    <li class="nav-item">
                        <button class="nav-link"
                                data-bs-toggle="tab"
                                data-bs-target="#history"
                                type="button">
                            <i class="bi bi-clock-history me-1"></i> History
                        </button>
                    </li>

                    @if(auth()->user()->hasRole('record'))
                    <li class="nav-item">
                        <button class="nav-link"
                                data-bs-toggle="tab"
                                data-bs-target="#visits"
                                type="button">
                            <i class="bi bi-clock-history me-1"></i> Visits
                        </button>
                    </li>
                    @endif

                    <li class="nav-item">
                        <button class="nav-link"
                                data-bs-toggle="tab"
                                data-bs-target="#activities"
                                type="button">
                            <i class="bi bi-activity me-1"></i> Activities
                        </button>
                    </li>

                    @if(auth()->user()->hasRole('accountant'))
                    <li class="nav-item dropdown">
                        <button class="nav-link dropdown-toggle"
                                data-bs-toggle="dropdown"
                                type="button">
                            <i class="bi bi-wallet2 me-1"></i> Finance
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#bills" type="button">
                                    <i class="bi bi-receipt me-2"></i> Bills
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#payments" type="button">
                                    <i class="bi bi-credit-card me-2"></i> Payments
                                </button>
                            </li>
                        </ul>
                    </li>
                    @endif
                    
                    @if(auth()->user()->hasRole('nurse') || auth()->user()->hasRole('doctor') || auth()->user()->hasRole('midwife'))
                    <li class="nav-item dropdown">
                        <button class="nav-link dropdown-toggle"
                                data-bs-toggle="dropdown"
                                type="button">
                            <i class="bi bi-clipboard2-pulse me-1"></i> Clinical
                        </button>
                        <ul class="dropdown-menu">
                            @if(auth()->user()->hasRole('nurse') || auth()->user()->hasRole('doctor') || auth()->user()->hasRole('midwife'))
                                <li><h6 class="dropdown-header">Nursing Clinicals</h6></li>
                                <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#vitalsigns" type="button"><i class="bi bi-heart-pulse me-2"></i> Vital Signs</button></li>
                                <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#observations" type="button"><i class="bi bi-eye me-2"></i> Observations</button></li>
                                <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#drugchart" type="button"><i class="bi bi-capsule-pill me-2"></i> Drug Chart</button></li>
                                <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#fluidbalance" type="button"><i class="bi bi-droplet me-2"></i> Fluid Balance</button></li>
                            @endif

                            @if(auth()->user()->hasRole('doctor') || auth()->user()->hasRole('midwife'))
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Medication Clinicals</h6></li>
                                <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#admissions" type="button"><i class="bi bi-hospital me-2"></i> Admissions</button></li>
                                <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#prescriptions" type="button"><i class="bi bi-prescription2 me-2"></i> Prescriptions</button></li>
                                <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#continuations" type="button"><i class="bi bi-pencil me-2"></i> Continuation Sheet</button></li>
                            @endif
                            <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#investigations" type="button"><i class="bi bi-clipboard2-pulse me-2"></i> Investigations</button></li>

                        </ul>
                    </li>

                     @endif

                    @if(auth()->user()->hasRole('midwife'))
                    <li class="nav-item dropdown">
                        <button class="nav-link dropdown-toggle"
                                data-bs-toggle="dropdown"
                                type="button">
                            <i class="bi bi-heart-pulse-fill me-1"></i> Maternity
                        </button>
                        <ul class="dropdown-menu">
                            <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#maternity-anc" type="button"><i class="bi bi-heart-pulse-fill me-2"></i> ANC Records</button></li>
                            <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#maternity-labour" type="button"><i class="bi bi-activity me-2"></i> Labour Records</button></li>
                            <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#maternity-delivery" type="button"><i class="bi bi-hospital-fill me-2"></i> Delivery Records</button></li>
                            <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#maternity-newborn" type="button"><i class="bi bi-bandaid-fill me-2"></i> Newborn Records</button></li>
                            <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#maternity-postnatal" type="button"><i class="bi bi-journal-medical me-2"></i> Postnatal Records</button></li>
                            <li><button class="dropdown-item" data-bs-toggle="tab" data-bs-target="#maternity-child-follow-up" type="button"><i class="bi bi-arrow-repeat me-2"></i> Child Follow-up</button></li>
                        </ul>
                    </li>
                    @endif

                    <li class="nav-item text-danger">
                        <button class="nav-link active"
                                data-bs-toggle="tab"
                                data-bs-target="#actions"
                                type="button">
                            <i class="bi bi-lightning-charge me-1"></i> Quick Actions
                        </button>
                    </li>
                

                </ul>
            </div>

            <!-- Tab Content -->
            <div class="card-body tab-content">

                <!-- BIO DATA -->
                <div class="tab-pane fade" id="bio">
                    @include('patient.profile.infor')
                </div>

                
                <div class="tab-pane fade" id="bills">

                    @include('patient.profile.bill')

                </div>

                <div class="tab-pane fade" id="payments">

                    @include('patient.profile.payment')

                </div>

                <!-- VISITS -->
                <div class="tab-pane fade" id="visits">

                    @include('patient.profile.visits')

                </div>

                <!-- VITAL SIGNS -->
                <div class="tab-pane fade" id="vitalsigns">

                    @include('patient.profile.vitalsign')

                </div>

                <div class="tab-pane fade" id="prescriptions">

                    @include('patient.profile.prescription')

                </div>

                

                <!--  DRUG CHART -->
                <div class="tab-pane fade" id="drugchart">
                    @include('patient.profile.drugchart')
                </div>

                <!--  DRUG CHART -->
                <div class="tab-pane fade" id="fluidbalance">
                    @include('patient.profile.fluidbalance')
                </div>
                    <!-- NURSING NOTE -->
                <div class="tab-pane fade" id="observations">
                    @include('patient.profile.observations')
                </div>
                <!-- INVESTIGATIONS -->
                <div class="tab-pane fade" id="investigations">
                    @include('patient.profile.investigations')
                </div>

                <div class="tab-pane fade" id="admissions">
                    @include('patient.profile.admissions')
                </div>

                <div class="tab-pane fade" id="continuations">
                    @include('patient.profile.continuations')
                </div>

                @if(auth()->user()->hasRole('midwife'))
                    <div class="tab-pane fade" id="maternity-anc">
                        @include('patient.profile.maternity.antenatal')
                    </div>
                    <div class="tab-pane fade" id="maternity-labour">
                        @include('patient.profile.maternity.labour')
                    </div>
                    <div class="tab-pane fade" id="maternity-delivery">
                        @include('patient.profile.maternity.delivery')
                    </div>
                    <div class="tab-pane fade" id="maternity-newborn">
                        @include('patient.profile.maternity.newborn')
                    </div>
                    <div class="tab-pane fade" id="maternity-postnatal">
                        @include('patient.profile.maternity.postnatal')
                    </div>
                    <div class="tab-pane fade" id="maternity-child-follow-up">
                        @include('patient.profile.maternity.child-follow-up')
                    </div>
                @endif

                <!-- ACTIVITIES -->
                <div class="tab-pane fade" id="activities">
                    @include('patient.profile.activities')
                </div>
                
                <!-- QUICK ACTIONS -->
                <div class="tab-pane fade show active" id="actions">
                    @include('patient.profile.actions')
                    
                </div>

                <div class="tab-pane fade" id="history">
                    @include('patient.profile.history')
                </div>
                
            </div>
        </div>
    </div>

    <style>
        .workflow-steps {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #ccc;
        }
        
        .step.completed {
            background: #e8f8f5;
            border-left-color: #27AE60;
        }
        
        .step-marker {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .patient-profile-card {
            position: relative;
            overflow: hidden;
        }

        .patient-visit-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            z-index: 1;
            font-size: 7rem;
            font-weight: 800;
            letter-spacing: 0.45rem;
            color: rgba(0, 0, 0, 0.06);
            text-transform: uppercase;
            pointer-events: none;
            user-select: none;
            white-space: nowrap;
            transform: translate(-50%, -50%) rotate(-12deg);
            text-align: center;
            width: 100%;
            max-width: 100%;
        }
        
        .step.completed .step-marker {
            color: #27AE60;
        }
        
        .step-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #333;
        }
    </style>
