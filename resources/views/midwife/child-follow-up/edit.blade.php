@extends('layouts.modern')

@section('title', 'Edit Child Follow-up')

@section('content')
<x-ui.page title="Edit Child Follow-up" subtitle="Update child follow-up assessment.">
    <x-slot name="actions"><a href="{{ route('midwife.child-follow-up.show', $childFollowUp) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a></x-slot>
    <form action="{{ route('midwife.child-follow-up.update', $childFollowUp) }}" method="POST" class="space-y-6">@csrf @method('PUT') @include('midwife.child-follow-up._form', ['childFollowUp' => $childFollowUp, 'newborn' => $childFollowUp->newborn, 'submitLabel' => 'Update Follow-up', 'cancelRoute' => route('midwife.child-follow-up.show', $childFollowUp)])</form>
</x-ui.page>
@endsection
