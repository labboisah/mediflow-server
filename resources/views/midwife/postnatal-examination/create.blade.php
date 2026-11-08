@extends('layouts.modern')

@section('title', 'New Postnatal Examination')

@section('content')
<x-ui.page title="Record Postnatal Examination" subtitle="{{ $delivery->patient->name() }} after {{ str($delivery->delivery_type ?? 'delivery')->headline() }}.">
    <x-slot name="actions"><a href="{{ route('midwife.delivery.show', $delivery) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a></x-slot>
    <form action="{{ route('midwife.postnatal-examination.store', $delivery) }}" method="POST" class="space-y-6">@csrf @include('midwife.postnatal-examination._form', ['delivery' => $delivery, 'submitLabel' => 'Save Examination', 'cancelRoute' => route('midwife.delivery.show', $delivery)])</form>
</x-ui.page>
@endsection
