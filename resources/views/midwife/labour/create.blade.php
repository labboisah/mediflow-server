@extends('layouts.modern')

@section('title', 'Create Labour Record')

@section('content')
    <x-ui.page title="New Labour Record" subtitle="Record labour admission for {{ $patient->name() }}.">
        <form action="{{ route('midwife.labour.store', $patient) }}" method="POST">@csrf @include('midwife.labour._form', ['patient' => $patient, 'submitLabel' => 'Create Labour Record', 'cancelRoute' => route('midwife.labour.index')])</form>
    </x-ui.page>
@endsection
