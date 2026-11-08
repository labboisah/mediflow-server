<div>
    <x-ui.page title="Vital Signs" subtitle="{{ $patient->name() }} | {{ $patient->hospital_number }}">
        <x-slot:actions>
            <a href="{{ route('patient.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
        </x-slot:actions>

        @include('components.clinical._feedback')

        <div class="grid gap-5 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <x-ui.card :title="$editingId ? 'Update Vital Signs' : 'Record Vital Signs'" subtitle="Capture the patient vitals for the active visit.">
                <form wire:submit.prevent="save" class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-2 2xl:grid-cols-3">
                        @foreach(['body_temperature' => 'Temp', 'blood_pressure_systolic' => 'BP Systolic', 'blood_pressure_diastolic' => 'BP Diastolic', 'heart_rate' => 'Heart Rate', 'respiratory_rate' => 'Resp. Rate', 'oxygen_saturation' => 'Oxygen %', 'blood_glucose' => 'Glucose', 'weight' => 'Weight', 'height' => 'Height'] as $field => $label)
                            <div>
                                <x-ui.input :label="$label" type="number" step="0.01" wire:model="form.{{ $field }}" />
                                @error('form.' . $field)<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input label="Recorded Date" type="datetime-local" wire:model="form.recorded_date" />
                        <x-ui.textarea label="Notes" rows="3" wire:model="form.notes" />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button type="submit" variant="primary">{{ $editingId ? 'Update Vital Signs' : 'Save Vital Signs' }}</x-ui.button>
                        @if($editingId)
                            <x-ui.button type="button" variant="secondary" wire:click="cancelEdit">Cancel</x-ui.button>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card title="Recent Vital Signs" subtitle="Latest entries for this active visit.">
                <x-ui.table>
                    <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                        <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Temp</th><th class="px-4 py-3">BP</th><th class="px-4 py-3">HR</th><th class="px-4 py-3">SpO2</th><th class="px-4 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-med-line bg-white">
                        @forelse($recent as $vital)
                            <tr class="hover:bg-med-canvas/50">
                                <td class="px-4 py-3 text-med-ink">{{ optional($vital->recorded_date)->format('d M Y, H:i') ?? $vital->recorded_date }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $vital->body_temperature ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $vital->blood_pressure_systolic && $vital->blood_pressure_diastolic ? $vital->blood_pressure_systolic . '/' . $vital->blood_pressure_diastolic : 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $vital->heart_rate ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $vital->oxygen_saturation ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-right"><button type="button" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas" wire:click="edit({{ $vital->id }})">Edit</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8"><x-ui.empty-state title="No Vital Signs" message="No vital signs have been recorded for this active visit." /></td></tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
            </x-ui.card>
        </div>
    </x-ui.page>
</div>
