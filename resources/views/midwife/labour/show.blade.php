@extends('layouts.modern')

@section('title', 'Labour Record')

@section('content')
    @php
        $patient = $labour->patient;
        $sections = [
            'Labour Information' => ['Labour Onset Time' => optional($labour->labour_onset_time)->format('M d, Y h:i A') ?? 'N/A', 'Mode of Onset' => str($labour->mode_of_onset ?? 'N/A')->headline(), 'Gestational Weeks' => $labour->gestational_weeks ?? 'N/A', 'Labour Type' => $labour->labour_type ?? 'N/A', 'Reason for Induction' => $labour->reason_for_induction ?? 'N/A', 'Previous Obstetric History' => $labour->previous_obstetric_history ?? 'N/A'],
            'Pre-Labour Assessment' => ['Cervical State' => $labour->cervical_state ?? 'N/A', 'Show' => str($labour->show ?? 'N/A')->headline(), 'Rupture of Membranes' => str($labour->rupture_of_membranes ?? 'N/A')->headline(), 'Liquor' => $labour->liquor ?? 'N/A'],
            'Maternal Vital Signs' => ['Blood Pressure' => $labour->blood_pressure ?? 'N/A', 'Pulse Rate' => $labour->pulse_rate ? $labour->pulse_rate . ' bpm' : 'N/A', 'Temperature' => $labour->temperature ? $labour->temperature . ' C' : 'N/A', 'Respiration Rate' => $labour->respiration_rate ?? 'N/A'],
            'Labour Progress' => ['Stage' => str($labour->stage ?? 'N/A')->headline(), 'Status' => str($labour->status ?? 'N/A')->headline(), 'First Stage Started' => optional($labour->first_stage_started_at)->format('M d, Y h:i A') ?? 'N/A', 'Second Stage Started' => optional($labour->second_stage_started_at)->format('M d, Y h:i A') ?? 'N/A', 'Third Stage Started' => optional($labour->third_stage_started_at)->format('M d, Y h:i A') ?? 'N/A'],
            'Fetal Monitoring And Notes' => ['Fetal Heart Rate' => $labour->fetal_heart_rate ? $labour->fetal_heart_rate . ' bpm' : 'N/A', 'Monitoring Notes' => $labour->fetal_monitoring_notes ?? 'N/A', 'Complications' => $labour->complications ?? 'N/A', 'Clinical Notes' => $labour->clinical_notes ?? 'N/A'],
        ];
    @endphp

    <x-ui.page title="Labour Record" subtitle="{{ $patient->name() ?? 'N/A' }} | {{ $patient->hospital_number ?? 'N/A' }} | {{ $labour->created_at->format('M d, Y') }}">
        <x-slot name="actions"><a href="{{ route('midwife.labour.edit', $labour) }}" class="inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">Edit</a><a href="{{ route('midwife.labour.index') }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a></x-slot>
        <div class="grid gap-6 xl:grid-cols-12">
            <div class="space-y-6 xl:col-span-8">
                @foreach($sections as $title => $items)
                    <x-ui.card title="{{ $title }}"><dl class="grid gap-4 md:grid-cols-2">@foreach($items as $label => $value)<div><dt class="text-sm font-medium text-med-muted">{{ $label }}</dt><dd class="mt-1 whitespace-pre-line text-sm font-semibold text-med-ink">{{ $value }}</dd></div>@endforeach</dl></x-ui.card>
                @endforeach
            </div>
            <aside class="space-y-6 xl:col-span-4">
                <x-ui.card title="Patient Information"><dl class="space-y-3 text-sm"><div><dt class="text-med-muted">Hospital Number</dt><dd class="font-semibold text-med-ink">{{ $patient->hospital_number }}</dd></div><div><dt class="text-med-muted">Patient Name</dt><dd class="font-semibold text-med-ink">{{ $patient->name() }}</dd></div><div><dt class="text-med-muted">Age</dt><dd class="font-semibold text-med-ink">{{ $patient->age() }} years</dd></div><div><dt class="text-med-muted">Gender</dt><dd class="font-semibold text-med-ink">{{ $patient->demographic->gender }}</dd></div></dl></x-ui.card>
                <x-ui.card title="Record Metadata"><dl class="space-y-3 text-sm"><div><dt class="text-med-muted">Created At</dt><dd class="font-semibold text-med-ink">{{ $labour->created_at->format('M d, Y h:i A') }}</dd></div><div><dt class="text-med-muted">Updated At</dt><dd class="font-semibold text-med-ink">{{ $labour->updated_at->format('M d, Y h:i A') }}</dd></div><div><dt class="text-med-muted">Recorded By</dt><dd class="font-semibold text-med-ink">{{ $labour->recordedBy->name ?? 'N/A' }}</dd></div><div><dt class="text-med-muted">Record ID</dt><dd class="font-semibold text-med-ink">#{{ $labour->id }}</dd></div></dl></x-ui.card>
            </aside>
        </div>
    </x-ui.page>
@endsection
