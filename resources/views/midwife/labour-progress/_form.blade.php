@php
    $record = $progress ?? null;
    $labour = $labour ?? $record?->labour;
    $patient = $labour?->patient;
    $fieldClass = 'mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm placeholder:text-med-muted/70';
    $labelClass = 'mb-1 block text-sm font-medium text-med-ink';
    $value = fn ($name, $default = '') => old($name, data_get($record, $name, $default));
    $dateValue = fn ($name, $default = null) => old($name, data_get($record, $name)?->format('Y-m-d\TH:i') ?? $default);
@endphp

@if ($errors->any())
    <div class="rounded-md border border-med-danger/25 bg-red-50 px-4 py-3 text-sm text-med-danger"><p class="font-semibold">Please fix the highlighted errors.</p><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="grid gap-6 xl:grid-cols-12">
    <div class="space-y-6 xl:col-span-8">
        <x-ui.card title="Progress Details">
            <div class="grid gap-4 md:grid-cols-3">
                <label><span class="{{ $labelClass }}">Recorded At</span><input type="datetime-local" name="recorded_at" value="{{ $dateValue('recorded_at', now()->format('Y-m-d\TH:i')) }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Contraction Frequency (/10 min)</span><input type="number" name="contraction_frequency" min="0" max="20" value="{{ $value('contraction_frequency') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Contraction Duration (seconds)</span><input type="number" name="contraction_duration" min="0" max="300" value="{{ $value('contraction_duration') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Contraction Intensity</span><select name="contraction_intensity" class="{{ $fieldClass }}"><option value="">Select</option>@foreach(['mild'=>'Mild','moderate'=>'Moderate','strong'=>'Strong'] as $key=>$label)<option value="{{ $key }}" @selected($value('contraction_intensity')===$key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="{{ $labelClass }}">Cervical Dilation (cm)</span><input type="number" name="cervical_dilation" min="0" max="10" step="0.1" value="{{ $value('cervical_dilation') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Cervical Effacement (%)</span><input type="number" name="cervical_effacement" min="0" max="100" value="{{ $value('cervical_effacement') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Cervical Consistency</span><select name="cervical_consistency" class="{{ $fieldClass }}"><option value="">Select</option>@foreach(['firm'=>'Firm','medium'=>'Medium','soft'=>'Soft'] as $key=>$label)<option value="{{ $key }}" @selected($value('cervical_consistency')===$key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="{{ $labelClass }}">Cervical Position</span><select name="cervical_position" class="{{ $fieldClass }}"><option value="">Select</option>@foreach(['posterior'=>'Posterior','middle'=>'Middle','anterior'=>'Anterior'] as $key=>$label)<option value="{{ $key }}" @selected($value('cervical_position')===$key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="{{ $labelClass }}">Fetal Station</span><input type="number" name="fetal_station" min="-5" max="5" value="{{ $value('fetal_station') }}" class="{{ $fieldClass }}"></label>
            </div>
        </x-ui.card>

        <x-ui.card title="Fetal And Maternal Assessment">
            <div class="grid gap-4 md:grid-cols-3">
                <label><span class="{{ $labelClass }}">Fetal Position</span><select name="fetal_position" class="{{ $fieldClass }}"><option value="">Select</option>@foreach(['cephalic'=>'Cephalic','breech'=>'Breech','oblique'=>'Oblique','transverse'=>'Transverse'] as $key=>$label)<option value="{{ $key }}" @selected($value('fetal_position')===$key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="{{ $labelClass }}">Fetal Heart Rate</span><input type="number" name="fetal_heart_rate" min="100" max="190" value="{{ $value('fetal_heart_rate') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Fetal Heart Variability</span><select name="fetal_heart_variability" class="{{ $fieldClass }}"><option value="">Select</option>@foreach(['absent'=>'Absent','minimal'=>'Minimal','moderate'=>'Moderate','marked'=>'Marked'] as $key=>$label)<option value="{{ $key }}" @selected($value('fetal_heart_variability')===$key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="{{ $labelClass }}">Blood Pressure</span><input type="text" name="blood_pressure" value="{{ $value('blood_pressure') }}" class="{{ $fieldClass }}" placeholder="120/80"></label>
                <label><span class="{{ $labelClass }}">Temperature (C)</span><input type="number" step="0.1" name="temperature" min="34" max="42" value="{{ $value('temperature') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Pulse Rate</span><input type="number" name="pulse_rate" min="40" max="180" value="{{ $value('pulse_rate') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Uterine Tone</span><input type="text" name="uterine_tone" value="{{ $value('uterine_tone') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Uterine Tenderness</span><input type="text" name="uterine_tenderness" value="{{ $value('uterine_tenderness') }}" class="{{ $fieldClass }}"></label>
                <label class="flex items-center gap-2 pt-7 text-sm font-medium text-med-ink"><input type="checkbox" name="meconium_stained_liquor" value="1" class="rounded border-med-line text-med-primary focus:ring-med-primary" @checked((bool) $value('meconium_stained_liquor'))> Meconium stained liquor</label>
            </div>
        </x-ui.card>

        <x-ui.card title="Notes And Interventions">
            <div class="grid gap-4 md:grid-cols-2">
                @foreach(['vaginal_examination_findings'=>'Vaginal Examination Findings','fetal_movements'=>'Fetal Movements','maternal_pain_relief'=>'Maternal Pain Relief','coping_mechanisms'=>'Coping Mechanisms','interventions'=>'Interventions','medications_given'=>'Medications Given','observations_and_notes'=>'Observations and Notes'] as $name=>$label)
                    <label class="{{ $name === 'observations_and_notes' ? 'md:col-span-2' : '' }}"><span class="{{ $labelClass }}">{{ $label }}</span><textarea name="{{ $name }}" rows="3" class="{{ $fieldClass }}">{{ $value($name) }}</textarea></label>
                @endforeach
            </div>
        </x-ui.card>
    </div>

    <aside class="space-y-6 xl:col-span-4">
        <x-ui.card title="Labour Context"><dl class="space-y-3 text-sm"><div><dt class="text-med-muted">Patient</dt><dd class="font-semibold text-med-ink">{{ $patient?->name() ?? 'N/A' }}</dd></div><div><dt class="text-med-muted">Hospital Number</dt><dd class="font-semibold text-med-ink">{{ $patient?->hospital_number ?? 'N/A' }}</dd></div><div><dt class="text-med-muted">Labour Onset</dt><dd class="font-semibold text-med-ink">{{ $labour?->labour_onset_time?->format('M d, Y h:i A') ?? 'N/A' }}</dd></div><div><dt class="text-med-muted">Status</dt><dd><x-ui.badge :variant="$labour?->status === 'completed' ? 'success' : ($labour?->status === 'complicated' ? 'danger' : 'warning')">{{ str($labour?->status ?? 'ongoing')->headline() }}</x-ui.badge></dd></div></dl></x-ui.card>
        <x-ui.card title="Save Progress"><div class="flex flex-col gap-2"><x-ui.button type="submit">{{ $submitLabel ?? 'Save Progress' }}</x-ui.button><a href="{{ $cancelRoute ?? route('midwife.labour.progress.index', $labour) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Cancel</a></div></x-ui.card>
    </aside>
</div>
