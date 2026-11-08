@extends('layouts.modern')

@section('title', 'Edit Postnatal Examination')

@section('content')
<x-ui.page title="Edit Postnatal Examination" subtitle="Update postnatal recovery findings.">
    <x-slot name="actions"><a href="{{ route('midwife.postnatal-examination.show', $postnatalExamination) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a></x-slot>
    <form action="{{ route('midwife.postnatal-examination.update', $postnatalExamination) }}" method="POST" class="space-y-6">@csrf @method('PUT') @include('midwife.postnatal-examination._form', ['postnatalExamination' => $postnatalExamination, 'delivery' => $postnatalExamination->delivery, 'submitLabel' => 'Update Examination', 'cancelRoute' => route('midwife.postnatal-examination.show', $postnatalExamination)])</form>
</x-ui.page>
@endsection
