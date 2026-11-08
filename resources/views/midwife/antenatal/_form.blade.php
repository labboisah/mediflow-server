@php
    $record = $antenatalCare ?? null;
    $fieldClass = 'mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm placeholder:text-med-muted/70';
    $labelClass = 'mb-1 block text-sm font-medium text-med-ink';
    $value = fn ($name, $default = '') => old($name, data_get($record, $name, $default));
    $dateValue = fn ($name) => old($name, data_get($record, $name)?->format('Y-m-d'));
@endphp

@if ($errors->any())
    <div class="rounded-md border border-med-danger/25 bg-red-50 px-4 py-3 text-sm text-med-danger">
        <p class="font-semibold">Please fix the highlighted errors.</p>
        <ul class="mt-2 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 xl:grid-cols-12">
    <div class="space-y-6 xl:col-span-8">
        <x-ui.card title="Pregnancy Details">
            @isset($visit)
                <input type="hidden" name="patient_visit_id" value="{{ $visit->id }}">
            @endisset
            <div class="grid gap-4 md:grid-cols-3">
                <label><span class="{{ $labelClass }}">Last Menstrual Period</span><input type="date" name="last_menstrual_period" value="{{ $dateValue('last_menstrual_period') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Expected Delivery Date</span><input type="date" name="expected_delivery_date" value="{{ $dateValue('expected_delivery_date') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Gestational Weeks</span><input type="number" name="gestational_weeks" min="1" max="42" value="{{ $value('gestational_weeks') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Number of Fetuses</span><input type="number" name="number_of_fetuses" min="1" max="8" value="{{ $value('number_of_fetuses', 1) }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Pregnancy Type</span><input type="text" name="pregnancy_type" value="{{ $value('pregnancy_type') }}" class="{{ $fieldClass }}" placeholder="Singleton, twins"></label>
                <label><span class="{{ $labelClass }}">Status</span><select name="status" class="{{ $fieldClass }}"><option value="">Optional</option>@foreach(['normal' => 'Normal', 'complicated' => 'Complicated', 'high_risk' => 'High Risk'] as $key => $label)<option value="{{ $key }}" @selected($value('status', 'normal') === $key)>{{ $label }}</option>@endforeach</select></label>
            </div>
        </x-ui.card>

        <x-ui.card title="Vital Signs">
            <div class="grid gap-4 md:grid-cols-3">
                <label><span class="{{ $labelClass }}">Blood Pressure</span><input type="text" name="blood_pressure" value="{{ $value('blood_pressure') }}" class="{{ $fieldClass }}" placeholder="120/80"></label>
                <label><span class="{{ $labelClass }}">Weight (kg)</span><input type="number" name="weight" min="30" max="250" step="0.1" value="{{ $value('weight') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Height (cm)</span><input type="number" name="height" min="100" max="250" step="0.1" value="{{ $value('height') }}" class="{{ $fieldClass }}"></label>
            </div>
        </x-ui.card>

        <x-ui.card title="Physical Examination">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="md:col-span-2"><span class="{{ $labelClass }}">Abdominal Examination</span><textarea name="abdominal_examination" rows="3" class="{{ $fieldClass }}">{{ $value('abdominal_examination') }}</textarea></label>
                <label><span class="{{ $labelClass }}">Fundal Height</span><input type="text" name="fundal_height" value="{{ $value('fundal_height') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Fetal Heart Rate</span><input type="text" name="fetal_heart_rate" value="{{ $value('fetal_heart_rate') }}" class="{{ $fieldClass }}"></label>
                <label><span class="{{ $labelClass }}">Fetal Movement</span><textarea name="fetal_movement" rows="3" class="{{ $fieldClass }}">{{ $value('fetal_movement') }}</textarea></label>
                <label><span class="{{ $labelClass }}">Vaginal Examination</span><textarea name="vaginal_examination" rows="3" class="{{ $fieldClass }}">{{ $value('vaginal_examination') }}</textarea></label>
            </div>
        </x-ui.card>

        <x-ui.card title="Investigations And Risk">
            <div class="grid gap-4 md:grid-cols-2">
                <label><span class="{{ $labelClass }}">Urine Analysis</span><textarea name="urine_analysis" rows="3" class="{{ $fieldClass }}">{{ $value('urine_analysis') }}</textarea></label>
                <label><span class="{{ $labelClass }}">Blood Tests</span><textarea name="blood_tests" rows="3" class="{{ $fieldClass }}">{{ $value('blood_tests') }}</textarea></label>
                <label><span class="{{ $labelClass }}">Ultrasound Findings</span><textarea name="ultrasound_findings" rows="3" class="{{ $fieldClass }}">{{ $value('ultrasound_findings') }}</textarea></label>
                <label><span class="{{ $labelClass }}">Risk Factors</span><textarea name="risk_factors" rows="3" class="{{ $fieldClass }}">{{ $value('risk_factors') }}</textarea></label>
                <label class="md:col-span-2"><span class="{{ $labelClass }}">Complications</span><textarea name="complications" rows="3" class="{{ $fieldClass }}">{{ $value('complications') }}</textarea></label>
            </div>
        </x-ui.card>

        <x-ui.card title="Management And Counseling">
            <div class="grid gap-4 md:grid-cols-2">
                <label><span class="{{ $labelClass }}">Management Plan</span><textarea name="management_plan" rows="4" class="{{ $fieldClass }}">{{ $value('management_plan') }}</textarea></label>
                <label><span class="{{ $labelClass }}">Counseling Topics</span><textarea name="counseling_topics" rows="4" class="{{ $fieldClass }}">{{ $value('counseling_topics') }}</textarea></label>
                <label class="flex items-center gap-2 text-sm font-medium text-med-ink"><input type="checkbox" name="took_supplements" value="1" class="rounded border-med-line text-med-primary focus:ring-med-primary" @checked((bool) $value('took_supplements'))> Took Supplements</label>
                <label class="md:col-span-2"><span class="{{ $labelClass }}">Clinical Notes</span><textarea name="clinical_notes" rows="4" class="{{ $fieldClass }}">{{ $value('clinical_notes') }}</textarea></label>
            </div>
        </x-ui.card>
    </div>

    <aside class="space-y-6 xl:col-span-4">
        <x-ui.card title="Patient Information">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-med-muted">Hospital Number</dt><dd class="font-semibold text-med-ink">{{ $patient->hospital_number }}</dd></div>
                <div><dt class="text-med-muted">Patient Name</dt><dd class="font-semibold text-med-ink">{{ $patient->demographic->first_name ?? 'N/A' }} {{ $patient->demographic->last_name ?? '' }}</dd></div>
                <div><dt class="text-med-muted">Age</dt><dd class="font-semibold text-med-ink">{{ $patient->age() }} years</dd></div>
                <div><dt class="text-med-muted">Gender</dt><dd class="font-semibold text-med-ink">{{ $patient->demographic->gender ?? 'N/A' }}</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Save Record">
            <div class="flex flex-col gap-2">
                <x-ui.button type="submit">{{ $submitLabel ?? 'Save Record' }}</x-ui.button>
                <a href="{{ $cancelRoute ?? route('midwife.antenatal.index') }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Cancel</a>
            </div>
        </x-ui.card>
    </aside>
</div>
