@extends('layouts.modern')
@section('title', 'Delivery Registration')
@section('content')
<x-ui.page title="Delivery Registration" subtitle="Register delivery details for {{ $labour->patient->name() }}."><form action="{{ route('midwife.delivery.store', $labour) }}" method="POST">@csrf @include('midwife.delivery._form', ['labour'=>$labour, 'submitLabel'=>'Register Delivery', 'cancelRoute'=>route('midwife.labour.show', $labour)])</form></x-ui.page>
@endsection
