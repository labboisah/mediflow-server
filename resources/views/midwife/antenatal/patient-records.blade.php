@extends('layouts.modern')

@section('title', 'Antenatal Care Records')

@section('content')
    <x-ui.page title="Antenatal Care Records" subtitle="{{ $patient->demographic->first_name }} {{ $patient->demographic->last_name }} ({{ $patient->age() }} years)">
        <x-slot name="actions">
            @if(auth()->user()->hasAnyRole(['midwife', 'administrator']))
                <a href="{{ route('midwife.antenatal.create', $patient) }}" class="inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">New Record</a>
                <a href="{{ route('midwife.antenatal.index') }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a>
            @endif
        </x-slot>

        <x-ui.card title="Patient Summary">
            <dl class="grid gap-4 md:grid-cols-4">
                <div><dt class="text-sm text-med-muted">Hospital Number</dt><dd class="font-semibold text-med-ink">{{ $patient->hospital_number }}</dd></div>
                <div><dt class="text-sm text-med-muted">Age</dt><dd class="font-semibold text-med-ink">{{ $patient->age() }} years</dd></div>
                <div><dt class="text-sm text-med-muted">Gender</dt><dd class="font-semibold text-med-ink">{{ $patient->demographic->gender }}</dd></div>
                <div><dt class="text-sm text-med-muted">Contact</dt><dd class="font-semibold text-med-ink">{{ $patient->demographic->phone_number ?? $patient->demographic->phone ?? 'N/A' }}</dd></div>
            </dl>
        </x-ui.card>

        @if($antenatalRecords->count() > 0)
            <x-ui.table>
                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Gestation</th><th class="px-4 py-3">BP</th><th class="px-4 py-3">Weight</th><th class="px-4 py-3">FHR</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Recorded By</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-med-line bg-white">
                    @foreach($antenatalRecords as $record)
                        <tr>
                            <td class="px-4 py-4 text-med-muted">{{ $record->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $record->gestational_weeks ? $record->gestational_weeks . ' weeks' : '-' }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $record->blood_pressure ?? '-' }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $record->weight ? $record->weight . ' kg' : '-' }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $record->fetal_heart_rate ?? '-' }}</td>
                            <td class="px-4 py-4"><x-ui.badge :variant="$record->status === 'normal' ? 'success' : ($record->status === 'high_risk' ? 'danger' : 'warning')">{{ str($record->status)->headline() }}</x-ui.badge></td>
                            <td class="px-4 py-4 text-med-muted">{{ $record->recordedBy->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-4"><div class="flex flex-wrap justify-end gap-2"><a href="{{ route('midwife.antenatal.show', $record) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">View</a>@if(auth()->user()->hasAnyRole(['midwife', 'administrator']))<a href="{{ route('midwife.antenatal.edit', $record) }}" class="text-sm font-semibold text-med-info hover:text-blue-700">Edit</a><form action="{{ route('midwife.antenatal.destroy', $record) }}" method="POST" onsubmit="return confirm('Are you sure?')">@csrf @method('DELETE')<button type="submit" class="text-sm font-semibold text-med-danger hover:text-red-800">Delete</button></form>@endif</div></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @else
            <x-ui.empty-state title="No Antenatal Records" message="No antenatal care records found for this patient.">
                @if(auth()->user()->hasAnyRole(['midwife', 'administrator']))
                    <a href="{{ route('midwife.antenatal.create', $patient) }}" class="inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">Create First Record</a>
                @endif
            </x-ui.empty-state>
        @endif
    </x-ui.page>
@endsection
