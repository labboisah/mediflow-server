@extends('layouts.modern')

@section('title', 'Patient List')
@section('page-title', 'Patient List')
@section('page-subtitle', 'Search, filter, and open patient records quickly.')

@section('content')
    <livewire:patient.patient-management mode="record" />
@endsection
