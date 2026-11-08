@extends('layouts.modern')

@section('title', 'Dashboard.Blade')
@section('page-title', 'Dashboard.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Dashboard')

@section('content')

    @if(auth()->user()->hasRole('midwife'))
        @php 
            $data = auth()->user()->getMidwifeData();
        @endphp
    @include('midwife.dashboard', $data)
    @endif

    @if(auth()->user()->hasRole('record'))
        @include('record.dashboard')
    @endif

    @if(auth()->user()->hasRole('doctor'))
        @include('doctor.dashboard')
    @endif

    @if(auth()->user()->hasRole('nurse'))
        @include('nurse.dashboard')
    @endif

    @if(auth()->user()->hasRole('pharmacist'))
        @include('pharmacy.dashboard')
    @endif

    @if(auth()->user()->hasRole('lab_technician'))
        @include('lab.dashboard')
    @endif

    @if(Auth::user()->hasRole('accountant'))
        @include('accountant.dashboard')
    @endif

    @if(auth()->user()->hasRole('administrator'))
        @include('admin.dashboard')
    @endif 
    
    @if(auth()->user()->hasRole('radiologist'))
        @include('radiology.dashboard')
    @endif 

@endsection


