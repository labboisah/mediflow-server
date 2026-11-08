<x-ui.table>
    <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted"><tr><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Investigation</th><th class="px-4 py-3">Diagnosis</th><th class="px-4 py-3">Specimen</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Requested By</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
    <tbody class="divide-y divide-med-line bg-white">
        @forelse($records as $record)
            @php($patient = $record->patientVisit?->patient)
            <tr class="hover:bg-med-canvas/50"><td class="px-4 py-3"><p class="font-semibold text-med-ink">{{ $patient?->name() ?? 'N/A' }}</p><p class="text-xs text-med-muted">{{ $patient?->hospital_number ?? 'N/A' }}</p></td><td class="px-4 py-3 text-med-muted">{{ $record->investigation?->name ?? 'N/A' }}</td><td class="max-w-xs px-4 py-3 text-med-muted">{{ str($record->clinical_diagnoses ?? 'N/A')->limit(80) }}</td><td class="px-4 py-3 text-med-muted">{{ $record->specimen ?? 'N/A' }}</td><td class="px-4 py-3"><x-ui.badge variant="warning">{{ ucfirst($record->status ?? 'Pending') }}</x-ui.badge></td><td class="px-4 py-3 text-med-muted">{{ $record->requestedBy?->name ?? 'N/A' }}</td><td class="px-4 py-3 text-right">@if($patient)
    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route($config['route'], ['patient' => $patient, 'request' => $record->id]) }}" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">Edit</a>
        <a href="{{ route('patient.show', $patient) }}" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">View Profile</a>
    </div>
@endif</td></tr>
        @empty
            <tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state title="No Investigation Requests" message="No investigation requests found." /></td></tr>
        @endforelse
    </tbody>
</x-ui.table>
