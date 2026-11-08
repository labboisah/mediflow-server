@extends('layouts.modern')

@section('title', 'New Newborn Examination')

@section('content')
<x-ui.page title="Record Newborn Examination" subtitle="Newborn {{ $newborn->newborn_registration_number ?? 'N/A' }}">
    <x-slot name="actions"><a href="{{ route('midwife.newborn.show', $newborn) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a></x-slot>
    <form action="{{ route('midwife.newborn-examination.store', $newborn) }}" method="POST" class="space-y-6">@csrf @include('midwife.newborn-examination._form', ['newborn' => $newborn, 'submitLabel' => 'Save Examination', 'cancelRoute' => route('midwife.newborn.show', $newborn)])</form>
</x-ui.page>
@endsection
