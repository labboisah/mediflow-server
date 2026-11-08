@extends('layouts.modern')

@section('title', 'Antenatal Care Record')

@section('content')
    @php
        $patient = $antenatalCare->patient;
        $items = [
            'Pregnancy Details' => [
                'LMP' => $antenatalCare->last_menstrual_period?->format('M d, Y') ?? 'N/A',
                'EDD' => $antenatalCare->expected_delivery_date?->format('M d, Y') ?? 'N/A',
                'Gestational Weeks' => $antenatalCare->gestational_weeks ? $antenatalCare->gestational_weeks . ' weeks' : 'N/A',
                'Number of Fetuses' => $antenatalCare->number_of_fetuses ?? 'N/A',
                'Pregnancy Type' => $antenatalCare->pregnancy_type ?? 'N/A',
            ],
            'Vital Signs' => [
                'Blood Pressure' => $antenatalCare->blood_pressure ?? 'N/A',
                'Weight' => $antenatalCare->weight ? $antenatalCare->weight . ' kg' : 'N/A',
                'Height' => $antenatalCare->height ? $antenatalCare->height . ' cm' : 'N/A',
            ],
            'Clinical Findings' => [
                'Abdominal Examination' => $antenatalCare->abdominal_examination ?? 'N/A',
                'Fundal Height' => $antenatalCare->fundal_height ?? 'N/A',
                'Fetal Heart Rate' => $antenatalCare->fetal_heart_rate ?? 'N/A',
                'Fetal Movement' => $antenatalCare->fetal_movement ?? 'N/A',
                'Vaginal Examination' => $antenatalCare->vaginal_examination ?? 'N/A',
            ],
            'Investigations And Plan' => [
                'Urine Analysis' => $antenatalCare->urine_analysis ?? 'N/A',
                'Blood Tests' => $antenatalCare->blood_tests ?? 'N/A',
                'Ultrasound Findings' => $antenatalCare->ultrasound_findings ?? 'N/A',
                'Risk Factors' => $antenatalCare->risk_factors ?? 'N/A',
                'Complications' => $antenatalCare->complications ?? 'N/A',
                'Management Plan' => $antenatalCare->management_plan ?? 'N/A',
                'Counseling Topics' => $antenatalCare->counseling_topics ?? 'N/A',
                'Took Supplements' => $antenatalCare->took_supplements ? 'Yes' : 'No',
                'Clinical Notes' => $antenatalCare->clinical_notes ?? 'N/A',
            ],
        ];
    @endphp

    <x-ui.page title="Antenatal Care Record" subtitle="{{ $patient->demographic->first_name }} {{ $patient->demographic->last_name }} | Hospital #{{ $patient->hospital_number }}">
        <x-slot name="actions">
            @if(auth()->user()->hasAnyRole(['midwife', 'administrator']))
                <a href="{{ route('midwife.antenatal.edit', $antenatalCare) }}" class="inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">Edit</a>
                <a href="{{ route('midwife.antenatal.patient-records', $patient) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
            @endif
        </x-slot>

        <div class="grid gap-6 xl:grid-cols-12">
            <div class="space-y-6 xl:col-span-8">
                @foreach($items as $title => $details)
                    <x-ui.card title="{{ $title }}">
                        <dl class="grid gap-4 md:grid-cols-2">
                            @foreach($details as $label => $value)
                                <div>
                                    <dt class="text-sm font-medium text-med-muted">{{ $label }}</dt>
                                    <dd class="mt-1 whitespace-pre-line text-sm font-semibold text-med-ink">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-ui.card>
                @endforeach
            </div>
            <aside class="space-y-6 xl:col-span-4">
                <x-ui.card title="Patient Information">
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-med-muted">Hospital Number</dt><dd class="font-semibold text-med-ink">{{ $patient->hospital_number }}</dd></div>
                        <div><dt class="text-med-muted">Age</dt><dd class="font-semibold text-med-ink">{{ $patient->age() }} years</dd></div>
                        <div><dt class="text-med-muted">Gender</dt><dd class="font-semibold text-med-ink">{{ $patient->demographic->gender ?? 'N/A' }}</dd></div>
                        <div><dt class="text-med-muted">Status</dt><dd><x-ui.badge :variant="$antenatalCare->status === 'normal' ? 'success' : ($antenatalCare->status === 'high_risk' ? 'danger' : 'warning')">{{ str($antenatalCare->status)->headline() }}</x-ui.badge></dd></div>
                    </dl>
                </x-ui.card>
                <x-ui.card title="Record Metadata">
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-med-muted">Recorded By</dt><dd class="font-semibold text-med-ink">{{ $antenatalCare->recordedBy?->name ?? 'N/A' }}</dd></div>
                        <div><dt class="text-med-muted">Created</dt><dd class="font-semibold text-med-ink">{{ $antenatalCare->created_at?->format('M d, Y h:i A') }}</dd></div>
                        <div><dt class="text-med-muted">Visit</dt><dd class="font-semibold text-med-ink">{{ $antenatalCare->visit?->visit_type ?? 'N/A' }}</dd></div>
                    </dl>
                </x-ui.card>
            </aside>
        </div>
    </x-ui.page>
@endsection
