@extends('layouts.modern')
@section('content')<x-ui.page title="Specialist Billing" subtitle="{{ $c->patient->name() }} | {{ $c->service_name }}">
<x-slot:actions><a href="{{ route('specialist.index') }}">Workspace</a></x-slot:actions>@include('specialist.notice')@include('specialist.billing-panel')</x-ui.page>@endsection
