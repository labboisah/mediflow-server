@extends('layouts.modern')

@section('title', 'Labour Records')

@section('content')
    <x-ui.page title="Labour Records" subtitle="Search and manage labour records across maternity care.">
        <x-slot name="actions"><a href="{{ route('midwife.labour-management') }}" class="inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">Direct Labour Entry</a></x-slot>
        <x-ui.card><form method="GET" action="{{ route('midwife.labour.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end"><div class="flex-1"><x-ui.input label="Search" type="search" name="q" value="{{ $search }}" placeholder="Hospital number, patient name, phone, stage, or status" /></div><x-ui.button type="submit" variant="secondary">Search</x-ui.button></form></x-ui.card>
        @if($labours->isEmpty())
            <x-ui.empty-state title="No Labour Records" message="No records match the current search." />
        @else
            <x-ui.table><thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Onset</th><th class="px-4 py-3">Hospital No.</th><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Phone</th><th class="px-4 py-3">Stage</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Recorded By</th><th class="px-4 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y divide-med-line bg-white">
                @foreach($labours as $record)
                    @php($patient = $record->patient)
                    <tr><td class="px-4 py-4 text-med-muted">{{ $record->labour_onset_time?->format('M d, Y h:i A') ?? $record->created_at?->format('M d, Y h:i A') }}</td><td class="px-4 py-4"><x-ui.badge variant="info">{{ $patient?->hospital_number ?? 'N/A' }}</x-ui.badge></td><td class="px-4 py-4 font-semibold text-med-ink">{{ $patient?->name() ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $patient?->demographic?->phone_number ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ str($record->stage)->headline() }}</td><td class="px-4 py-4"><x-ui.badge :variant="$record->status === 'completed' ? 'success' : ($record->status === 'complicated' ? 'danger' : 'warning')">{{ str($record->status)->headline() }}</x-ui.badge></td><td class="px-4 py-4 text-med-muted">{{ $record->recordedBy?->name ?? 'N/A' }}</td><td class="px-4 py-4"><div class="flex flex-wrap justify-end gap-2"><a href="{{ route('midwife.labour.show', $record) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">View</a><a href="{{ route('midwife.labour.edit', $record) }}" class="text-sm font-semibold text-med-info hover:text-blue-700">Edit</a>@if($patient)<a href="{{ route('midwife.patient.show', $patient) }}" class="text-sm font-semibold text-med-success hover:text-green-700">Profile</a>@endif</div></td></tr>
                @endforeach
            </tbody></x-ui.table>
        @endif
    </x-ui.page>
@endsection
