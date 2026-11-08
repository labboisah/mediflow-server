@extends('layouts.modern')

@section('title', 'Radiology Requests')

@section('content')
    <x-ui.page title="Radiology Requests" subtitle="Review paid requests, record results, and print completed reports.">
        @livewire('radiology.investigation-requests-table')
    </x-ui.page>
@endsection
