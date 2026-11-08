<x-ui.table>
    <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted"><tr><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Date / Time</th><th class="px-4 py-3">Input</th><th class="px-4 py-3">Output</th><th class="px-4 py-3">Balance</th><th class="px-4 py-3">Recorded By</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
    <tbody class="divide-y divide-med-line bg-white">
        @forelse($records as $record)
            @php
                $patient = $record->admission?->patientVisit?->patient;
                $totalIn = (float) ($record->oral ?? 0) + (float) ($record->iv ?? 0);
                $totalOut = (float) ($record->urine ?? 0) + (float) ($record->faces ?? 0);
            @endphp
            <tr class="hover:bg-med-canvas/50"><td class="px-4 py-3"><p class="font-semibold text-med-ink">{{ $patient?->name() ?? 'N/A' }}</p><p class="text-xs text-med-muted">{{ $patient?->hospital_number ?? 'N/A' }}</p></td><td class="px-4 py-3 text-med-muted">{{ $record->date }} {{ $record->time }}</td><td class="px-4 py-3 text-med-muted">Oral {{ $record->oral ?? 0 }} / IV {{ $record->iv ?? 0 }}</td><td class="px-4 py-3 text-med-muted">Urine {{ $record->urine ?? 0 }} / Faeces {{ $record->faces ?? 0 }}</td><td class="px-4 py-3 font-semibold text-med-ink">{{ number_format($totalIn - $totalOut, 2) }}</td><td class="px-4 py-3 text-med-muted">{{ $record->recordedBy?->name ?? 'N/A' }}</td><td class="px-4 py-3 text-right">@if($patient)
    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route($config['route'], $patient) }}" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">Edit</a>
        <a href="{{ route('patient.show', $patient) }}" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">View Profile</a>
    </div>
@endif</td></tr>
        @empty
            <tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state title="No Fluid Balance" message="No fluid balance entries found." /></td></tr>
        @endforelse
    </tbody>
</x-ui.table>

