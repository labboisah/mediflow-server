@extends('layouts.modern')

@section('title', 'Record Patient Visit - ' . ($patient->demographic?->full_name ?? 'Patient'))

@section('content')
<x-ui.page title="Record Patient Visit" subtitle="Create a new visit for {{ $patient->demographic?->full_name ?? 'this patient' }}.">
    <x-slot:actions>
        <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
            Back To Profile
        </a>
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
        <div class="space-y-6">
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
                    <div>
                        <p class="text-xs font-semibold uppercase text-med-muted">Current Status</p>
                        <x-ui.badge variant="{{ $patient->currentVisit() ? 'success' : 'neutral' }}">
                            {{ $patient->currentVisit()?->status ?? 'No active visit' }}
                        </x-ui.badge>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <form action="{{ route('record.visits.store', $patient) }}" method="POST" class="space-y-6">
            @csrf

            <x-ui.card title="Visit Information" subtitle="Select the care service that should open this visit and generate the related bill.">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Visit Date" name="visit_date" type="date" value="{{ old('visit_date', now()->format('Y-m-d')) }}" required />

                    <x-ui.select label="Visit Type" name="visit_type" required>
                        <option value="">Select visit type</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" @selected((string) old('visit_type') === (string) $service->id)>
                                {{ $service->name }}{{ $service->price !== null ? ' - NGN ' . number_format((float) $service->price, 2) : '' }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                @if($services->isEmpty())
                    <div class="mt-4 rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                        No active services are available. An administrator needs to activate at least one service before visits can be recorded.
                    </div>
                @endif
            </x-ui.card>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-4 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                    Cancel
                </a>
                <button type="submit" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark" @disabled($services->isEmpty())>
                    Record Visit
                </button>
            </div>
        </form>
    </div>
</x-ui.page>
@endsection
