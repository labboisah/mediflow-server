@php
    // Ensure $patient is available in the parent view
    $visits = $patient->patientVisits()->latest('created_at')->with([
        'investigationRequests.investigation',
        'observations',
        'prescriptions.prescriptionItems.drugCharts.medicine',
        'bills',
        'createdBy',
        'vitalSigns',
        'admissions.discharge',
        'continuations',
        'fluidBalances'
    ])->latest()->get();
@endphp




    
        @if($visits->isEmpty())
            <div class="alert alert-info small mb-0">No visit history available for this patient.</div>
        @else
            @foreach($visits as $visit)
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="me-2">{{ $visit->visit_type }}</strong>
                            <span class="text-muted small">{{ optional($visit->visit_date)->format('d M, Y') ?? $visit->visit_date }}</span>
                        </div>
                        <div class="text-end small text-muted d-flex align-items-center">
                            <div>{{ $visit->createdBy?->name ?? 'System' }}</div>
                            <button
                                class="btn btn-sm btn-outline-secondary ms-3 visit-detail-toggle"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#visitDetails-{{ $visit->id }}"
                                aria-expanded="false"
                                aria-controls="visitDetails-{{ $visit->id }}">
                                Show details
                            </button>
                        </div>
                    </div>

                    <div class="collapse" id="visitDetails-{{ $visit->id }}">
                        <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <h6 class="mb-1">Investigations</h6>
                                @if($visit->investigationRequests->isEmpty())
                                    <p class="text-muted small mb-0">None</p>
                                @else
                                    <ul class="list-unstyled small mb-0">
                                        @foreach($visit->investigationRequests as $ir)
                                            <li>
                                                {{ $ir->investigation?->name ?? '—' }}
                                                @if(isset($ir->payment_status))
                                                    <small class="text-muted">({{ $ir->payment_status }})</small>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <div class="col-md-4 mb-2">
                                <h6 class="mb-1">Vital Signs</h6>
                                @if($visit->vitalSigns->isEmpty())
                                    <p class="text-muted small mb-0">None</p>
                                @else
                                    <ul class="list-unstyled small mb-0">
                                        @foreach($visit->vitalSigns->take(6) as $vs)
                                            <li>
                                                Temp: {{ $vs->body_temperature ?? '—' }}°C,
                                                BP: {{ $vs->blood_pressure_systolic ?? '—' }}/{{ $vs->blood_pressure_diastolic ?? '—' }},
                                                HR: {{ $vs->heart_rate ?? '—' }} <small class="text-muted">{{ optional($vs->recorded_date)->format('d M, Y H:i') }}</small>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <div class="col-md-4 mb-2">
                                <h6 class="mb-1">Admissions / Discharge</h6>
                                @if($visit->admissions->isEmpty())
                                    <p class="text-muted small mb-0">None</p>
                                @else
                                    <ul class="list-unstyled small mb-0">
                                        @foreach($visit->admissions as $adm)
                                            <li>
                                                <strong>{{ $adm->status ?? 'Admission' }}</strong>
                                                <div class="small text-muted">Date: {{ optional($adm->date)->format('d M, Y') ?? $adm->date }} @if($adm->bed) | Bed: {{ $adm->bed?->name ?? '' }}@endif</div>
                                                @if($adm->discharge)
                                                    <div class="small text-muted">Discharged: {{ optional($adm->discharge->created_at)->format('d M, Y') }} by {{ $adm->discharge->dischargedBy?->name ?? '—' }}</div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4 mb-2">
                                <h6 class="mb-1">Observations</h6>
                                @if($visit->observations->isEmpty())
                                    <p class="text-muted small mb-0">None</p>
                                @else
                                    <ul class="list-unstyled small mb-0">
                                        @foreach($visit->observations->take(6) as $obs)
                                            <li>{{ $obs->observation }} <small class="text-muted">{{ optional($obs->created_at)->format('d M, Y H:i') }}</small></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <div class="col-md-4 mb-2">
                                <h6 class="mb-1">Prescriptions & Drug Chart</h6>
                                @if($visit->prescriptions->isEmpty())
                                    <p class="text-muted small mb-0">None</p>
                                @else
                                    @foreach($visit->prescriptions as $presc)
                                        <div class="mb-2">
                                            <div class="small"><strong>Prescription</strong> <span class="text-muted">{{ optional($presc->created_at)->format('d M, Y') }}</span></div>
                                            <ul class="list-unstyled small mb-1">
                                                @foreach($presc->prescriptionItems as $item)
                                                    <li>{{ $item->medicine?->name ?? ($item->drug_name ?? 'Medication') }} <small class="text-muted">{{ $item->dose ?? '' }} {{ $item->duration ?? '' }}</small></li>
                                                    @if($item->drugCharts && $item->drugCharts->isNotEmpty())
                                                        <li class="text-muted small">Drug chart entries:
                                                            <ul class="list-unstyled small mb-0">
                                                                @foreach($item->drugCharts as $dc)
                                                                    <li>{{ $dc->medicine?->name ?? '—' }} @if($dc->dispensedBy) <small class="text-muted">(dispensed by {{ $dc->dispensedBy->name }})</small>@endif</li>
                                                                @endforeach
                                                            </ul>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <div class="col-md-4 mb-2">
                                <h6 class="mb-1">Continuations</h6>
                                @if($visit->continuations->isEmpty())
                                    <p class="text-muted small mb-0">None</p>
                                @else
                                    <ul class="list-unstyled small mb-0">
                                        @foreach($visit->continuations->take(6) as $c)
                                            <li>{{ $c->activity }} <small class="text-muted">{{ optional($c->created_at)->format('d M, Y H:i') }}</small></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6 mb-2">
                                <h6 class="mb-1">Fluid Balance</h6>
                                @if($visit->fluidBalances->isEmpty())
                                    <p class="text-muted small mb-0">None</p>
                                @else
                                    <ul class="list-unstyled small mb-0">
                                        @foreach($visit->fluidBalances->take(6) as $fb)
                                            <li>{{ $fb->type ?? 'Entry' }}: {{ $fb->amount ?? '' }} <small class="text-muted">{{ optional($fb->created_at)->format('d M, Y H:i') }}</small></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <div class="col-md-6 mb-2">
                                <h6 class="mb-1">Bills & Payments</h6>
                                @if($visit->bills->isEmpty())
                                    <p class="text-muted small mb-0">None</p>
                                @else
                                    <ul class="list-unstyled small mb-0">
                                        @foreach($visit->bills as $bill)
                                            <li>
                                                <strong>{{ $bill->bill_number }}</strong>
                                                <div class="small text-muted">Amount: {{ number_format($bill->amount, 2) }} | Due: {{ number_format($bill->due_amount, 2) }} | Status: {{ $bill->status }}</div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>

                        @if($visit->clinical_notes)
                            <hr />
                            <h6 class="mb-1">Clinical Notes</h6>
                            <p class="small text-muted mb-0">{{ $visit->clinical_notes }}</p>
                        @endif

                    </div>
                </div>
            @endforeach
        @endif
    


<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('visitHistoryToggle');
        var collapseEl = document.getElementById('visitHistoryCollapse');

        if (toggle && collapseEl) {
            collapseEl.addEventListener('show.bs.collapse', function () {
                toggle.textContent = 'Hide Visit History';
            });

            collapseEl.addEventListener('hide.bs.collapse', function () {
                toggle.textContent = 'Show Visit History';
            });

            // Ensure default is hidden (Bootstrap collapse default)
            toggle.textContent = 'Show Visit History';
        }

        // Per-visit toggles: update button text when individual visit details show/hide
        document.querySelectorAll('.visit-detail-toggle').forEach(function (btn) {
            var targetSelector = btn.getAttribute('data-bs-target');
            if (!targetSelector) return;
            var targetEl = document.querySelector(targetSelector);
            if (!targetEl) return;

            targetEl.addEventListener('show.bs.collapse', function () {
                btn.textContent = 'Hide details';
            });

            targetEl.addEventListener('hide.bs.collapse', function () {
                btn.textContent = 'Show details';
            });
        });
    });
</script>
