<div>
    <x-ui.page title="Observations" subtitle="{{ $patient->name() }} | {{ $patient->hospital_number }}">
        <x-slot:actions>
            <a href="{{ route('patient.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
        </x-slot:actions>

        @include('components.clinical._feedback')

        <div class="grid gap-5 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
            <x-ui.card :title="$editingId ? 'Update Observation' : 'Record Observation'" subtitle="Record nursing observations for the active visit.">
                <form wire:submit.prevent="save" class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach(['temperature' => 'Temperature', 'mate_pulse' => 'Maternal Pulse', 'blood_pressure_systolic' => 'BP Systolic', 'blood_pressure_diastolic' => 'BP Diastolic', 'respiratory_rate' => 'Respiration', 'drop_rate' => 'Drop Rate'] as $field => $label)
                            <div>
                                <x-ui.input :label="$label" wire:model="form.{{ $field }}" />
                                @error('form.' . $field)<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.input label="Contraction" wire:model="form.constraction" />
                        <x-ui.input label="Fits" wire:model="form.fits" />
                        <x-ui.input label="Date" type="date" wire:model="form.date" />
                        <x-ui.input label="Time" type="time" wire:model="form.time" />
                    </div>

                    <x-ui.textarea label="Remark" rows="4" wire:model="form.remark" />

                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button type="submit" variant="primary">{{ $editingId ? 'Update Observation' : 'Save Observation' }}</x-ui.button>
                        @if($editingId)
                            <x-ui.button type="button" variant="secondary" wire:click="cancelEdit">Cancel</x-ui.button>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card title="Recent Observations" subtitle="Latest observation notes for this active visit.">
                <x-ui.table>
                    <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                        <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Temp</th><th class="px-4 py-3">BP</th><th class="px-4 py-3">Remark</th><th class="px-4 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-med-line bg-white">
                        @forelse($recent as $item)
                            <tr class="hover:bg-med-canvas/50">
                                <td class="px-4 py-3 text-med-ink">{{ optional($item->date)->format('d M Y') ?? $item->date }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $item->time ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $item->temperature ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $item->blood_pressure ?? 'N/A' }}</td>
                                <td class="max-w-xs px-4 py-3 text-med-muted">{{ $item->remark ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-right"><button type="button" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas" wire:click="edit({{ $item->id }})">Edit</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8"><x-ui.empty-state title="No Observations" message="No observations have been recorded for this active visit." /></td></tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
            </x-ui.card>
        </div>
    </x-ui.page>
</div>
