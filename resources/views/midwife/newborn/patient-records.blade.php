@extends('layouts.modern')

@section('title', 'Patient Newborn Records')

@section('content')
<x-ui.page title="Newborn Records" subtitle="Records for {{ $patient->name() }}.">
    <x-slot name="actions"><a href="{{ route('midwife.patient.show', $patient) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Patient Profile</a></x-slot>

    <x-ui.card title="Patient History" subtitle="Total: {{ $newborns->count() }}">
        @if($newborns->isEmpty())
            <x-ui.empty-state title="No newborn records" message="No newborns have been recorded for this patient." />
        @else
            <x-ui.table>
                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Registry #</th><th class="px-4 py-3">Delivery ID</th><th class="px-4 py-3">Birth Date/Time</th><th class="px-4 py-3">Sex</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-med-line bg-white">@foreach($newborns as $newborn)<tr><td class="px-4 py-4 text-med-muted">{{ $loop->iteration }}</td><td class="px-4 py-4 font-semibold text-med-ink">{{ $newborn->newborn_registration_number ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $newborn->delivery_id }}</td><td class="px-4 py-4 text-med-muted">{{ $newborn->birth_date_time?->format('M d, Y H:i') ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ str($newborn->sex)->headline() }}</td><td class="px-4 py-4"><x-ui.badge variant="{{ $newborn->status === 'alive' ? 'success' : 'danger' }}">{{ str($newborn->status)->headline() }}</x-ui.badge></td><td class="px-4 py-4"><div class="flex justify-end gap-2"><a href="{{ route('midwife.newborn.show', $newborn) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">View</a><a href="{{ route('midwife.newborn.edit', $newborn) }}" class="text-sm font-semibold text-med-info hover:text-blue-700">Edit</a></div></td></tr>@endforeach</tbody>
            </x-ui.table>
        @endif
    </x-ui.card>
</x-ui.page>
@endsection
