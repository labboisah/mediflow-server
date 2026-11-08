@extends('layouts.modern')

@section('title', 'New Labour Progress')

@section('content')
    <x-ui.page title="Add Labour Progress" subtitle="Record new progress for labour of {{ $labour->patient->full_name ?? $labour->patient->name() }}.">
        <form action="{{ route('midwife.labour.progress.store', $labour) }}" method="POST">@csrf @include('midwife.labour-progress._form', ['labour' => $labour, 'submitLabel' => 'Save Progress', 'cancelRoute' => route('midwife.labour.progress.index', $labour)])</form>
    </x-ui.page>
@endsection
