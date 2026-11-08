@extends('layouts.modern')

@section('title', 'Patient Profile - ' . ($patient->demographic?->full_name ?? 'Patient'))

@section('content')
@php
    $patient->loadMissing(['demographic.lga', 'nextOfKin', 'patientVisits', 'appointments', 'referrals']);
    $currentVisit = $patient->currentVisit();
    $paymentSummary = $patient->payment();
    $pendingBalance = $paymentSummary['pending'] ?? 0;
    $activeAdmission = $currentVisit?->admissions()->with('bed.ward')->whereNotIn('status', ['discharged', 'absconded', 'sama'])->latest()->first();
    $latestNote = $currentVisit ? $currentVisit->continuations()->latest()->first() : null;
    $recentActivities = $currentVisit ? $currentVisit->visitActivities()->with('recordedBy')->latest()->limit(8)->get() : collect();
    $roleBackRoute = auth()->user()->hasRole('doctor') ? 'doctor.patient.index' : (auth()->user()->hasRole('nurse') ? 'nurse.patient.index' : 'dashboard');
    $clinicalActions = [];

    if (auth()->user()->hasRole('nurse') || auth()->user()->hasRole('midwife')) {
        $clinicalActions[] = ['label' => 'Record Vital Signs', 'route' => 'patient.vitalsign.create', 'variant' => 'danger'];
        $clinicalActions[] = ['label' => 'Record Observations', 'route' => 'patient.observation.record', 'variant' => 'danger'];
        $clinicalActions[] = ['label' => 'Drug Chart', 'route' => 'patient.drugchart.record', 'variant' => 'success'];
        $clinicalActions[] = ['label' => 'Fluid Balance', 'route' => 'patient.fluidbalance.record', 'variant' => 'success'];
    }

    if (auth()->user()->hasRole('doctor') || auth()->user()->hasRole('midwife')) {
        $clinicalActions[] = ['label' => 'Admit Patient', 'route' => 'patient.admission.create', 'variant' => 'success'];
        $clinicalActions[] = ['label' => 'Continuation Sheet', 'route' => 'patient.continuation.create', 'variant' => 'info'];
        $clinicalActions[] = ['label' => 'Prescription', 'route' => 'patient.prescription.create', 'variant' => 'success'];
    }

    if (auth()->user()->hasRole('doctor') || auth()->user()->hasRole('nurse') || auth()->user()->hasRole('midwife')) {
        $clinicalActions[] = ['label' => 'Investigation Request', 'route' => 'patient.investigation.create', 'variant' => 'danger'];
    }
@endphp

