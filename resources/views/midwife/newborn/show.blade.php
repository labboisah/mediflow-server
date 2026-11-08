@extends('layouts.modern')

@section('title', 'Newborn Record - ' . ($newborn->newborn_registration_number ?? 'Newborn'))

@section('content')
@php
    $statusVariant = $newborn->status === 'alive' ? 'success' : 'danger';
    $yesNo = fn ($value) => $value ? ['Yes', 'success'] : ['No', 'danger'];
@endphp
<x-ui.page title="Newborn Record" subtitle="{{ $newborn->patient->name() }} - {{ $newborn->birth_date_time?->format('M d, Y h:i A') ?? 'N/A' }}">
    <x-slot name="actions"><a href="{{ route('midwife.newborn.edit', $newborn) }}" class="inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">Edit</a><a href="{{ route('midwife.delivery.show', $newborn->delivery_id) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a></x-slot>

    <div class="grid gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <x-ui.card title="Delivery Information">
                <dl class="grid gap-4 text-sm md:grid-cols-4"><div><dt class="text-med-muted">Mother</dt><dd class="font-semibold text-med-ink">{{ $newborn->patient->name() }}</dd></div><div><dt class="text-med-muted">Hospital Number</dt><dd class="font-semibold text-med-ink">{{ $newborn->patient->hospital_number }}</dd></div><div><dt class="text-med-muted">Delivery Type</dt><dd><x-ui.badge>{{ str($newborn->delivery->delivery_type)->headline() }}</x-ui.badge></dd></div><div><dt class="text-med-muted">Delivery Date</dt><dd class="font-semibold text-med-ink">{{ $newborn->delivery->delivery_date_time?->format('M d, Y h:i A') ?? 'N/A' }}</dd></div></dl>
            </x-ui.card>

            <x-ui.card title="Newborn Information">
                <dl class="grid gap-4 text-sm md:grid-cols-4"><div><dt class="text-med-muted">Sex</dt><dd class="font-semibold text-med-ink">{{ str($newborn->sex)->headline() }}</dd></div><div><dt class="text-med-muted">Birth Order</dt><dd class="font-semibold text-med-ink">{{ $newborn->birth_order ?? 'N/A' }}</dd></div><div><dt class="text-med-muted">Registration Number</dt><dd class="font-semibold text-med-ink">{{ $newborn->newborn_registration_number ?? 'N/A' }}</dd></div><div><dt class="text-med-muted">Birth Date & Time</dt><dd class="font-semibold text-med-ink">{{ $newborn->birth_date_time?->format('M d, Y h:i A') ?? 'N/A' }}</dd></div><div><dt class="text-med-muted">Presentation</dt><dd class="font-semibold text-med-ink">{{ str($newborn->presentation ?? 'N/A')->headline() }}</dd></div><div><dt class="text-med-muted">Status</dt><dd><x-ui.badge variant="{{ $statusVariant }}">{{ str($newborn->status)->headline() }}</x-ui.badge></dd></div></dl>
            </x-ui.card>

            <x-ui.card title="Measurements And APGAR">
                <div class="grid gap-4 md:grid-cols-3"><div class="rounded-md border border-med-line p-4"><p class="text-xs uppercase text-med-muted">Birth Weight</p><p class="mt-1 text-lg font-semibold text-med-ink">{{ $newborn->birth_weight ?? 'N/A' }}</p></div><div class="rounded-md border border-med-line p-4"><p class="text-xs uppercase text-med-muted">Birth Length</p><p class="mt-1 text-lg font-semibold text-med-ink">{{ $newborn->birth_length ?? 'N/A' }}</p></div><div class="rounded-md border border-med-line p-4"><p class="text-xs uppercase text-med-muted">Head Circumference</p><p class="mt-1 text-lg font-semibold text-med-ink">{{ $newborn->head_circumference ?? 'N/A' }}</p></div><div class="rounded-md border border-med-line p-4"><p class="text-xs uppercase text-med-muted">APGAR 1 Minute</p><p class="mt-1 text-2xl font-semibold text-med-ink">{{ $newborn->apgar_score_1_minute ?? '-' }}</p></div><div class="rounded-md border border-med-line p-4"><p class="text-xs uppercase text-med-muted">APGAR 5 Minutes</p><p class="mt-1 text-2xl font-semibold text-med-ink">{{ $newborn->apgar_score_5_minutes ?? '-' }}</p></div><div class="rounded-md border border-med-line p-4"><p class="text-xs uppercase text-med-muted">APGAR 10 Minutes</p><p class="mt-1 text-2xl font-semibold text-med-ink">{{ $newborn->apgar_score_10_minutes ?? '-' }}</p></div></div>
                <dl class="mt-4 grid gap-4 text-sm md:grid-cols-5"><div><dt class="text-med-muted">Appearance</dt><dd class="font-semibold text-med-ink">{{ $newborn->apgar_appearance_1min ?? '-' }}</dd></div><div><dt class="text-med-muted">Pulse</dt><dd class="font-semibold text-med-ink">{{ $newborn->apgar_pulse_1min ?? '-' }}</dd></div><div><dt class="text-med-muted">Grimace</dt><dd class="font-semibold text-med-ink">{{ $newborn->apgar_grimace_1min ?? '-' }}</dd></div><div><dt class="text-med-muted">Activity</dt><dd class="font-semibold text-med-ink">{{ $newborn->apgar_activity_1min ?? '-' }}</dd></div><div><dt class="text-med-muted">Respiration</dt><dd class="font-semibold text-med-ink">{{ $newborn->apgar_respiration_1min ?? '-' }}</dd></div></dl>
            </x-ui.card>

            <x-ui.card title="Clinical Notes"><dl class="space-y-4 text-sm">@foreach(['general_condition'=>'General Condition','physical_examination'=>'Physical Examination','birth_defects_noted'=>'Birth Defects Noted','meconium_aspiration'=>'Meconium Aspiration','feeding_problems'=>'Feeding Problems','immunizations_details'=>'Immunization Details','screening_test_results'=>'Screening Test Results','special_care_needed'=>'Special Care Needed','referred_to'=>'Referred To','neonatal_observations'=>'Neonatal Observations','delivery_notes'=>'Delivery Notes'] as $field => $label)<div><dt class="font-medium text-med-muted">{{ $label }}</dt><dd class="mt-1 whitespace-pre-line text-med-ink">{{ $newborn->{$field} ?: 'N/A' }}</dd></div>@endforeach</dl></x-ui.card>
        </div>

        <aside class="space-y-6 xl:col-span-4">
            <x-ui.card title="Care Checks"><dl class="space-y-3 text-sm">@foreach(['breastfeeding_initiated'=>'Breastfeeding Initiated','vitamin_k_given'=>'Vitamin K','eye_prophylaxis_given'=>'Eye Prophylaxis','immunizations_given'=>'Immunizations','screening_test_done'=>'Screening Test'] as $field => $label)@php([$text, $variant] = $yesNo($newborn->{$field}))<div class="flex items-center justify-between gap-3"><dt class="text-med-muted">{{ $label }}</dt><dd><x-ui.badge variant="{{ $variant }}">{{ $text }}</x-ui.badge></dd></div>@endforeach<div><dt class="text-med-muted">First Breastfeed Time</dt><dd class="font-semibold text-med-ink">{{ $newborn->first_breastfeed_time?->format('M d, Y h:i A') ?? 'N/A' }}</dd></div></dl></x-ui.card>
            <x-ui.card title="Record Metadata"><dl class="space-y-3 text-sm"><div><dt class="text-med-muted">Recorded By</dt><dd class="font-semibold text-med-ink">{{ $newborn->recordedBy->name ?? 'N/A' }}</dd></div><div><dt class="text-med-muted">Created At</dt><dd class="font-semibold text-med-ink">{{ $newborn->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</dd></div><div><dt class="text-med-muted">Last Updated</dt><dd class="font-semibold text-med-ink">{{ $newborn->updated_at?->format('M d, Y h:i A') ?? 'N/A' }}</dd></div><div><dt class="text-med-muted">Record ID</dt><dd class="font-semibold text-med-ink">#{{ $newborn->id }}</dd></div></dl></x-ui.card>
        </aside>
    </div>
</x-ui.page>
@endsection
