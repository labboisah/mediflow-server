@extends('layouts.modern')

@section('title', 'Record Referral - ' . ($patient->demographic?->full_name ?? 'Patient'))

@section('content')
<x-ui.page title="Record Patient Referral" subtitle="Create a referral record for {{ $patient->demographic?->full_name ?? 'this patient' }}.">
    <x-slot:actions>
        <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
            Back To Profile
        </a>
    </x-slot:actions>

    <form action="{{ route('record.referrals.store', $patient) }}" method="POST" class="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
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
            <x-ui.card title="Referral Details" subtitle="Capture where the patient is being referred and why.">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Referral Date" name="referral_date" type="date" value="{{ old('referral_date', now()->format('Y-m-d')) }}" required />

                    <x-ui.select label="Referred To" name="referred_to_department" required>
                        <option value="">Select department or facility</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->name }}" @selected(old('referred_to_department') === $department->name)>{{ $department->name }}</option>
                        @endforeach
                        <option value="Other Hospital" @selected(old('referred_to_department') === 'Other Hospital')>Other Hospital</option>
                    </x-ui.select>

                    <div class="md:col-span-2">
                        <x-ui.textarea label="Reason For Referral" name="reason_for_referral" rows="4" required placeholder="Why is the patient being referred?">{{ old('reason_for_referral') }}</x-ui.textarea>
                    </div>

                    <div class="md:col-span-2">
                        <x-ui.textarea label="Clinical Notes" name="notes" rows="4" placeholder="Brief clinical or administrative notes for the referral">{{ old('notes') }}</x-ui.textarea>
                    </div>
                </div>
            </x-ui.card>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-4 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                    Cancel
                </a>
                <button type="submit" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
                    Record Referral
                </button>
            </div>
        </div>
    </form>
</x-ui.page>
@endsection
