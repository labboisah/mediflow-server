@extends('layouts.modern')

@section('title', 'New Antenatal Care Record')

@section('content')
    <x-ui.page title="New Antenatal Care Record" subtitle="Create antenatal care record for {{ $patient->demographic->first_name ?? 'N/A' }} {{ $patient->demographic->last_name ?? '' }}.">
        <form action="{{ route('midwife.antenatal.store', $patient) }}" method="POST">
            @csrf
            @include('midwife.antenatal._form', [
                'patient' => $patient,
                'visit' => $visit,
                'submitLabel' => 'Create Antenatal Record',
                'cancelRoute' => route('midwife.antenatal.index'),
            ])
        </form>
    </x-ui.page>
@endsection
