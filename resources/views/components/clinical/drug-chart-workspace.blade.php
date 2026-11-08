<div>
    <x-ui.page title="Drug Chart" subtitle="{{ $patient->name() }} | {{ $patient->hospital_number }}">
        <x-slot:actions>
            <a href="{{ route('patient.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
        </x-slot:actions>

        @include('components.clinical._feedback')

        <div class="grid gap-5 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
            <x-ui.card title="Search Prescribed Medicines" subtitle="Select started medicines for this active visit.">
                <div>
                    <x-ui.input label="Search medicine" wire:model.live.debounce.300ms="prescriptionSearch" placeholder="Search medicine, generic name, or manufacturer" />
                    <div class="mt-3 max-h-72 overflow-y-auto rounded-md border border-med-line">
                        @forelse($items as $item)
                            @php($itemKey = (string) $item->id)
                            <label class="flex cursor-pointer items-start gap-3 border-b border-med-line px-3 py-3 last:border-b-0 hover:bg-med-canvas">
                                <input class="mf-focus mt-1 rounded border-med-line text-med-primary" type="checkbox" wire:click="toggleItem({{ $item->id }})" @checked(isset($selectedItems[$itemKey]))>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-med-ink">{{ $item->medicine?->name ?? 'Unknown medicine' }}</span>
                                    <span class="mt-1 block text-xs text-med-muted">Prescribed: {{ $item->dosage }} | {{ $item->route?->name ?? 'Route not set' }}</span>
                                </span>
                            </label>
                        @empty
                            <p class="px-3 py-4 text-sm text-med-muted">No active prescribed medicines found.</p>
                        @endforelse
                    </div>
                    @error('prescriptionItemId')<p class="mt-2 text-sm text-med-danger">{{ $message }}</p>@enderror
                </div>
            </x-ui.card>

            <x-ui.card title="Drug Chart Review" subtitle="Enter the administration details for each selected medicine.">
                <div class="mb-4 grid gap-4 sm:grid-cols-2">
                    <div><x-ui.input label="Date" type="date" wire:model="date" />@error('date')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</div>
                    <div><x-ui.input label="Time" type="time" wire:model="time" />@error('time')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</div>
                </div>

                <div class="space-y-3">
                    @forelse($selectedItems as $key => $selectedItem)
                        <div class="rounded-md border border-med-line p-4" wire:key="drug-review-{{ $key }}">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-med-ink">{{ $selectedItem['medicine'] }}</h3>
                                <button type="button" class="mf-focus rounded-md border border-red-200 px-2.5 py-1 text-xs font-semibold text-med-danger hover:bg-red-50" wire:click="removeSelectedItem('{{ $key }}')">Remove</button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <x-ui.input label="Dosage Given" wire:model="selectedItems.{{ $key }}.dosage" />
                                    @error("selectedItems.{$key}.dosage")<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <x-ui.input label="Comment" wire:model="selectedItems.{{ $key }}.comment" />
                                    @error("selectedItems.{$key}.comment")<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-med-line px-4 py-8 text-center text-sm text-med-muted">Select one or more prescribed medicines to prepare drug chart entries.</div>
                    @endforelse
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.button type="button" wire:click="save" :disabled="empty($selectedItems)">Save Drug Chart Entries</x-ui.button>
                    @if($editingId)
                        <x-ui.button type="button" variant="secondary" wire:click="cancelEdit">Cancel Edit</x-ui.button>
                    @endif
                </div>
            </x-ui.card>

            <div class="xl:col-span-2">
                <x-ui.card title="Recent Drug Chart" subtitle="Latest medication administration records for this visit.">
                    <x-ui.table>
                        <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                            <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Medicine</th><th class="px-4 py-3">Dosage</th><th class="px-4 py-3">Comment</th><th class="px-4 py-3 text-right">Action</th></tr>
                        </thead>
                        <tbody class="divide-y divide-med-line bg-white">
                            @forelse($recent as $chart)
                                <tr class="hover:bg-med-canvas/50">
                                    <td class="px-4 py-3">{{ $chart->date ? date('Y-m-d', strtotime($chart->date)) : 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $chart->time ? date('h:i A', strtotime($chart->time)) : 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $chart->medicine?->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $chart->dosage ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $chart->comment ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-right"><button type="button" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary hover:bg-med-canvas" wire:click="edit({{ $chart->id }})">Edit</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8"><x-ui.empty-state title="No Drug Chart" message="No drug chart entries have been recorded for this active visit." /></td></tr>
                            @endforelse
                        </tbody>
                    </x-ui.table>
                </x-ui.card>
            </div>
        </div>
    </x-ui.page>
</div>