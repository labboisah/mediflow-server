<div>
    <x-ui.page title="Investigation Request" subtitle="{{ $patient->name() }} | {{ $patient->hospital_number }}">
        <x-slot:actions>
            <a href="{{ route('patient.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
        </x-slot:actions>

        @include('components.clinical._feedback')

        <form wire:submit.prevent="save" class="grid gap-5 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <x-ui.card title="Request Details" subtitle="Describe the diagnosis and select investigations.">
                <div class="space-y-4">
                    <div>
                        <x-ui.textarea label="Clinical Diagnosis" rows="3" wire:model="clinicalDiagnoses" />
                        @error('clinicalDiagnoses')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <x-ui.input label="Discount (%)" type="number" min="0" max="100" wire:model="discount" />
                        @error('discount')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <x-ui.input label="Search Investigations" wire:model.live.debounce.300ms="investigationSearch" placeholder="Search by investigation name or code" :disabled="$editingRequestId" />
                        <div class="mt-3 max-h-80 overflow-y-auto rounded-md border border-med-line">
                            @forelse($investigations as $investigation)
                                @php($investigationSelected = collect($rows)->contains(fn ($row) => (int) ($row['investigation_id'] ?? 0) === $investigation->id))
                                <label class="flex cursor-pointer items-start gap-3 border-b border-med-line px-3 py-3 last:border-b-0 hover:bg-med-canvas">
                                    <input class="mf-focus mt-1 rounded border-med-line text-med-primary" type="checkbox" wire:click="toggleInvestigation({{ $investigation->id }})" @checked($investigationSelected) @disabled($editingRequestId)>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-med-ink">{{ $investigation->name }}</span>
                                        <span class="mt-1 block text-xs text-med-muted">{{ $investigation->code ?: 'No code' }} | {{ $investigation->investigationType?->name ?? 'No type' }} | &#8358;{{ number_format((float) $investigation->price, 2) }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="px-3 py-4 text-sm text-med-muted">No matching investigations found.</p>
                            @endforelse
                        </div>
                        @error('investigation_rows')<p class="mt-2 text-sm text-med-danger">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Investigation Review" subtitle="Review selected tests and add specimen details.">
                <div class="space-y-3">
                    @forelse($rows as $index => $row)
                        <div class="rounded-md border border-med-line p-4" wire:key="investigation-row-{{ $index }}">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-med-ink">Investigation {{ $index + 1 }}</h3>
                                <button type="button" class="mf-focus rounded-md border border-red-200 px-2.5 py-1 text-xs font-semibold text-med-danger hover:bg-red-50" wire:click="removeRow({{ $index }})" @disabled($editingRequestId)>Remove</button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <x-ui.select label="Type" wire:model.live="rows.{{ $index }}.type_id">
                                        <option value="">Select type</option>
                                        @foreach($types as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </div>
                                <div>
                                    <x-ui.select label="Investigation" wire:model="rows.{{ $index }}.investigation_id">
                                        <option value="">Select investigation</option>
                                        @foreach($investigations->where('investigation_type_id', $row['type_id']) as $investigation)
                                            <option value="{{ $investigation->id }}">{{ $investigation->name }} — &#8358;{{ number_format((float) $investigation->price, 2) }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </div>
                                <div class="sm:col-span-2"><x-ui.input label="Specimen" wire:model="rows.{{ $index }}.specimen" /></div>
                            </div>
                            @error("rows.{$index}.investigation_id")<p class="mt-2 text-sm text-med-danger">{{ $message }}</p>@enderror
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-med-line px-4 py-8 text-center text-sm text-med-muted">Search and select investigations to begin this request.</div>
                    @endforelse
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.button type="button" variant="secondary" wire:click="addRow" :disabled="$editingRequestId">Add Investigation</x-ui.button>
                    <x-ui.button type="submit">{{ $editingRequestId ? 'Update Request & Bill' : 'Register Request & Bill' }}</x-ui.button>
                    @if($editingRequestId)
                        <x-ui.button type="button" variant="secondary" wire:click="cancelEdit">Cancel</x-ui.button>
                    @endif
                </div>
            </x-ui.card>
        </form>

        <div class="mt-5">
            <x-ui.card title="Recent Requests" subtitle="Latest investigation requests for this visit.">
                <div class="divide-y divide-med-line">
                    @forelse($recentRequests as $request)
                        <div class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-semibold text-med-ink">{{ $request->investigation?->name ?? 'Investigation' }}</p>
                                <p class="mt-1 text-xs text-med-muted">{{ $request->lab_no ?? 'Pending lab number' }} | Bill: {{ $request->bill?->bill_number ?? 'N/A' }} | {{ $request->bill?->status ?? 'pending' }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <strong class="text-sm text-med-ink">&#8358;{{ number_format((float) ($request->bill?->due_amount ?? $request->investigation?->price ?? 0), 2) }}</strong>
                                <button type="button" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary hover:bg-med-canvas" wire:click="editRequest({{ $request->id }})">Edit</button>
                                <button type="button" class="mf-focus rounded-md border border-red-200 px-3 py-1.5 text-sm font-semibold text-med-danger hover:bg-red-50" wire:click="deleteRequest({{ $request->id }})" wire:confirm="Remove this investigation request and update the bill?">Remove</button>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state title="No Recent Requests" message="No investigation requests have been recorded for this visit." />
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </x-ui.page>
</div>