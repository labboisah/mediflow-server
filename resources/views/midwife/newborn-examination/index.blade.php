@extends('layouts.modern')

@section('title', 'Newborn Examination Records')

@section('content')
<x-ui.page title="Newborn Examination Records" subtitle="Search and manage neonatal examination records.">
    <div class="space-y-6">
        <x-ui.card><form method="GET" action="{{ route('midwife.newborn-examination.index') }}" class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end"><label><span class="mb-1 block text-sm font-medium text-med-ink">Search</span><input type="search" name="q" value="{{ $search }}" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm placeholder:text-med-muted/70" placeholder="Hospital number, mother, registration number, phone, or status"></label><x-ui.button type="submit">Search</x-ui.button></form></x-ui.card>
        <x-ui.card title="Records" subtitle="Total: {{ $newbornExaminations->count() }}">
            @if($newbornExaminations->isEmpty())<x-ui.empty-state title="No newborn examination records found" message="Try another search term or record an examination from a newborn record." />@else
            <x-ui.table><thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Newborn #</th><th class="px-4 py-3">Mother</th><th class="px-4 py-3">Weight</th><th class="px-4 py-3">Temperature</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Recorded By</th><th class="px-4 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y divide-med-line bg-white">@foreach($newbornExaminations as $record)@php($patient = $record->newborn?->patient)<tr><td class="px-4 py-4 text-med-muted">{{ $record->examination_date_time?->format('M d, Y h:i A') ?? 'N/A' }}</td><td class="px-4 py-4 font-semibold text-med-ink">{{ $record->newborn?->newborn_registration_number ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $patient?->name() ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $record->weight ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $record->temperature ?? 'N/A' }}</td><td class="px-4 py-4"><x-ui.badge variant="{{ $record->exam_status === 'normal' ? 'success' : ($record->exam_status === 'abnormal' ? 'danger' : 'warning') }}">{{ str($record->exam_status)->headline() }}</x-ui.badge></td><td class="px-4 py-4 text-med-muted">{{ $record->recordedBy?->name ?? 'N/A' }}</td><td class="px-4 py-4"><div class="flex flex-wrap justify-end gap-2"><a href="{{ route('midwife.newborn-examination.show', $record) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">View</a><a href="{{ route('midwife.newborn-examination.edit', $record) }}" class="text-sm font-semibold text-med-info hover:text-blue-700">Edit</a>@if($patient)<a href="{{ route('midwife.patient.show', $patient) }}" class="text-sm font-semibold text-med-muted hover:text-med-ink">Profile</a>@endif</div></td></tr>@endforeach</tbody></x-ui.table>
            @endif
        </x-ui.card>
    </div>
</x-ui.page>
@endsection
