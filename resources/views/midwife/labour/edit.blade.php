@extends('layouts.modern')

@section('title', 'Edit Labour Record')

@section('content')
    <x-ui.page title="Edit Labour Record" subtitle="Update labour information for {{ $patient->name() }}.">
        <form action="{{ route('midwife.labour.update', $labour) }}" method="POST">@csrf @method('PUT') @include('midwife.labour._form', ['patient' => $patient, 'labour' => $labour, 'submitLabel' => 'Update Labour Record', 'cancelRoute' => route('midwife.labour.show', $labour)])</form>
    </x-ui.page>
@endsection
