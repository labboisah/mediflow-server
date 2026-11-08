@php
    $record = $labour ?? null;
    $fieldClass = 'mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm placeholder:text-med-muted/70';
    $labelClass = 'mb-1 block text-sm font-medium text-med-ink';
    $value = fn ($name, $default = '') => old($name, data_get($record, $name, $default));
    $dateValue = fn ($name, $default = null) => old($name, data_get($record, $name)?->format('Y-m-d\TH:i') ?? $default);
    $patient = $patient ?? $record?->patient;
@endphp

@if ($errors->any())
    <div class="rounded-md border border-med-danger/25 bg-red-50 px-4 py-3 text-sm text-med-danger">
        <p class="font-semibold">Please fix the highlighted errors.</p>
        <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="grid gap-6 xl:grid-cols-12">
    <div class="space-y-6 xl:col-span-8">
        <x-ui.card title="Labour Information">
            <div class="grid gap-4 md:grid-cols-2">
                <label><span class="{{ $labelClass }}">Labour Onset Time</span><input type="datetime-local" name="labour_onset_time" value="{{ $dateValue('labour_onset_time', now()->format('Y-m-d\TH:i')) }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Mode of Onset</span><select name="mode_of_onset" class="{{ $fieldClass }}"><option value="">Select</option>@foreach(['spontaneous'=>'Spontaneous','induced'=>'Induced'] as $key=>$label)<option value="{{ $key }}" @selected($value('mode_of_onset')===$key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="{{ $labelClass }}">Gestational Weeks</span><input type="number" name="gestational_weeks" min="20" max="45" value="{{ $value('gestational_weeks') }}" class="{{ $fieldClass }}" placeholder="38"></label>
                <label><span class="{{ $labelClass }}">Labour Type</span><input type="text" name="labour_type" value="{{ $value('labour_type') }}" class="{{ $fieldClass }}" placeholder="Primigravida / Multigravida"></label>
                <label class="md:col-span-2"><span class="{{ $labelClass }}">Reason for Induction</span><textarea name="reason_for_induction" rows="3" class="{{ $fieldClass }}">{{ $value('reason_for_induction') }}</textarea></label>
                <label class="md:col-span-2"><span class="{{ $labelClass }}">Previous Obstetric History</span><textarea name="previous_obstetric_history" rows="3" class="{{ $fieldClass }}">{{ $value('previous_obstetric_history') }}</textarea></label>
            </div>
        </x-ui.card>

        <x-ui.card title="Pre-Labour Assessment">
            <div class="grid gap-4 md:grid-cols-3">
                <label><span class="{{ $labelClass }}">Cervical State</span><input type="text" name="cervical_state" value="{{ $value('cervical_state') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Show</span><select name="show" class="{{ $fieldClass }}"><option value="">Select</option>@foreach(['present'=>'Present','absent'=>'Absent'] as $key=>$label)<option value="{{ $key }}" @selected($value('show')===$key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="{{ $labelClass }}">Rupture of Membranes</span><select name="rupture_of_membranes" class="{{ $fieldClass }}"><option value="">Select</option>@foreach(['intact'=>'Intact','spontaneous rupture'=>'Spontaneous Rupture','artificial rupture'=>'Artificial Rupture'] as $key=>$label)<option value="{{ $key }}" @selected($value('rupture_of_membranes')===$key)>{{ $label }}</option>@endforeach</select></label>
                <label class="md:col-span-3"><span class="{{ $labelClass }}">Liquor</span><textarea name="liquor" rows="3" class="{{ $fieldClass }}">{{ $value('liquor') }}</textarea></label>
            </div>
        </x-ui.card>

        <x-ui.card title="Maternal Vital Signs">
            <div class="grid gap-4 md:grid-cols-4">
                <label><span class="{{ $labelClass }}">Blood Pressure</span><input type="text" name="blood_pressure" value="{{ $value('blood_pressure') }}" class="{{ $fieldClass }}" placeholder="120/80"></label>
                <label><span class="{{ $labelClass }}">Pulse Rate</span><input type="number" name="pulse_rate" value="{{ $value('pulse_rate') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Temperature</span><input type="number" step="0.1" name="temperature" value="{{ $value('temperature') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Respiration Rate</span><input type="number" name="respiration_rate" value="{{ $value('respiration_rate') }}" class="{{ $fieldClass }}"></label>
            </div>
        </x-ui.card>

        <x-ui.card title="Labour Progress And Fetal Monitoring">
            <div class="grid gap-4 md:grid-cols-3">
                <label><span class="{{ $labelClass }}">Stage</span><select name="stage" class="{{ $fieldClass }}"><option value="">Select</option>@foreach(['not_started'=>'Not Started','first_stage'=>'First Stage','second_stage'=>'Second Stage','third_stage'=>'Third Stage','completed'=>'Completed'] as $key=>$label)<option value="{{ $key }}" @selected($value('stage','not_started')===$key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="{{ $labelClass }}">Status</span><select name="status" class="{{ $fieldClass }}"><option value="">Select</option>@foreach(['ongoing'=>'Ongoing','completed'=>'Completed','complicated'=>'Complicated'] as $key=>$label)<option value="{{ $key }}" @selected($value('status','ongoing')===$key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="{{ $labelClass }}">Fetal Heart Rate</span><input type="number" name="fetal_heart_rate" value="{{ $value('fetal_heart_rate') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">First Stage Started</span><input type="datetime-local" name="first_stage_started_at" value="{{ $dateValue('first_stage_started_at') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Second Stage Started</span><input type="datetime-local" name="second_stage_started_at" value="{{ $dateValue('second_stage_started_at') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Third Stage Started</span><input type="datetime-local" name="third_stage_started_at" value="{{ $dateValue('third_stage_started_at') }}" class="{{ $fieldClass }}"></label>
                <label class="md:col-span-3"><span class="{{ $labelClass }}">Fetal Monitoring Notes</span><textarea name="fetal_monitoring_notes" rows="3" class="{{ $fieldClass }}">{{ $value('fetal_monitoring_notes') }}</textarea></label>
                <label class="md:col-span-3"><span class="{{ $labelClass }}">Complications</span><textarea name="complications" rows="3" class="{{ $fieldClass }}">{{ $value('complications') }}</textarea></label>
                <label class="md:col-span-3"><span class="{{ $labelClass }}">Clinical Notes</span><textarea name="clinical_notes" rows="4" class="{{ $fieldClass }}">{{ $value('clinical_notes') }}</textarea></label>
            </div>
        </x-ui.card>
    </div>

    <aside class="space-y-6 xl:col-span-4">
        <x-ui.card title="Patient Information">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-med-muted">Hospital Number</dt><dd class="font-semibold text-med-ink">{{ $patient->hospital_number }}</dd></div>
                <div><dt class="text-med-muted">Patient Name</dt><dd class="font-semibold text-med-ink">{{ $patient->name() }}</dd></div>
                <div><dt class="text-med-muted">Age</dt><dd class="font-semibold text-med-ink">{{ $patient->age() }} years</dd></div>
                <div><dt class="text-med-muted">Gender</dt><dd class="font-semibold text-med-ink">{{ $patient->demographic->gender ?? 'N/A' }}</dd></div>
            </dl>
        </x-ui.card>
        <x-ui.card title="Save Record">
            <div class="flex flex-col gap-2">
                <x-ui.button type="submit">{{ $submitLabel ?? 'Save Labour Record' }}</x-ui.button>
                <a href="{{ $cancelRoute ?? route('midwife.labour.index') }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Cancel</a>
            </div>
        </x-ui.card>
    </aside>
</div>
