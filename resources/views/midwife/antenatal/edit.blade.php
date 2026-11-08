@extends('layouts.modern')

@section('title', 'Edit Antenatal Care Record')

@section('content')
    <x-ui.page title="Edit Antenatal Care Record" subtitle="Update antenatal care record for {{ $antenatalCare->patient->demographic->first_name }} {{ $antenatalCare->patient->demographic->last_name }}.">
        <form action="{{ route('midwife.antenatal.update', $antenatalCare) }}" method="POST">
            @csrf
            @method('PUT')
            @include('midwife.antenatal._form', [
                'patient' => $antenatalCare->patient,
                'antenatalCare' => $antenatalCare,
                'submitLabel' => 'Update Antenatal Record',
                'cancelRoute' => route('midwife.antenatal.show', $antenatalCare),
            ])
        </form>
    </x-ui.page>
@endsection
