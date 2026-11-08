@extends('layouts.modern')

@section('title', 'Record Discharge - ' . ($patient->demographic?->full_name ?? 'Patient'))

@section('content')
<x-ui.page title="Record Patient Discharge" subtitle="Close the active admission for {{ $patient->demographic?->full_name ?? 'this patient' }}.">
    <x-slot:actions>
        <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back To Profile</a>
    </x-slot:actions>

    <form action="{{ route('record.discharges.store', $patient) }}" method="POST" class="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
        @csrf

        <x-ui.card title="Admission Context">
            <div class="space-y-4">
                <div><p class="text-xs font-semibold uppercase text-med-muted">Patient</p><p class="mt-1 text-lg font-semibold text-med-ink">{{ $patient->demographic?->full_name ?? 'N/A' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-med-muted">Hospital Number</p><p class="mt-1 text-base font-semibold text-med-primary">{{ $patient->hospital_number }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-med-muted">Admission Status</p><x-ui.badge variant="warning">{{ $admission->status ?? 'Active' }}</x-ui.badge></div>
                <div><p class="text-xs font-semibold uppercase text-med-muted">Bed</p><p class="mt-1 text-base text-med-ink">{{ $admission->bed?->ward?->name ?? 'N/A' }} / {{ $admission->bed?->bed_no ?? 'N/A' }}</p></div>
            </div>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Discharge Details" subtitle="Record the discharge reason and optional follow-up date.">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Discharge Date" name="date" type="date" value="{{ old('date', now()->format('Y-m-d')) }}" required />
                    <x-ui.input label="Discharge Time" name="time" type="time" value="{{ old('time', now()->format('H:i')) }}" required />
                    <x-ui.input label="Next Appointment Date" name="next_appointment_date" type="date" value="{{ old('next_appointment_date') }}" />
                    <div class="md:col-span-2">
                        <x-ui.textarea label="Discharge Reason" name="reason" rows="5" required placeholder="Summary of treatment, status, and recommendations">{{ old('reason') }}</x-ui.textarea>
                    </div>
                </div>
            </x-ui.card>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-4 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Cancel</a>
                <button type="submit" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">Record Discharge</button>
            </div>
        </div>
    </form>
</x-ui.page>
@endsection
