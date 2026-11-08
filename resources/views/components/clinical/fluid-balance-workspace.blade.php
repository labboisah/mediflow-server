<div>
    <x-ui.page title="Fluid Balance" subtitle="{{ $patient->name() }} | {{ $patient->hospital_number }}">
        <x-slot:actions>
            <a href="{{ route('patient.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
        </x-slot:actions>

        @include('components.clinical._feedback')

        @unless($admission)
            <div class="mb-5 rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-medium text-orange-800">
                A confirmed admission is required before recording fluid balance.
            </div>
        @endunless

        <div class="grid gap-5 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
            <x-ui.card :title="$editingId ? 'Update Fluid Balance' : 'Record Fluid Balance'" subtitle="Capture fluid intake and output for the confirmed admission.">
                <form wire:submit.prevent="save" class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.input label="Date" type="date" wire:model="form.date" />
                        <x-ui.input label="Time" type="time" wire:model="form.time" />
                        @foreach(['type_in' => 'Type In', 'tube_in' => 'Tube In', 'oral' => 'Oral', 'iv' => 'IV', 'type_out' => 'Type Out', 'tube_out' => 'Tube Out', 'urine' => 'Urine', 'faces' => 'Faeces'] as $field => $label)
                            <div>
                                <x-ui.input :label="$label" wire:model="form.{{ $field }}" />
                                @error('form.' . $field)<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button type="submit" variant="primary" :disabled="! $admission">{{ $editingId ? 'Update Fluid Balance' : 'Save Fluid Balance' }}</x-ui.button>
                        @if($editingId)
                            <x-ui.button type="button" variant="secondary" wire:click="cancelEdit">Cancel</x-ui.button>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card title="Recent Fluid Balance" subtitle="Latest intake and output entries for this admission.">
                <x-ui.table>
                    <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                        <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Oral</th><th class="px-4 py-3">IV</th><th class="px-4 py-3">Urine</th><th class="px-4 py-3">Faeces</th><th class="px-4 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-med-line bg-white">
                        @forelse($recent as $item)
                            <tr class="hover:bg-med-canvas/50">
                                <td class="px-4 py-3 text-med-ink">{{ optional($item->date)->format('d M Y') ?? $item->date }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $item->time ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $item->oral ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $item->iv ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $item->urine ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $item->faces ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-right"><button type="button" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas" wire:click="edit({{ $item->id }})">Edit</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state title="No Fluid Balance" message="No fluid balance entries have been recorded for this admission." /></td></tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
            </x-ui.card>
        </div>
    </x-ui.page>
</div>

