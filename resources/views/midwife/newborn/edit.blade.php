@extends('layouts.modern')

@section('title', 'Edit Newborn Record')

@section('content')
<x-ui.page title="Edit Newborn Record" subtitle="Update neonatal details and observations.">
    <x-slot name="actions"><a href="{{ route('midwife.newborn.show', $newborn) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a></x-slot>
    <form action="{{ route('midwife.newborn.update', $newborn) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('midwife.newborn._form', ['newborn' => $newborn, 'delivery' => $newborn->delivery, 'submitLabel' => 'Update Newborn', 'cancelRoute' => route('midwife.newborn.show', $newborn)])
    </form>
</x-ui.page>
@endsection
