@extends('layouts.modern')

@section('title', 'Edit Labour Progress')

@section('content')
    <x-ui.page title="Edit Labour Progress" subtitle="Progress record at {{ $progress->recorded_at->format('M d, Y H:i') }}.">
        <form action="{{ route('midwife.labour.progress.update', [$progress->labour, $progress]) }}" method="POST">@csrf @method('PUT') @include('midwife.labour-progress._form', ['progress' => $progress, 'labour' => $progress->labour, 'submitLabel' => 'Save Changes', 'cancelRoute' => route('midwife.labour.progress.show', [$progress->labour, $progress])])</form>
    </x-ui.page>
@endsection