<x-ui.page title="{{ $patient->demographic?->full_name ?? 'Patient Profile' }}" subtitle="Hospital Number: {{ $patient->hospital_number }}">
    <x-slot:actions>
        <a href="{{ route($roleBackRoute) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
        @if($activeAdmission)
            <a href="{{ route('patient.discharge.create', $activeAdmission) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-danger bg-white px-3 py-2 text-sm font-semibold text-med-danger transition hover:bg-red-50">Discharge</a>
        @endif
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-[1.3fr_0.9fr]">
        <div class="space-y-6">
            <x-ui.card title="Clinical Actions" subtitle="Actions available for your current role.">
                @if(count($clinicalActions) > 0)
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($clinicalActions as $action)
                            <a href="{{ route($action['route'], $patient) }}" class="rounded-md border border-med-line bg-white p-4 transition hover:bg-med-canvas">
                                <p class="font-semibold text-med-ink">{{ $action['label'] }}</p>
                                <p class="mt-1 text-sm text-med-muted">Open workspace</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state title="No Clinical Actions" message="Your current role has no clinical actions on this profile." />
                @endif
            </x-ui.card>

            <x-ui.card title="Patient Information" subtitle="Core demographic and contact information.">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Full Name</p><p class="mt-1 font-semibold text-med-ink">{{ $patient->demographic?->full_name ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Gender</p><p class="mt-1 text-med-ink">{{ $patient->demographic?->gender ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Age</p><p class="mt-1 text-med-ink">{{ $patient->demographic?->age ? $patient->demographic->age . ' years' : 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Phone</p><p class="mt-1 text-med-ink">{{ $patient->demographic?->phone_number ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Marital Status</p><p class="mt-1 text-med-ink">{{ $patient->demographic?->marital_status ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">LGA</p><p class="mt-1 text-med-ink">{{ $patient->demographic?->lga?->name ?? 'N/A' }}</p></div>
                    <div class="md:col-span-2 xl:col-span-3"><p class="text-xs font-semibold uppercase text-med-muted">Address</p><p class="mt-1 text-med-ink">{{ $patient->demographic?->address ?? 'N/A' }}</p></div>
                </div>
            </x-ui.card>

            <x-ui.card title="Visit History" subtitle="Recent visits for this patient.">
                @if($patient->patientVisits->count() > 0)
                    <x-ui.table>
                        <thead class="bg-med-canvas text-left text-xs font-semibold uppercase tracking-wide text-med-muted"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Created</th></tr></thead>
                        <tbody class="divide-y divide-med-line bg-white">
                            @foreach($patient->patientVisits->sortByDesc('created_at')->take(10) as $visit)
                                <tr><td class="px-4 py-3 text-med-ink">{{ $visit->visit_date?->format('M d, Y') ?? 'N/A' }}</td><td class="px-4 py-3 text-med-muted">{{ $visit->visit_type ?? 'N/A' }}</td><td class="px-4 py-3"><x-ui.badge variant="{{ $visit->status === 'Active' ? 'success' : 'neutral' }}">{{ $visit->status ?? 'N/A' }}</x-ui.badge></td><td class="px-4 py-3 text-med-muted">{{ $visit->created_at?->diffForHumans() ?? 'N/A' }}</td></tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @else
                    <x-ui.empty-state title="No Visit History" message="No visit history is available for this patient." />
                @endif
            </x-ui.card>

            <x-ui.card title="Recent Activities" subtitle="Latest actions from the current visit.">
                @if($recentActivities->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentActivities as $activity)
                            <div class="rounded-md border border-med-line bg-med-canvas/50 px-4 py-3"><p class="font-semibold text-med-ink">{{ $activity->activity }}</p><p class="mt-1 text-sm text-med-muted">{{ $activity->created_at?->diffForHumans() }} by {{ $activity->recordedBy?->name ?? 'Unknown user' }}</p></div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state title="No Recent Activities" message="Activities will appear here as clinical work is recorded." />
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Current Status">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3"><span class="text-sm text-med-muted">Visit Status</span><x-ui.badge variant="{{ $currentVisit?->status === 'Active' ? 'success' : 'neutral' }}">{{ $currentVisit?->status ?? 'No active visit' }}</x-ui.badge></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-sm text-med-muted">Admission</span><span class="text-sm font-semibold text-med-ink">{{ $activeAdmission ? (($activeAdmission->bed?->ward?->name ?? 'Ward') . ' / ' . ($activeAdmission->bed?->bed_no ?? 'Bed')) : ($currentVisit ? $currentVisit->admissionStatus() : 'N/A') }}</span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-sm text-med-muted">Pending Balance</span><span class="text-sm font-semibold {{ $pendingBalance > 0 ? 'text-med-danger' : 'text-med-primary' }}">NGN {{ number_format($pendingBalance, 2) }}</span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-sm text-med-muted">Last Visit</span><span class="text-sm font-semibold text-med-ink">{{ $currentVisit?->visit_date?->format('M d, Y') ?? 'N/A' }}</span></div>
                </div>
            </x-ui.card>

            <x-ui.card title="Next Of Kin">
                <div class="space-y-4">
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Name</p><p class="mt-1 font-semibold text-med-ink">{{ $patient->nextOfKin?->name ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Relationship</p><p class="mt-1 text-med-ink">{{ $patient->nextOfKin?->relationship ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Telephone</p><p class="mt-1 text-med-ink">{{ $patient->nextOfKin?->telephone ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Address</p><p class="mt-1 text-med-ink">{{ $patient->nextOfKin?->contact_address ?? 'N/A' }}</p></div>
                </div>
            </x-ui.card>

            <x-ui.card title="Latest Continuation Note">
                @if($latestNote)
                    <div class="space-y-3 text-sm text-med-ink">
                        <p><span class="font-semibold">Notes:</span> {{ $latestNote->note }}</p>
                        <p><span class="font-semibold">History:</span> {{ $latestNote->history }}</p>
                        <p><span class="font-semibold">Examination:</span> {{ $latestNote->examination }}</p>
                        <p><span class="font-semibold">Diagnosis:</span> {{ $latestNote->diagnose }}</p>
                        <p><span class="font-semibold">Plan:</span> {{ $latestNote->plan }}</p>
                    </div>
                @else
                    <p class="text-sm text-med-muted">No continuation note is available for the current visit.</p>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-ui.page>
@endsection
