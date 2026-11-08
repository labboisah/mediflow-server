@extends('layouts.modern')

@section('title', 'Nurse Dashboard')

@section('content')
<x-ui.page title="Nurse Dashboard" subtitle="Review today clinical activity and open common nursing workspaces.">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.card><p class="text-sm font-medium text-med-muted">Vital Signs Today</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['vitalSignsToday']) }}</p></x-ui.card>
        <x-ui.card><p class="text-sm font-medium text-med-muted">Observations Today</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['observationsToday']) }}</p></x-ui.card>
        <x-ui.card><p class="text-sm font-medium text-med-muted">Drug Charts Today</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['drugChartsToday']) }}</p></x-ui.card>
        <x-ui.card><p class="text-sm font-medium text-med-muted">Walk-in Patients</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['walkInPatients']) }}</p></x-ui.card>
    </div>

    <x-ui.card title="Quick Actions">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('nurse.patient.index') }}" class="mf-focus rounded-md border border-med-line bg-white p-4 transition hover:bg-med-canvas">
                <p class="font-semibold text-med-ink"><i class="bi bi-list-check mr-2 text-med-primary"></i>Patient Queue</p>
                <p class="mt-1 text-sm text-med-muted">Browse active nursing requests</p>
            </a>
            <a href="{{ route('patient.search') }}" class="mf-focus rounded-md border border-med-line bg-white p-4 transition hover:bg-med-canvas">
                <p class="font-semibold text-med-ink"><i class="bi bi-search mr-2 text-med-primary"></i>Search Patient</p>
                <p class="mt-1 text-sm text-med-muted">Find by number, name, or phone</p>
            </a>
            <a href="{{ route('nurse.clinicals.vital-signs') }}" class="mf-focus rounded-md border border-med-line bg-white p-4 transition hover:bg-med-canvas">
                <p class="font-semibold text-med-ink"><i class="bi bi-heart-pulse mr-2 text-med-danger"></i>Vital Signs</p>
                <p class="mt-1 text-sm text-med-muted">Open vital signs records</p>
            </a>
            <a href="{{ route('nurse.admissions.index') }}" class="mf-focus rounded-md border border-med-line bg-white p-4 transition hover:bg-med-canvas">
                <p class="font-semibold text-med-ink"><i class="bi bi-hospital mr-2 text-med-info"></i>Admissions</p>
                <p class="mt-1 text-sm text-med-muted">Review admitted patients</p>
            </a>
        </div>
    </x-ui.card>

    <div class="grid gap-5 xl:grid-cols-2">
        <x-ui.card title="Recent Visiting Patients" subtitle="Visits recorded today.">
            @if($recentVisits->count() > 0)
                <x-ui.table>
                    <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted"><tr><th class="px-4 py-3">Hospital No</th><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Visit Type</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                    <tbody class="divide-y divide-med-line bg-white">
                        @foreach($recentVisits as $visit)
                            @php($patient = $visit->patient)
                            <tr class="hover:bg-med-canvas/50"><td class="px-4 py-3"><x-ui.badge variant="info">{{ $patient?->hospital_number ?? 'N/A' }}</x-ui.badge></td><td class="px-4 py-3"><p class="font-semibold text-med-ink">{{ $patient?->demographic?->full_name ?? 'N/A' }}</p><p class="text-xs text-med-muted">{{ $patient?->demographic?->phone_number ?? 'N/A' }}</p></td><td class="px-4 py-3 text-med-muted">{{ $visit->visit_type ?? 'N/A' }}</td><td class="px-4 py-3 text-right">@if($patient)<a href="{{ route('patient.show', $patient) }}" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">View</a>@endif</td></tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @else
                <x-ui.empty-state title="No Visits Today" message="No patient visit has been recorded today." />
            @endif
        </x-ui.card>

        <x-ui.card title="Vital Signs Recorded Today" subtitle="Latest vital signs captured today.">
            @if($recentVitalSigns->count() > 0)
                <x-ui.table>
                    <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted"><tr><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Temp</th><th class="px-4 py-3">BP</th><th class="px-4 py-3">By</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                    <tbody class="divide-y divide-med-line bg-white">
                        @foreach($recentVitalSigns as $vitalSign)
                            @php($patient = $vitalSign->patientVisit?->patient)
                            <tr class="hover:bg-med-canvas/50"><td class="px-4 py-3"><p class="font-semibold text-med-ink">{{ $patient?->demographic?->full_name ?? 'N/A' }}</p><p class="text-xs text-med-muted">{{ $patient?->hospital_number ?? 'N/A' }}</p></td><td class="px-4 py-3 text-med-muted">{{ $vitalSign->body_temperature ?? 'N/A' }}</td><td class="px-4 py-3 text-med-muted">{{ $vitalSign->blood_pressure_systolic ?? 'N/A' }} / {{ $vitalSign->blood_pressure_diastolic ?? 'N/A' }}</td><td class="px-4 py-3 text-med-muted">{{ $vitalSign->recordedBy?->name ?? 'N/A' }}</td><td class="px-4 py-3 text-right">@if($patient)<a href="{{ route('patient.show', $patient) }}" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">View</a>@endif</td></tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @else
                <x-ui.empty-state title="No Vital Signs Today" message="No vital signs have been recorded today." />
            @endif
        </x-ui.card>
    </div>
</x-ui.page>
@endsection
