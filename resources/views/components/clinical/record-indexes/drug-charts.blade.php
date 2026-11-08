<x-ui.table>
    <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted"><tr><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Medicine</th><th class="px-4 py-3">Dosage Given</th><th class="px-4 py-3">Comment</th><th class="px-4 py-3">Recorded By</th><th class="px-4 py-3">Time</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
    <tbody class="divide-y divide-med-line bg-white">
        @forelse($records as $record)
            @php($patient = $record->prescriptionItem?->prescription?->patientVisit?->patient)
            <tr class="hover:bg-med-canvas/50"><td class="px-4 py-3"><p class="font-semibold text-med-ink">{{ $patient?->name() ?? 'N/A' }}</p><p class="text-xs text-med-muted">{{ $patient?->hospital_number ?? 'N/A' }}</p></td><td class="px-4 py-3 text-med-muted">{{ $record->medicine?->name ?? 'N/A' }}</td><td class="px-4 py-3 text-med-muted">{{ $record->dosage ?? 'N/A' }}</td><td class="max-w-xs px-4 py-3 text-med-muted">{{ str($record->comment ?? 'N/A')->limit(80) }}</td><td class="px-4 py-3 text-med-muted">{{ $record->dispensedBy?->name ?? 'N/A' }}</td><td class="px-4 py-3 text-med-muted">{{ optional($record->created_at)->format('M d, Y') }} {{ $record->time }}</td><td class="px-4 py-3 text-right">@if($patient)
    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route($config['route'], $patient) }}" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">Edit</a>
        <a href="{{ route('patient.show', $patient) }}" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">View Profile</a>
    </div>
@endif</td></tr>
        @empty
            <tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state title="No Drug Chart Entries" message="No drug chart entries found." /></td></tr>
        @endforelse
    </tbody>
</x-ui.table>
