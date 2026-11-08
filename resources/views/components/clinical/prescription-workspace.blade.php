<div>
    <x-ui.page title="Prescription" subtitle="{{ $patient->name() }} | {{ $patient->hospital_number }}">
        <x-slot:actions>
            <a href="{{ route('patient.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
        </x-slot:actions>

        @include('components.clinical._feedback')

        <div class="grid gap-5 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
            <x-ui.card title="Select Medicines" subtitle="Build the prescription for this active visit.">
                <div class="space-y-4">
                    <div>
                        <x-ui.textarea label="Treatment / Infection / Disease" rows="3" wire:model.live="treatmentDiagnosis" placeholder="Indicate diagnosis, infection, or disease being treated" />
                        @error('treatmentDiagnosis')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <x-ui.input label="Search Medicines" wire:model.live.debounce.300ms="medicineSearch" placeholder="Search medicine, generic name, or manufacturer" />
                        <div class="mt-3 max-h-72 overflow-y-auto rounded-md border border-med-line">
                            @forelse($medicines as $medicine)
                                @php($medicineKey = (string) $medicine->id)
                                <label class="flex cursor-pointer items-start gap-3 border-b border-med-line px-3 py-3 last:border-b-0 hover:bg-med-canvas">
                                    <input class="mf-focus mt-1 rounded border-med-line text-med-primary" type="checkbox" wire:click="toggleMedicine({{ $medicine->id }})" @checked(isset($selectedMedicines[$medicineKey]))>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-med-ink">{{ $medicine->name }}</span>
                                        <span class="mt-1 block text-xs text-med-muted">{{ $medicine->generic_name ?: 'No generic name' }} | {{ $medicine->manufacturer ?: 'No manufacturer' }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="px-3 py-4 text-sm text-med-muted">No matching medicine found. You can add a typed medicine below.</p>
                            @endforelse
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <x-ui.select wire:model="newMedicineTypeId">
                                <option value="">Medicine type for a new name</option>
                                @foreach($medicineTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.button type="button" variant="secondary" wire:click="addCustomMedicine">Add Typed Medicine</x-ui.button>
                        </div>
                        @error('medicineSearch')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                        @error('newMedicineTypeId')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-ui.card>

            @if($selectedMedicines)
                <x-ui.card title="Prescription Review" subtitle="Complete the route and instructions for each selected medicine.">
                    <div class="mb-4 text-xs font-semibold uppercase tracking-wide text-med-muted">{{ count($selectedMedicines) }} selected</div>
                    <div class="space-y-3">
                        @foreach($selectedMedicines as $key => $selectedMedicine)
                            <div class="rounded-md border border-med-line p-4" wire:key="selected-medicine-{{ $key }}">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-med-ink">{{ $selectedMedicine['name'] }}</h3>
                                    <button type="button" class="mf-focus rounded-md border border-red-200 px-2.5 py-1 text-xs font-semibold text-med-danger hover:bg-red-50" wire:click="removeSelectedMedicine('{{ $key }}')">Remove</button>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <x-ui.select label="Route" wire:model="selectedMedicines.{{ $key }}.routeId">
                                            <option value="">Select route</option>
                                            @foreach($routes as $route)
                                                <option value="{{ $route->id }}">{{ $route->name }}</option>
                                            @endforeach
                                        </x-ui.select>
                                        @error("selectedMedicines.{$key}.routeId")<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                                    </div>
                                    <div><x-ui.input label="Dosage" wire:model="selectedMedicines.{{ $key }}.dosage" />@error("selectedMedicines.{$key}.dosage")<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</div>
                                    <div><x-ui.input label="Period" wire:model="selectedMedicines.{{ $key }}.period" />@error("selectedMedicines.{$key}.period")<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</div>
                                    <div><x-ui.input label="Duration" wire:model="selectedMedicines.{{ $key }}.duration" />@error("selectedMedicines.{$key}.duration")<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        <x-ui.button type="button" class="w-full" wire:click="addSelectedMedicines">Add Reviewed Medicines to Prescription</x-ui.button>
                    </div>
                </x-ui.card>
            @endif

            <div class="xl:col-span-2">
                <x-ui.card title="Prescription Items" subtitle="Medicines, stock status, administration status, and total amount.">
                    <x-slot:actions>
                        <x-ui.button type="button" wire:click="submitPrescription">Submit to Pharmacy</x-ui.button>
                    </x-slot:actions>
                    <x-ui.table>
                        <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                            <tr><th class="px-4 py-3">Medicine</th><th class="px-4 py-3">Company</th><th class="px-4 py-3">Stock</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3">Route</th><th class="px-4 py-3">Dosage</th><th class="px-4 py-3">Period</th><th class="px-4 py-3">Duration</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
                        </thead>
                        <tbody class="divide-y divide-med-line bg-white">
                            @forelse($prescription?->prescriptionItems ?? [] as $item)
                                @php($quantity = $item->medicine?->availableQuantity() ?? 0)
                                <tr class="hover:bg-med-canvas/50" wire:key="prescription-item-{{ $item->id }}">
                                    <td class="px-4 py-3"><p class="font-semibold text-med-ink">{{ $item->medicine?->name ?? 'N/A' }}</p>@if($item->medicine?->generic_name)<p class="text-xs text-med-muted">{{ $item->medicine->generic_name }}</p>@endif</td>
                                    <td class="px-4 py-3 text-med-muted">{{ $item->medicine?->manufacturer ?? 'N/A' }}</td>
                                    <td class="px-4 py-3"><x-ui.badge :variant="$quantity > 0 ? 'success' : 'danger'">{{ $quantity > 0 ? 'Available: ' . $quantity : 'Not available' }}</x-ui.badge></td>
                                    <td class="px-4 py-3 text-right font-semibold text-med-ink">&#8358;{{ number_format($item->medicine?->latestSellingPrice() ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-med-muted">{{ $item->route?->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-med-muted">{{ $item->dosage }}</td>
                                    <td class="px-4 py-3 text-med-muted">{{ $item->period }}</td>
                                    <td class="px-4 py-3 text-med-muted">{{ $item->duration }}</td>
                                    <td class="px-4 py-3"><x-ui.badge :variant="$item->isStarted() ? 'success' : 'neutral'">{{ $item->isStarted() ? 'Started' : 'Stopped' }}</x-ui.badge>@if($item->medication_status_changed_at)<p class="mt-1 text-xs text-med-muted">{{ $item->medication_status_changed_at->format('M d, h:i A') }}</p>@endif</td>
                                    <td class="px-4 py-3 text-right">
                                        @if(auth()->user()->hasRole('doctor'))
                                            <div class="flex flex-wrap justify-end gap-1.5">
                                                <button type="button" class="mf-focus rounded-md border border-med-line px-2.5 py-1.5 text-xs font-semibold text-med-primary hover:bg-med-canvas" wire:click="editItem({{ $item->id }})">Edit</button>
                                                <button type="button" class="mf-focus rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-med-danger hover:bg-red-50" wire:click="removeItem({{ $item->id }})" wire:confirm="Delete this medicine from the prescription?">Delete</button>
                                                @if($item->isStarted())
                                                    <button type="button" class="mf-focus rounded-md border border-orange-200 px-2.5 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-50" wire:click="stopMedication({{ $item->id }})">Stop</button>
                                                @else
                                                    <button type="button" class="mf-focus rounded-md border border-green-200 px-2.5 py-1.5 text-xs font-semibold text-med-primary hover:bg-green-50" wire:click="startMedication({{ $item->id }})">Start</button>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-4 py-8"><x-ui.empty-state title="No Medicine Added" message="Add at least one medicine to prepare this prescription." /></td></tr>
                            @endforelse
                        </tbody>
                        @if($prescription?->prescriptionItems?->count())
                            <tfoot class="bg-med-canvas/60">
                                <tr><th colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Prescription Amount</th><th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">&#8358;{{ number_format($prescription->prescriptionItems->sum(fn($item) => $item->medicine?->latestSellingPrice() ?? 0), 2) }}</th><th colspan="6"></th></tr>
                            </tfoot>
                        @endif
                    </x-ui.table>
                </x-ui.card>
            </div>
        </div>
    </x-ui.page>
</div>