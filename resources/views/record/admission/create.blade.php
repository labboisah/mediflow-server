@extends('layouts.modern')

@section('title', 'Record Admission - ' . ($patient->demographic?->full_name ?? 'Patient'))

@section('content')
<x-ui.page title="Record Patient Admission" subtitle="Assign an available bed and open an admission for {{ $patient->demographic?->full_name ?? 'this patient' }}.">
    <x-slot:actions>
        <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back To Profile</a>
    </x-slot:actions>

    <form action="{{ route('record.admissions.store', $patient) }}" method="POST" class="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
        @csrf

        <x-ui.card title="Patient Context">
            <div class="space-y-4">
                <div><p class="text-xs font-semibold uppercase text-med-muted">Patient</p><p class="mt-1 text-lg font-semibold text-med-ink">{{ $patient->demographic?->full_name ?? 'N/A' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-med-muted">Hospital Number</p><p class="mt-1 text-base font-semibold text-med-primary">{{ $patient->hospital_number }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-med-muted">Current Visit</p><x-ui.badge variant="{{ $patient->currentVisit() ? 'success' : 'neutral' }}">{{ $patient->currentVisit()?->status ?? 'No active visit' }}</x-ui.badge></div>
            </div>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Admission Details" subtitle="Only vacant beds are listed for admission.">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Admission Date" name="date" type="date" value="{{ old('date', now()->format('Y-m-d')) }}" required />
                    <x-ui.input label="Admission Time" name="time" type="time" value="{{ old('time', now()->format('H:i')) }}" required />
                    <x-ui.input label="Admission Days" name="days" type="number" min="1" value="{{ old('days', 1) }}" required />
                    <x-ui.select label="Bed Assignment" name="bed_id" required>
                        <option value="">Select vacant bed</option>
                        @foreach($wards as $ward)
                            @if($ward->beds->isNotEmpty())
                                <optgroup label="{{ $ward->name }}">
                                    @foreach($ward->beds as $bed)
                                        <option value="{{ $bed->id }}" @selected((string) old('bed_id') === (string) $bed->id)>{{ $bed->bed_no }}{{ $ward->price ? ' - NGN ' . number_format((float) $ward->price, 2) . '/day' : '' }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </x-ui.select>
                    <div class="md:col-span-2">
                        <x-ui.textarea label="Admission Note" name="note" rows="4" placeholder="Reason for admission or additional notes">{{ old('note') }}</x-ui.textarea>
                    </div>
                </div>

                @if($wards->flatMap->beds->isEmpty())
                    <div class="mt-4 rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">No vacant beds are available at the moment.</div>
                @endif
            </x-ui.card>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-4 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Cancel</a>
                <button type="submit" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark" @disabled($wards->flatMap->beds->isEmpty())>Record Admission</button>
            </div>
        </div>
    </form>
</x-ui.page>
@endsection
