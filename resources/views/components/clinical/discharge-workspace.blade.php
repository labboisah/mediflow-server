<div>
    <x-ui.page title="Discharge" subtitle="{{ $admission->patientVisit->patient->name() }} | {{ $admission->patientVisit->patient->hospital_number }}">
        <x-slot:actions>
            <a href="{{ route('patient.show', $admission->patientVisit->patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
        </x-slot:actions>

        @include('components.clinical._feedback')

        <x-ui.card :title="$admission->discharge ? 'Update Discharge Summary' : 'Discharge Patient'" subtitle="Close the admission, release the bed, and update the patient visit status.">
            <form wire:submit.prevent="save" class="space-y-5">
                <div class="grid gap-4 md:grid-cols-3">
                    <x-ui.input label="Date" type="date" wire:model="date" />
                    <x-ui.input label="Time" type="time" wire:model="time" />
                    <x-ui.input label="Next Appointment" type="date" wire:model="nextAppointmentDate" />
                </div>
                <div>
                    <x-ui.textarea label="Reason / Summary" rows="7" wire:model="reason" />
                    @error('reason')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                </div>
                <x-ui.button type="submit" variant="primary">{{ $admission->discharge ? 'Update Discharge Summary' : 'Discharge Patient' }}</x-ui.button>
            </form>
        </x-ui.card>
    </x-ui.page>
</div>
