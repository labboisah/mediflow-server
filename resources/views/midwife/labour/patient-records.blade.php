@extends('layouts.modern')

@section('title', 'Labour Records')

@section('content')
    <x-ui.page title="Labour Records" subtitle="Complete labour history for {{ $patient->name() }}.">
        <x-slot name="actions"><a href="{{ route('midwife.labour.create', $patient) }}" class="inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">New Record</a><a href="{{ route('midwife.labour.index') }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a></x-slot>
        <x-ui.card title="Patient Information"><dl class="grid gap-4 md:grid-cols-4"><div><dt class="text-sm text-med-muted">Hospital Number</dt><dd class="font-semibold text-med-ink">{{ $patient->hospital_number }}</dd></div><div><dt class="text-sm text-med-muted">Patient Name</dt><dd class="font-semibold text-med-ink">{{ $patient->name() }}</dd></div><div><dt class="text-sm text-med-muted">Age</dt><dd class="font-semibold text-med-ink">{{ $patient->age() }} years</dd></div><div><dt class="text-sm text-med-muted">Phone</dt><dd class="font-semibold text-med-ink">{{ $patient->demographic->phone_number ?? 'N/A' }}</dd></div></dl></x-ui.card>
        @if($labours->isEmpty())
            <x-ui.empty-state title="No Labour Records" message="No labour records found for this patient." />
        @else
            <x-ui.table><thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Onset</th><th class="px-4 py-3">Stage</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">BP</th><th class="px-4 py-3">FHR</th><th class="px-4 py-3">Temperature</th><th class="px-4 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y divide-med-line bg-white">
                @foreach($labours as $labour)
                    <tr><td class="px-4 py-4 text-med-muted">{{ optional($labour->labour_onset_time)->format('M d, Y') ?? 'N/A' }}</td><td class="px-4 py-4"><x-ui.badge variant="info">{{ str($labour->mode_of_onset ?? 'N/A')->headline() }}</x-ui.badge></td><td class="px-4 py-4"><x-ui.badge>{{ str($labour->stage)->headline() }}</x-ui.badge></td><td class="px-4 py-4"><x-ui.badge :variant="$labour->status === 'completed' ? 'success' : ($labour->status === 'complicated' ? 'danger' : 'warning')">{{ str($labour->status)->headline() }}</x-ui.badge></td><td class="px-4 py-4 text-med-muted">{{ $labour->blood_pressure ?? '-' }}</td><td class="px-4 py-4 text-med-muted">{{ $labour->fetal_heart_rate ? $labour->fetal_heart_rate . ' bpm' : '-' }}</td><td class="px-4 py-4 text-med-muted">{{ $labour->temperature ? $labour->temperature . ' C' : '-' }}</td><td class="px-4 py-4"><div class="flex flex-wrap justify-end gap-2"><a href="{{ route('midwife.labour.show', $labour) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">View</a><a href="{{ route('midwife.labour.edit', $labour) }}" class="text-sm font-semibold text-med-info hover:text-blue-700">Edit</a><form action="{{ route('midwife.labour.destroy', $labour) }}" method="POST" onsubmit="return confirm('Delete this labour record?')">@csrf @method('DELETE')<button type="submit" class="text-sm font-semibold text-med-danger hover:text-red-800">Delete</button></form></div></td></tr>
                @endforeach
            </tbody></x-ui.table>
        @endif
    </x-ui.page>
@endsection
