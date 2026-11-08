@extends('layouts.modern')

@section('title', 'Index.Blade')
@section('page-title', 'Index.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Admission Management')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 d-flex align-items-center">
            <i class="bi bi-hospital text-success me-2"></i>
            Admission Management
        </h1>
        <p class="text-muted mb-0">
            {{ $startDate ? $startDate->format('M d, Y') : 'All dates' }}
            to
            {{ $endDate ? $endDate->format('M d, Y') : 'All dates' }}
        </p>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach([
        'Total' => $summary['total'],
        'Active' => $summary['active'],
        'Discharged' => $summary['discharged'],
        'SAMA' => $summary['sama'],
        'Absconded' => $summary['absconded'],
    ] as $label => $value)
        <div class="col-6 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="h4 mb-0">{{ number_format($value) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route($routePrefix . '.admissions.index') }}" class="row g-3 align-items-end">
            <div class="col-12 col-xl-3">
                <label for="search" class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" id="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Hospital no, name, phone, status">
                </div>
            </div>

            <div class="col-6 col-xl-2">
                <label for="ward_id" class="form-label">Ward</label>
                <select id="ward_id" name="ward_id" class="form-select">
                    <option value="">All wards</option>
                    @foreach($wards as $ward)
                        <option value="{{ $ward->id }}" @selected((string) request('ward_id') === (string) $ward->id)>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-xl-2">
                <label for="gender" class="form-label">Gender</label>
                <select id="gender" name="gender" class="form-select">
                    <option value="">All</option>
                    <option value="Male" @selected(request('gender') === 'Male')>Male</option>
                    <option value="Female" @selected(request('gender') === 'Female')>Female</option>
                    <option value="Other" @selected(request('gender') === 'Other')>Other</option>
                </select>
            </div>

            <div class="col-6 col-xl-2">
                <label for="start_date" class="form-label">From</label>
                <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" class="form-control">
            </div>

            <div class="col-6 col-xl-2">
                <label for="end_date" class="form-label">To</label>
                <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" class="form-control">
            </div>

            <div class="col-12 col-xl-1 d-grid">
                <button type="submit" class="btn btn-success" title="Apply filters">
                    <i class="bi bi-funnel"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Hospital No</th>
                    <th>Patient</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Ward / Bed</th>
                    <th>Admission Date</th>
                    <th>Status</th>
                    <th>Admitted By</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admissions as $admission)
                    @php
                        $patient = $admission->patientVisit?->patient;
                        $status = strtolower((string) $admission->status);
                        $canCloseAdmission = ! in_array($status, ['discharged', 'closed', 'absconded', 'sama'], true);
                    @endphp
                    <tr>
                        <td><span class="badge bg-primary">{{ $patient?->hospital_number ?? 'N/A' }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $patient?->demographic?->full_name ?? 'N/A' }}</div>
                            <div class="text-muted small">{{ $patient?->demographic?->email ?? 'No email' }}</div>
                        </td>
                        <td>{{ $patient?->demographic?->gender ?? 'N/A' }}</td>
                        <td>{{ $patient?->demographic?->phone_number ?? 'N/A' }}</td>
                        <td>{{ $admission->bed?->ward?->name ?? 'N/A' }} / {{ $admission->bed?->bed_no ?? 'N/A' }}</td>
                        <td>{{ $admission->date ? date('M d, Y', strtotime($admission->date)) : 'N/A' }} {{ $admission->time }}</td>
                        <td><span class="badge bg-secondary">{{ str($admission->status ?? 'registered')->headline() }}</span></td>
                        <td>{{ $admission->admittedBy?->name ?? 'N/A' }}</td>
                        <td class="text-end">
                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                @if($patient)
                                    <a href="{{ route($routePrefix . '.patient-register.summary', $patient) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>
                                        View
                                    </a>
                                @endif

                                @if($canCloseAdmission)
                                    <form method="POST" action="{{ route($routePrefix . '.admissions.discharge', $admission) }}" onsubmit="return confirm('Discharge this patient and make the bed vacant?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-box-arrow-right me-1"></i>
                                            Discharge
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route($routePrefix . '.admissions.sama', $admission) }}" onsubmit="return confirm('Mark this patient as SAMA and make the bed vacant?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            SAMA
                                        </button>
                                    </form>
                                @elseif(! $patient)
                                    <span class="text-muted small">No profile</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">No admissions found for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($admissions->hasPages())
        <div class="card-footer bg-light">
            {{ $admissions->links() }}
        </div>
    @endif
</div>
@endsection

