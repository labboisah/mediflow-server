@extends('layouts.modern')

@section('title', 'Schedule Appointment - ' . ($patient->demographic?->full_name ?? 'Patient'))

@section('content')
<x-ui.page title="Schedule Appointment" subtitle="Create a future appointment for {{ $patient->demographic?->full_name ?? 'this patient' }}.">
    <x-slot:actions>
        <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
            Back To Profile
        </a>
    </x-slot:actions>

    <form action="{{ route('record.appointments.store', $patient) }}" method="POST" class="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
        @csrf

        <x-ui.card title="Patient Context">
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase text-med-muted">Patient</p>
                    <p class="mt-1 text-lg font-semibold text-med-ink">{{ $patient->demographic?->full_name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-med-muted">Hospital Number</p>
                    <p class="mt-1 text-base font-semibold text-med-primary">{{ $patient->hospital_number }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-med-muted">Phone</p>
                    <p class="mt-1 text-base text-med-ink">{{ $patient->demographic?->phone_number ?? 'N/A' }}</p>
                </div>
            </div>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Appointment Details" subtitle="Choose the appointment date, time, and any relevant note.">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Appointment Date" name="appointment_date" type="date" value="{{ old('appointment_date') }}" required />
                    <x-ui.input label="Appointment Time" name="appointment_time" type="time" value="{{ old('appointment_time') }}" required />
                    <div class="md:col-span-2">
                        <x-ui.textarea label="Notes" name="notes" rows="4" placeholder="Any additional notes about this appointment">{{ old('notes') }}</x-ui.textarea>
                    </div>
                </div>
            </x-ui.card>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-4 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                    Cancel
                </a>
                <button type="submit" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
                    Schedule Appointment
                </button>
            </div>
        </div>
    </form>
</x-ui.page>
@endsection
