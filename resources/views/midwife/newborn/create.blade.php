@extends('layouts.modern')

@section('title', 'Newborn Registration - ' . $delivery->patient->name())

@section('content')
<x-ui.page title="Newborn Registration" subtitle="Register newborn details for {{ $delivery->patient->name() }}.">
    <x-slot name="actions"><a href="{{ route('midwife.delivery.show', $delivery) }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back</a></x-slot>
    <form action="{{ route('midwife.newborn.store', $delivery) }}" method="POST" class="space-y-6">
        @csrf
        @include('midwife.newborn._form', ['delivery' => $delivery, 'submitLabel' => 'Register Newborn', 'cancelRoute' => route('midwife.delivery.show', $delivery)])
    </form>
</x-ui.page>
@endsection
