@extends('layouts.modern')

@section('title', 'Investigation Result')

@section('content')
@php($patient = $investigationRequest->patientVisit?->patient)
<x-ui.page title="Investigation Result" subtitle="{{ $patient?->hospital_number ?? 'N/A' }} | {{ $patient?->demographic?->full_name ?? 'Patient' }}">
    <x-slot:actions>
        @if($patient)
            <a href="{{ route('patient.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back to Patient</a>
        @endif
    </x-slot:actions>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <x-ui.card title="{{ $investigationRequest->investigation?->name ?? 'Investigation' }}" subtitle="{{ $investigationRequest->investigation?->investigationType?->name ?? 'Investigation details' }}">
            @if($investigationRequest->investigationResults->count() > 0)
                <div class="space-y-3">
                    @foreach($investigationRequest->investigationResults as $result)
                        <div class="rounded-md border border-med-line p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-med-ink">{{ $result->parameter?->name ?? 'Parameter' }}</p>
                                    <p class="mt-1 text-sm text-med-muted">Range: {{ $result->parameter?->reference_range ?? 'N/A' }}</p>
                                </div>
                                <p class="text-right text-sm font-semibold text-med-ink">{{ $result->value }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Results Available" message="No results are available for this investigation request yet." />
            @endif
        </x-ui.card>

        <div class="space-y-5">
            <x-ui.card title="Request Details">
                <div class="space-y-3">
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Patient Name</p><p class="mt-1 text-sm font-semibold text-med-ink">{{ $patient?->demographic?->full_name ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Requested By</p><p class="mt-1 text-sm text-med-ink">{{ $investigationRequest->requestedBy?->name ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Requested At</p><p class="mt-1 text-sm text-med-ink">{{ optional($investigationRequest->created_at)->format('M d, Y h:i A') }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Completed At</p><p class="mt-1 text-sm text-med-ink">{{ optional($investigationRequest->completed_at)->format('M d, Y h:i A') ?? 'Not completed yet' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Performed By</p><p class="mt-1 text-sm text-med-ink">{{ $investigationRequest->performedBy?->name ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Status</p><p class="mt-1"><x-ui.badge variant="warning">{{ ucfirst($investigationRequest->status ?? 'pending') }}</x-ui.badge></p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Specimen</p><p class="mt-1 text-sm text-med-ink">{{ $investigationRequest->specimen ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Clinical Notes</p><p class="mt-1 text-sm text-med-ink">{{ $investigationRequest->clinical_diagnoses ?? 'N/A' }}</p></div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-ui.page>
@endsection
