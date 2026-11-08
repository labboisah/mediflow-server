@extends('layouts.modern')

@section('title', 'Patient Details - ' . ($patient->demographic?->full_name ?? 'Patient'))

@section('content')
@php
    $currentVisit = $patient->currentVisit();
    $paymentSummary = $patient->payment();
    $pendingBalance = $paymentSummary['pending'] ?? 0;
    $latestNote = $currentVisit ? $currentVisit->continuations()->latest()->first() : null;
    $recentActivities = $currentVisit ? $currentVisit->visitActivities()->with('recordedBy')->latest()->limit(8)->get() : collect();
    $latestAppointments = $patient->appointments->sortByDesc('appointment_date')->take(5);
    $latestReferrals = $patient->referrals->sortByDesc('referral_date')->take(5);
    $activeAdmission = $currentVisit?->admissions()->with('bed.ward')->whereNotIn('status', ['discharged', 'absconded', 'sama'])->latest()->first();
@endphp

<x-ui.page title="{{ $patient->demographic?->full_name ?? 'Patient Details' }}" subtitle="Hospital Number: {{ $patient->hospital_number }}">
    <x-slot:actions>
        <a href="{{ route('record.patients.edit.form', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
            Edit Patient
        </a>
        <a href="{{ route('record.visits.create.form', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
            Record Visit
        </a>
        <a href="{{ route('record.appointments.create', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
            Schedule Appointment
        </a>
        <a href="{{ route('record.referrals.create', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
            Record Referral
        </a>
        @if($currentVisit && ! $activeAdmission)
            <a href="{{ route('record.admissions.create', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                Record Admission
            </a>
        @endif
        @if($activeAdmission)
            <a href="{{ route('record.discharges.create', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-danger bg-white px-3 py-2 text-sm font-semibold text-med-danger transition hover:bg-red-50">
                Record Discharge
            </a>
        @endif
        <a href="{{ route('record.patients.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
            Back To List
        </a>
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-[1.5fr_0.8fr]">
        <div class="space-y-6">
            <x-ui.card title="Patient Information" subtitle="Core registration and demographic data.">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Full Name</p><p class="mt-1 text-base font-semibold text-med-ink">{{ $patient->demographic?->full_name ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Gender</p><p class="mt-1 text-base text-med-ink">{{ $patient->demographic?->gender ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Age</p><p class="mt-1 text-base text-med-ink">{{ $patient->demographic?->age ? $patient->demographic->age . ' years' : 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Date Of Birth</p><p class="mt-1 text-base text-med-ink">{{ $patient->demographic?->date_of_birth?->format('M d, Y') ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Phone</p><p class="mt-1 text-base text-med-ink">{{ $patient->demographic?->phone_number ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Email</p><p class="mt-1 text-base text-med-ink">{{ $patient->demographic?->email ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Marital Status</p><p class="mt-1 text-base text-med-ink">{{ $patient->demographic?->marital_status ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Occupation</p><p class="mt-1 text-base text-med-ink">{{ $patient->demographic?->occupation ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">LGA</p><p class="mt-1 text-base text-med-ink">{{ $patient->demographic?->lga?->name ?? 'N/A' }}</p></div>
                    <div class="md:col-span-2 xl:col-span-3"><p class="text-xs font-semibold uppercase text-med-muted">Address</p><p class="mt-1 text-base text-med-ink">{{ $patient->demographic?->address ?? 'N/A' }}</p></div>
                </div>
            </x-ui.card>

            <x-ui.card title="Visit History" subtitle="Latest visits recorded for this patient.">
                @if($patient->visits->count() > 0)
                    <x-ui.table>
                        <thead class="bg-med-canvas text-left text-xs font-semibold uppercase tracking-wide text-med-muted"><tr><th class="px-4 py-3">Visit Date</th><th class="px-4 py-3">Visit Type</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Created</th></tr></thead>
                        <tbody class="divide-y divide-med-line bg-white">
                            @foreach($patient->visits->sortByDesc('created_at')->take(8) as $visit)
                                <tr>
                                    <td class="px-4 py-3 text-med-ink">{{ $visit->visit_date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-med-muted">{{ $visit->visit_type ?? 'N/A' }}</td>
                                    <td class="px-4 py-3"><x-ui.badge variant="{{ $visit->status === 'Active' ? 'success' : 'neutral' }}">{{ $visit->status ?? 'N/A' }}</x-ui.badge></td>
                                    <td class="px-4 py-3 text-med-muted">{{ $visit->created_at?->diffForHumans() ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @else
                    <x-ui.empty-state title="No Visits Recorded" message="Create a visit when this patient arrives for care." />
                @endif
            </x-ui.card>

            <x-ui.card title="Appointments" subtitle="Recent and upcoming appointment records.">
                @if($latestAppointments->count() > 0)
                    <div class="space-y-3">
                        @foreach($latestAppointments as $appointment)
                            <div class="flex flex-col gap-2 rounded-md border border-med-line bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-med-ink">{{ $appointment->appointment_date?->format('M d, Y') ?? 'N/A' }} at {{ $appointment->appointment_time ?? 'N/A' }}</p>
                                    <p class="mt-1 text-sm text-med-muted">{{ $appointment->notes ?: 'No notes recorded.' }}</p>
                                </div>
                                <x-ui.badge variant="{{ $appointment->status === 'Scheduled' ? 'success' : ($appointment->status === 'Cancelled' ? 'danger' : 'info') }}">{{ $appointment->status }}</x-ui.badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state title="No Appointments" message="Schedule an appointment when a follow-up date is needed." />
                @endif
            </x-ui.card>

            <x-ui.card title="Referrals" subtitle="Recent referral records for this patient.">
                @if($latestReferrals->count() > 0)
                    <div class="space-y-3">
                        @foreach($latestReferrals as $referral)
                            <div class="rounded-md border border-med-line bg-white px-4 py-3">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="font-semibold text-med-ink">{{ $referral->referred_to_department }}</p>
                                    <x-ui.badge variant="{{ $referral->status === 'Pending' ? 'warning' : ($referral->status === 'Rejected' ? 'danger' : 'success') }}">{{ $referral->status }}</x-ui.badge>
                                </div>
                                <p class="mt-2 text-sm text-med-muted">{{ $referral->reason_for_referral }}</p>
                                <p class="mt-2 text-xs font-semibold uppercase text-med-muted">{{ $referral->referral_date?->format('M d, Y') ?? 'N/A' }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state title="No Referrals" message="Referral records will appear here after they are created." />
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
                    <x-ui.empty-state title="No Recent Activities" message="Activities will appear here after a visit workflow starts." />
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Current Status">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3"><span class="text-sm text-med-muted">Patient Type</span><x-ui.badge variant="{{ $patient->is_walkIn ? 'warning' : 'success' }}">{{ $patient->is_walkIn ? 'Walk-in' : 'Registered' }}</x-ui.badge></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-sm text-med-muted">Visit Status</span><span class="text-sm font-semibold text-med-ink">{{ $currentVisit?->status ?? 'No Active Visit' }}</span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-sm text-med-muted">Admission Status</span><span class="text-sm font-semibold text-med-ink">{{ $activeAdmission ? (($activeAdmission->bed?->ward?->name ?? 'Ward') . ' / ' . ($activeAdmission->bed?->bed_no ?? 'Bed')) : ($currentVisit ? $currentVisit->admissionStatus() : 'N/A') }}</span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-sm text-med-muted">Pending Balance</span><span class="text-sm font-semibold {{ $pendingBalance > 0 ? 'text-med-danger' : 'text-med-primary' }}">NGN {{ number_format($pendingBalance, 2) }}</span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-sm text-med-muted">Registered</span><span class="text-sm font-semibold text-med-ink">{{ $patient->created_at?->format('M d, Y') ?? 'N/A' }}</span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-sm text-med-muted">Last Visit</span><span class="text-sm font-semibold text-med-ink">{{ $currentVisit?->visit_date?->format('M d, Y') ?? 'N/A' }}</span></div>
                </div>
            </x-ui.card>

            <x-ui.card title="Next Of Kin">
                <div class="space-y-4">
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Name</p><p class="mt-1 text-base font-semibold text-med-ink">{{ $patient->nextOfKin?->name ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Relationship</p><p class="mt-1 text-base text-med-ink">{{ $patient->nextOfKin?->relationship ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Telephone</p><p class="mt-1 text-base text-med-ink">{{ $patient->nextOfKin?->telephone ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Address</p><p class="mt-1 text-base text-med-ink">{{ $patient->nextOfKin?->contact_address ?? 'N/A' }}</p></div>
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

