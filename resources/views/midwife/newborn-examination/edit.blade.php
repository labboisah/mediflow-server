@extends('layouts.modern')

@section('title', 'Edit Newborn Examination')

@section('content')
<x-ui.page title="Edit Newborn Examination" subtitle="Update neonatal examination details.">
    <x-slot name="actions"><a href="{{ route('midwife.newborn-examination.show', $newbornExamination) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a></x-slot>
    <form action="{{ route('midwife.newborn-examination.update', $newbornExamination) }}" method="POST" class="space-y-6">@csrf @method('PUT') @include('midwife.newborn-examination._form', ['newbornExamination' => $newbornExamination, 'newborn' => $newbornExamination->newborn, 'submitLabel' => 'Update Examination', 'cancelRoute' => route('midwife.newborn-examination.show', $newbornExamination)])</form>
</x-ui.page>
@endsection
