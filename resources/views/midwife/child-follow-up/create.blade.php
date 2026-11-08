@extends('layouts.modern')

@section('title', 'Child Follow-up Assessment')

@section('content')
<x-ui.page title="Child Follow-up Assessment" subtitle="Baby {{ $newborn->newborn_registration_number ?? 'N/A' }} - Mother: {{ $newborn->patient->name() }}">
    <x-slot name="actions"><a href="{{ route('midwife.child-follow-up.record', $newborn) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a></x-slot>
    <form action="{{ route('midwife.child-follow-up.store', $newborn) }}" method="POST" class="space-y-6">@csrf @include('midwife.child-follow-up._form', ['newborn' => $newborn, 'submitLabel' => 'Save Follow-up', 'cancelRoute' => route('midwife.child-follow-up.record', $newborn)])</form>
</x-ui.page>
@endsection
