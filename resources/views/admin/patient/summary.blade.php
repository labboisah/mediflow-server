@extends('layouts.modern')

@section('title', 'Summary.Blade')
@section('page-title', 'Summary.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Patient Summary')

@section('content')
@php($activeSection = request('section', 'visits'))
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $patient->demographic?->full_name ?? 'Patient Summary' }}</h1>
        <p class="text-muted mb-0">Hospital No: {{ $patient->hospital_number }} | Registered: {{ $patient->registration_date?->format('M d, Y') ?? 'N/A' }}</p>
    </div>
    <a href="{{ route('admin.patient-register.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>
        Back to Register
    </a>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'Visits', 'value' => $stats['visits'], 'icon' => 'bi-clipboard-pulse'],
        ['label' => 'Admissions', 'value' => $stats['admissions'], 'icon' => 'bi-hospital'],
        ['label' => 'Discharges', 'value' => $stats['discharges'], 'icon' => 'bi-box-arrow-right'],
        ['label' => 'Bills', 'value' => $stats['bills'], 'icon' => 'bi-receipt'],
        ['label' => 'Billed', 'value' => number_format($stats['billed'], 2), 'icon' => 'bi-cash-stack'],
        ['label' => 'Paid', 'value' => number_format($stats['paid'], 2), 'icon' => 'bi-credit-card-2-front'],
    ] as $stat)
        <div class="col-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">{{ $stat['label'] }}</div>
                            <div class="h5 mb-0">{{ $stat['value'] }}</div>
                        </div>
                        <i class="bi {{ $stat['icon'] }} text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Patient Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6"><div class="text-muted small">Gender</div><div class="fw-semibold">{{ $patient->demographic?->gender ?? 'N/A' }}</div></div>
                    <div class="col-6"><div class="text-muted small">Age</div><div class="fw-semibold">{{ $patient->demographic?->age ?? 'N/A' }}</div></div>
                    <div class="col-6"><div class="text-muted small">Phone</div><div class="fw-semibold">{{ $patient->demographic?->phone_number ?? 'N/A' }}</div></div>
                    <div class="col-6"><div class="text-muted small">File Type</div><div class="fw-semibold">{{ $patient->fileType?->name ?? 'General file' }}</div></div>
                    <div class="col-12"><div class="text-muted small">Address</div><div class="fw-semibold">{{ $patient->demographic?->address ?? 'N/A' }}</div></div>
                    <div class="col-12"><div class="text-muted small">Next of Kin</div><div class="fw-semibold">{{ $patient->nextOfKin?->name ?? 'N/A' }} {{ $patient->nextOfKin?->telephone ? '(' . $patient->nextOfKin->telephone . ')' : '' }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Financial Snapshot</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-4"><div class="text-muted small">Total Due</div><div class="fw-semibold">{{ number_format($stats['due'], 2) }}</div></div>
                    <div class="col-4"><div class="text-muted small">Payments</div><div class="fw-semibold">{{ number_format($stats['payments']) }}</div></div>
                    <div class="col-4"><div class="text-muted small">Balance</div><div class="fw-semibold">{{ number_format(max(0, $stats['due'] - $stats['paid']), 2) }}</div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Bill</th><th>Status</th><th class="text-end">Due</th><th class="text-end">Paid</th></tr></thead>
                        <tbody>
                            @forelse($bills->take(5) as $bill)
                                <tr>
                                    <td>{{ $bill->bill_number }}</td>
                                    <td>{{ ucfirst($bill->status ?? 'pending') }}</td>
                                    <td class="text-end">{{ number_format($bill->due_amount, 2) }}</td>
                                    <td class="text-end">{{ number_format($bill->payments->where('status', 'completed')->sum('amount'), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No bills found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation"><button id="visits-tab" class="nav-link {{ $activeSection === 'visits' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#visits" type="button" role="tab" aria-controls="visits" aria-selected="{{ $activeSection === 'visits' ? 'true' : 'false' }}" tabindex="{{ $activeSection === 'visits' ? '0' : '-1' }}">Visits</button></li>
    <li class="nav-item" role="presentation"><button id="admissions-tab" class="nav-link {{ $activeSection === 'admissions' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#admissions" type="button" role="tab" aria-controls="admissions" aria-selected="{{ $activeSection === 'admissions' ? 'true' : 'false' }}" tabindex="{{ $activeSection === 'admissions' ? '0' : '-1' }}">Admissions & Discharges</button></li>
    <li class="nav-item" role="presentation"><button id="payments-tab" class="nav-link {{ $activeSection === 'payments' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="{{ $activeSection === 'payments' ? 'true' : 'false' }}" tabindex="{{ $activeSection === 'payments' ? '0' : '-1' }}">Payments</button></li>
    <li class="nav-item" role="presentation"><button id="activity-tab" class="nav-link {{ $activeSection === 'activity' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-controls="activity" aria-selected="{{ $activeSection === 'activity' ? 'true' : 'false' }}" tabindex="{{ $activeSection === 'activity' ? '0' : '-1' }}">Other Information</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade {{ $activeSection === 'visits' ? 'show active' : '' }}" id="visits" role="tabpanel" aria-labelledby="visits-tab" tabindex="0">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Status</th><th>Created By</th><th>Requests</th><th>Bills</th></tr></thead>
                    <tbody>
                        @forelse($visits as $visit)
                            <tr>
                                <td>{{ $visit->visit_date?->format('M d, Y') ?? $visit->created_at?->format('M d, Y') }}</td>
                                <td>{{ $visit->visit_type ?? 'Visit' }}</td>
                                <td>{{ ucfirst($visit->status ?? 'active') }}</td>
                                <td>{{ $visit->createdBy?->name ?? 'System' }}</td>
                                <td>{{ $visit->service_requests_count + $visit->investigation_requests_count }}</td>
                                <td>{{ $visit->bills_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No visits found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($visits->hasPages())
                <div class="card-body border-top">
                    {{ $visits->appends(['section' => 'visits'])->fragment('visits')->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="tab-pane fade {{ $activeSection === 'admissions' ? 'show active' : '' }}" id="admissions" role="tabpanel" aria-labelledby="admissions-tab" tabindex="0">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Ward/Bed</th><th>Status</th><th>Admitted By</th><th>Discharge</th></tr></thead>
                    <tbody>
                        @forelse($admissions as $admission)
                            <tr>
                                <td>{{ $admission->date ?? $admission->created_at?->format('M d, Y') }}</td>
                                <td>{{ $admission->bed?->ward?->name ?? 'N/A' }} / {{ $admission->bed?->bed_no ?? 'N/A' }}</td>
                                <td>{{ ucfirst($admission->status ?? 'registered') }}</td>
                                <td>{{ $admission->admittedBy?->name ?? 'N/A' }}</td>
                                <td>{{ $admission->discharge ? $admission->discharge->created_at?->format('M d, Y') . ' by ' . ($admission->discharge->dischargedBy?->name ?? 'N/A') : 'Not discharged' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No current admission records found.</td></tr>
                        @endforelse
                        @foreach($legacyAdmissions as $legacyAdmission)
                            <tr>
                                <td>{{ $legacyAdmission->admission_date?->format('M d, Y') ?? 'N/A' }}</td>
                                <td>{{ $legacyAdmission->department ?? 'N/A' }} / {{ $legacyAdmission->bed_number ?? 'N/A' }}</td>
                                <td>{{ ucfirst($legacyAdmission->status ?? 'legacy') }}</td>
                                <td>{{ $legacyAdmission->admittedBy?->name ?? 'N/A' }}</td>
                                <td>{{ $legacyAdmission->discharge_date?->format('M d, Y') ?? 'Not discharged' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($admissions->hasPages() || $legacyAdmissions->hasPages())
                <div class="card-body border-top space-y-3">
                    @if($admissions->hasPages())
                        <div>
                            <p class="mb-2 text-sm font-medium text-med-muted">Current admissions</p>
                            {{ $admissions->appends(['section' => 'admissions'])->fragment('admissions')->links() }}
                        </div>
                    @endif
                    @if($legacyAdmissions->hasPages())
                        <div>
                            <p class="mb-2 text-sm font-medium text-med-muted">Legacy admissions</p>
                            {{ $legacyAdmissions->appends(['section' => 'admissions'])->fragment('admissions')->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="tab-pane fade {{ $activeSection === 'payments' ? 'show active' : '' }}" id="payments" role="tabpanel" aria-labelledby="payments-tab" tabindex="0">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Payment ID</th><th>Bill</th><th>Method</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date?->format('M d, Y H:i') ?? 'N/A' }}</td>
                                <td>{{ $payment->payment_id }}</td>
                                <td>{{ $payment->bill?->bill_number ?? 'N/A' }}</td>
                                <td>{{ $payment->paymentMethod?->name ?? 'N/A' }}</td>
                                <td>{{ ucfirst($payment->status ?? 'pending') }}</td>
                                <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No payments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
                <div class="card-body border-top">
                    {{ $payments->appends(['section' => 'payments'])->fragment('payments')->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="tab-pane fade {{ $activeSection === 'activity' ? 'show active' : '' }}" id="activity" role="tabpanel" aria-labelledby="activity-tab" tabindex="0">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @forelse($visits as $visit)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between gap-2">
                            <h6 class="mb-2">{{ $visit->visit_type ?? 'Visit' }} - {{ $visit->created_at?->format('M d, Y') }}</h6>
                            <span class="text-muted small">{{ $visit->vital_signs_count }} vital signs</span>
                        </div>
                        <div class="row g-3 small">
                            <div class="col-md-4">
                                <div class="fw-semibold mb-1">Services</div>
                                @forelse($visit->serviceRequests as $request)
                                    <div>{{ $request->service?->name ?? 'Service' }} <span class="text-muted">({{ $request->status ?? 'pending' }})</span></div>
                                @empty
                                    <div class="text-muted">None</div>
                                @endforelse
                            </div>
                            <div class="col-md-4">
                                <div class="fw-semibold mb-1">Investigations</div>
                                @forelse($visit->investigationRequests as $request)
                                    <div>{{ $request->investigation?->name ?? 'Investigation' }} <span class="text-muted">({{ $request->status ?? 'pending' }})</span></div>
                                @empty
                                    <div class="text-muted">None</div>
                                @endforelse
                            </div>
                            <div class="col-md-4">
                                <div class="fw-semibold mb-1">Activities</div>
                                @forelse($visit->visitActivities->take(5) as $activity)
                                    <div>{{ $activity->activity }} <span class="text-muted">{{ $activity->created_at?->format('M d, H:i') }}</span></div>
                                @empty
                                    <div class="text-muted">None</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No related visit information found.</p>
                @endforelse
            </div>
            @if($visits->hasPages())
                <div class="card-footer">
                    {{ $visits->appends(['section' => 'activity'])->fragment('activity')->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
