<div>
    <x-ui.page title="Admission" subtitle="{{ $patient->name() }} | {{ $patient->hospital_number }}">
        <x-slot:actions>
            <a href="{{ route('patient.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
        </x-slot:actions>

        @include('components.clinical._feedback')

        <div class="grid gap-5 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
            <x-ui.card :title="$editingId ? 'Update Admission' : 'Register Admission'" subtitle="Assign ward, bed, and expected bed-space charge for the active visit.">
                <form wire:submit.prevent="save" class="space-y-5">
                    <div>
                        <x-ui.select label="Ward" wire:model.live="wardId">
                            <option value="">Select ward</option>
                            @foreach($wards as $ward)
                                <option value="{{ $ward->id }}">{{ $ward->name }} - &#8358;{{ number_format((float) $ward->price, 2) }}/day</option>
                            @endforeach
                        </x-ui.select>
                        @error('wardId')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <x-ui.select label="Bed" wire:model="bedId">
                            <option value="">Select bed</option>
                            @foreach($beds as $bed)
                                <option value="{{ $bed->id }}">{{ $bed->bed_no }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('bedId')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-ui.input label="Days" type="number" min="1" wire:model.live="days" />
                        <x-ui.input label="Date" type="date" wire:model="date" />
                        <x-ui.input label="Time" type="time" wire:model="time" />
                    </div>

                    <x-ui.textarea label="Note" rows="4" wire:model="note" />

                    <div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Estimated ward/bed charge: <span class="font-semibold">&#8358;{{ number_format($estimatedAmount, 2) }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button type="submit" variant="primary">{{ $editingId ? 'Update Admission' : 'Register Admission' }}</x-ui.button>
                        @if($editingId)
                            <x-ui.button type="button" variant="secondary" wire:click="cancelEdit">Cancel</x-ui.button>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card title="Ward/Bed Charges This Visit" subtitle="Admissions and generated bed-space charges for the active visit.">
                <x-ui.table>
                    <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                        <tr><th class="px-4 py-3">Ward</th><th class="px-4 py-3">Bed</th><th class="px-4 py-3">Days</th><th class="px-4 py-3">Rate/Day</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Charge</th><th class="px-4 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-med-line bg-white">
                        @forelse($admissions as $admission)
                            @php
                                $bedBill = $admission->bills->firstWhere('service_description', 'Bed Space Charges') ?? $admission->bills->first();
                                $daysBilled = (int) ($bedBill?->billServices?->sum('quantity') ?: 1);
                                $dailyRate = (float) ($admission->bed?->ward?->price ?? $bedBill?->billServices?->first()?->unit_price ?? 0);
                                $bedCharge = (float) ($bedBill?->due_amount ?? ($dailyRate * $daysBilled));
                            @endphp
                            <tr class="hover:bg-med-canvas/50">
                                <td class="px-4 py-3 text-med-ink">{{ $admission->bed?->ward?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $admission->bed?->bed_no ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $daysBilled }}</td>
                                <td class="px-4 py-3 text-med-muted">&#8358;{{ number_format($dailyRate, 2) }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $admission->date }}</td>
                                <td class="px-4 py-3"><x-ui.badge :variant="$admission->status === 'discharged' ? 'neutral' : 'success'">{{ ucfirst($admission->status ?? 'active') }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-right font-semibold text-med-ink">&#8358;{{ number_format($bedCharge, 2) }}</td>
                                <td class="px-4 py-3 text-right"><button type="button" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas" wire:click="edit({{ $admission->id }})">Edit</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8"><x-ui.empty-state title="No Admissions" message="No admissions have been registered for this active visit." /></td></tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
            </x-ui.card>
        </div>
    </x-ui.page>
</div>
