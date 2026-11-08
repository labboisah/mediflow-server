@extends('layouts.modern')
@section('title', 'Edit Delivery Record')
@section('content')
<x-ui.page title="Edit Delivery Record" subtitle="Update delivery information for {{ $delivery->patient->name() }}."><form action="{{ route('midwife.delivery.update', $delivery) }}" method="POST">@csrf @method('PUT') @include('midwife.delivery._form', ['delivery'=>$delivery, 'labour'=>$delivery->labour, 'submitLabel'=>'Update Delivery', 'cancelRoute'=>route('midwife.delivery.show', $delivery)])</form></x-ui.page>
@endsection
