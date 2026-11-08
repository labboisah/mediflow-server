@extends('layouts.modern')

@section('title', 'Edit Patient')

@section('content')
<x-ui.page title="Edit Patient Information" subtitle="Update details for {{ $patient->demographic?->full_name ?? 'this patient' }}.">
    <x-slot:actions>
        <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
            Cancel
        </a>
    </x-slot:actions>

    <form action="{{ route('record.patients.update', $patient) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <x-ui.card title="Patient Demographics" subtitle="Name, contact details, origin, and personal data.">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <x-ui.input label="First Name" name="first_name" value="{{ old('first_name', $patient->demographic?->first_name) }}" required />
                <x-ui.input label="Last Name" name="last_name" value="{{ old('last_name', $patient->demographic?->last_name) }}" required />

                <x-ui.select label="Gender" name="gender" required>
                    <option value="">Select gender</option>
                    @foreach(['Male', 'Female', 'Other'] as $gender)
                        <option value="{{ $gender }}" @selected(old('gender', $patient->demographic?->gender) === $gender)>{{ $gender }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.input label="Date Of Birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', $patient->demographic?->date_of_birth?->format('Y-m-d')) }}" required />

                <x-ui.select label="State Of Origin" name="state" id="state-select">
                    <option value="">Select state</option>
                    @foreach($states as $state)
                        <option value="{{ $state->id }}" @selected((string) $selectedStateId === (string) $state->id)>{{ $state->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select label="Local Government" name="lga" id="lga-select">
                    <option value="">Select LGA</option>
                    @foreach($lgas as $lga)
                        <option value="{{ $lga->id }}" @selected((string) old('lga', $patient->demographic?->lga_id) === (string) $lga->id)>{{ $lga->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.input label="Occupation" name="occupation" value="{{ old('occupation', $patient->demographic?->occupation) }}" />

                <x-ui.select label="Marital Status" name="marital_status">
                    <option value="">Select status</option>
                    @foreach(['Single', 'Married', 'Divorced', 'Widowed'] as $status)
                        <option value="{{ $status }}" @selected(old('marital_status', $patient->demographic?->marital_status) === $status)>{{ $status }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.input label="Phone Number" name="phone_number" type="tel" value="{{ old('phone_number', $patient->demographic?->phone_number) }}" required />
                <x-ui.input label="Email" name="email" type="email" value="{{ old('email', $patient->demographic?->email) }}" />

                <div class="md:col-span-2 xl:col-span-3">
                    <x-ui.textarea label="Address" name="address" rows="3">{{ old('address', $patient->demographic?->address) }}</x-ui.textarea>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Next Of Kin" subtitle="Emergency contact information.">
            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.input label="Name" name="nok_name" value="{{ old('nok_name', $patient->nextOfKin?->name) }}" required />
                <x-ui.input label="Relationship" name="nok_relationship" value="{{ old('nok_relationship', $patient->nextOfKin?->relationship) }}" required />
                <x-ui.input label="Telephone" name="nok_telephone" type="tel" value="{{ old('nok_telephone', $patient->nextOfKin?->telephone) }}" required />
                <x-ui.input label="Contact Address" name="nok_contact_address" value="{{ old('nok_contact_address', $patient->nextOfKin?->contact_address) }}" />
            </div>
        </x-ui.card>

        <div class="sticky bottom-0 z-10 flex flex-col gap-3 border-t border-med-line bg-med-bg/95 px-1 py-4 backdrop-blur sm:flex-row sm:justify-end">
            <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-4 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                Cancel
            </a>
            <button type="submit" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
                Save Changes
            </button>
        </div>
    </form>
</x-ui.page>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const stateSelect = document.getElementById('state-select');
        const lgaSelect = document.getElementById('lga-select');

        if (!stateSelect || !lgaSelect) {
            return;
        }

        stateSelect.addEventListener('change', async () => {
            lgaSelect.innerHTML = '<option value="">Loading LGAs...</option>';

            if (!stateSelect.value) {
                lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                return;
            }

            try {
                const response = await fetch(`/ajax/state/${stateSelect.value}/get-lgas`);
                const lgas = await response.json();
                const options = ['<option value="">Select LGA</option>'];

                Object.entries(lgas).forEach(([id, name]) => {
                    options.push(`<option value="${id}">${name}</option>`);
                });

                lgaSelect.innerHTML = options.join('');
            } catch (error) {
                lgaSelect.innerHTML = '<option value="">Unable to load LGAs</option>';
            }
        });
    });
</script>
@endpush
