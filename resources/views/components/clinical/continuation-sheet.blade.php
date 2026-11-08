<div>
    <x-ui.page title="Continuation Sheet" subtitle="{{ $patient->name() }} | {{ $patient->hospital_number }}">
        <x-slot:actions>
            <a href="{{ route('patient.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
        </x-slot:actions>

        @include('components.clinical._feedback')

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(360px,0.9fr)]">
            <x-ui.card :title="$editingId ? 'Update Continuation Note' : 'Write Continuation Note'" subtitle="Document the active visit history, examination, diagnosis, and plan.">
                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <x-ui.textarea label="Clinical Note" rows="3" wire:model="notes" />
                        @error('notes')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 lg:grid-cols-2">
                        <div><x-ui.textarea label="Clinical History" rows="4" wire:model="history" />@error('history')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</div>
                        <div><x-ui.textarea label="Clinical Examination" rows="4" wire:model="examination" />@error('examination')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</div>
                        <div><x-ui.textarea label="Clinical Diagnosis" rows="4" wire:model="diagnose" />@error('diagnose')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</div>
                        <div><x-ui.textarea label="Clinical Plan" rows="4" wire:model="plan" />@error('plan')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button type="submit">{{ $editingId ? 'Update Note' : 'Save Note' }}</x-ui.button>
                        @if($editingId)
                            <x-ui.button type="button" variant="secondary" wire:click="cancelEdit">Cancel</x-ui.button>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <div class="space-y-5">
                @php $vitalSign = $patient->currentVisit()->vitalSigns()->latest()->first(); @endphp
                <x-ui.card title="Last Vital Signs" subtitle="Most recent vitals for the active visit.">
                    @if($vitalSign)
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div><p class="text-xs font-semibold uppercase text-med-muted">Recorded by</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ $vitalSign->recordedBy?->name ?? 'N/A' }}</p></div>
                            <div><p class="text-xs font-semibold uppercase text-med-muted">Date</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ optional($vitalSign->recorded_date)->format('d M Y, H:i') ?? 'N/A' }}</p></div>
                            <div><p class="text-xs font-semibold uppercase text-med-muted">Temperature</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ $vitalSign->body_temperature ?? 'N/A' }} °C</p></div>
                            <div><p class="text-xs font-semibold uppercase text-med-muted">Blood Pressure</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ $vitalSign->blood_pressure_systolic ?? 'N/A' }}/{{ $vitalSign->blood_pressure_diastolic ?? 'N/A' }}</p></div>
                            <div><p class="text-xs font-semibold uppercase text-med-muted">Heart Rate</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ $vitalSign->heart_rate ?? 'N/A' }} bpm</p></div>
                            <div><p class="text-xs font-semibold uppercase text-med-muted">Respiration</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ $vitalSign->respiratory_rate ?? 'N/A' }}</p></div>
                            <div><p class="text-xs font-semibold uppercase text-med-muted">Oxygen Saturation</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ $vitalSign->oxygen_saturation ?? 'N/A' }}</p></div>
                            <div><p class="text-xs font-semibold uppercase text-med-muted">Blood Glucose</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ $vitalSign->blood_glucose ?? 'N/A' }}</p></div>
                            <div><p class="text-xs font-semibold uppercase text-med-muted">Weight</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ $vitalSign->weight ?? 'N/A' }}</p></div>
                            <div><p class="text-xs font-semibold uppercase text-med-muted">Height</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ $vitalSign->height ?? 'N/A' }}</p></div>
                            <div class="sm:col-span-2"><p class="text-xs font-semibold uppercase text-med-muted">Remark</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ $vitalSign->notes ?: 'N/A' }}</p></div>
                            @if(auth()->user()->hasRole('nurse') || auth()->user()->hasRole('midwife'))
                                <div class="sm:col-span-2"><a href="{{ route('patient.vitalsign.create', $patient) }}" class="mf-focus inline-flex items-center rounded-md border border-med-line px-3 py-2 text-sm font-semibold text-med-primary hover:bg-med-canvas">Edit in Vital Signs</a></div>
                            @endif
                        </div>
                    @else
                        <x-ui.empty-state title="No Vital Signs" message="No vital signs have been recorded for this active visit." />
                    @endif
                </x-ui.card>

                <x-ui.card title="Recent Notes" subtitle="Latest continuation notes for this active visit.">
                    <div class="space-y-3">
                        @forelse($recent as $note)
                            <div class="rounded-md border border-med-line p-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-med-ink">{{ optional($note->created_at)->format('d M Y, H:i') }}</p>
                                    <button type="button" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas" wire:click="edit({{ $note->id }})">Edit</button>
                                </div>
                                <div class="space-y-2 text-sm text-med-muted">
                                    <p><span class="font-semibold text-med-ink">Note:</span> {{ $note->note ?: 'N/A' }}</p>
                                    <p><span class="font-semibold text-med-ink">History:</span> {{ $note->history ?: 'N/A' }}</p>
                                    <p><span class="font-semibold text-med-ink">Examination:</span> {{ $note->examination ?: 'N/A' }}</p>
                                    <p><span class="font-semibold text-med-ink">Diagnosis:</span> {{ $note->diagnose ?: 'N/A' }}</p>
                                    <p><span class="font-semibold text-med-ink">Plan:</span> {{ $note->plan ?: 'N/A' }}</p>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty-state title="No Continuation Notes" message="No continuation notes have been recorded for this active visit." />
                        @endforelse
                    </div>
                </x-ui.card>
            </div>
        </div>
    </x-ui.page>
</div>