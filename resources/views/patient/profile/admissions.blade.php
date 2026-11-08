<div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-hospital me-2"></i>Admissions / Ward-Bed Charges</h5>
    @if(auth()->user()->hasRole('doctor'))
        <a href="{{ route('patient.admission.create', $patient) }}" class="btn btn-sm btn-light">
            <i class="bi bi-pencil-square me-1"></i> Manage
        </a>
    @endif
</div>
<div class="card-body">
    @forelse($patient->currentVisit()->admissions()->with(['bed.ward', 'bills.billServices', 'admittedBy', 'discharge'])->latest()->get() as $admission)
        @php
            $bedBill = $admission->bills->firstWhere('service_description', 'Bed Space Charges') ?? $admission->bills->first();
            $days = (int) ($bedBill?->billServices?->sum('quantity') ?: 1);
            $rate = (float) ($admission->bed?->ward?->price ?? $bedBill?->billServices?->first()?->unit_price ?? 0);
            $charge = (float) ($bedBill?->due_amount ?? ($rate * $days));
        @endphp
        <div class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between gap-2 mb-2">
                <div>
                    <h6 class="mb-1">{{ $admission->bed?->ward?->name ?? 'Ward N/A' }} / Bed {{ $admission->bed?->bed_no ?? 'N/A' }}</h6>
                    <div class="small text-muted">
                        {{ $admission->date }} {{ $admission->time }} | {{ ucfirst($admission->status) }}
                    </div>
                </div>
                <strong>&#8358;{{ number_format($charge, 2) }}</strong>
            </div>

            <div class="row g-2">
                <div class="col-md-3"><span class="text-muted small">Days</span><div class="fw-semibold">{{ $days }}</div></div>
                <div class="col-md-3"><span class="text-muted small">Rate/Day</span><div class="fw-semibold">&#8358;{{ number_format($rate, 2) }}</div></div>
                <div class="col-md-3"><span class="text-muted small">Admitted By</span><div class="fw-semibold">{{ $admission->admittedBy?->name ?? 'N/A' }}</div></div>
                <div class="col-md-3"><span class="text-muted small">Bill</span><div class="fw-semibold">{{ $bedBill?->bill_number ?? 'N/A' }}</div></div>
            </div>

            @if($admission->note)
                <p class="mb-0 mt-2">{{ $admission->note }}</p>
            @endif

            <div class="d-flex flex-wrap gap-2 mt-3">
                @if(auth()->user()->hasRole('doctor'))
                    <a href="{{ route('patient.admission.create', $patient) }}" class="btn btn-sm btn-outline-primary">Edit Admission</a>
                    <a href="{{ route('patient.discharge.create', $admission) }}" class="btn btn-sm btn-outline-success">
                        {{ $admission->discharge ? 'Edit Discharge' : 'Discharge' }}
                    </a>
                @endif
            </div>
        </div>
    @empty
        <p class="text-muted mb-0">No admission record for this visit.</p>
    @endforelse
</div>
