@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() how. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() how. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')

@php 
$reportData = Auth::user()->generateReportData($date, $fromDate ?? null, $toDate ?? null); 
@endphp
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @include('reports.partials.report-actions')
            @include('reports.partials.report-header')

            {{-- Admin-Specific Report Content --}}
            @if(Auth::user()->hasRole('admin'))
                @include('reports.partials.admin_report', ['reportData' => $reportData])
            @endif
    
            {{-- Doctor-Specific Report Content --}}
            @if(Auth::user()->hasRole('doctor') && app(\App\Services\LicenseService::class)->moduleEnabled('doctor'))
                @include('reports.partials.doctor_report', ['reportData' => $reportData])
            @endif

            {{-- Nurse-Specific Report Content --}}
            @if(Auth::user()->hasRole('nurse') && app(\App\Services\LicenseService::class)->moduleEnabled('nursing'))
                @include('reports.partials.nurse_report', ['reportData' => $reportData])
            @endif

            {{-- Pharmacist-Specific Report Content --}}
            @if(Auth::user()->hasRole('pharmacist') && app(\App\Services\LicenseService::class)->moduleEnabled('pharmacy'))
                @include('reports.partials.pharmacist_report', ['reportData' => $reportData])
            @endif

            {{-- Midwife-Specific Report Content --}}
            @if(Auth::user()->hasRole('midwife') && app(\App\Services\LicenseService::class)->moduleEnabled('maternity'))
                @include('reports.partials.midwife_report', ['reportData' => $reportData])
            @endif

            {{-- Lab-Specific Report Content --}}
            @if(Auth::user()->hasRole('lab') && app(\App\Services\LicenseService::class)->moduleEnabled('laboratory'))
                @include('reports.partials.lab_report', ['reportData' => $reportData])
            @endif

            {{-- Pharmacy-Specific Report Content --}}
            @if(Auth::user()->hasRole('pharmacy') && app(\App\Services\LicenseService::class)->moduleEnabled('pharmacy'))
                @include('reports.partials.pharmacy_report', ['reportData' => $reportData])
            @endif

            {{-- Radiology-Specific Report Content --}}
            @if(Auth::user()->hasRole('radiology') && app(\App\Services\LicenseService::class)->moduleEnabled('radiology'))
                @include('reports.partials.radiology_report', ['reportData' => $reportData])
            @endif

            {{-- Record Officer-Specific Report Content --}}
            @if(Auth::user()->hasRole('record') && app(\App\Services\LicenseService::class)->moduleEnabled('patient_records'))
                @include('reports.partials.record_report', ['reportData' => $reportData])
            @endif

            {{-- Accountant-Specific Report Content --}}
            @if(Auth::user()->hasRole('accountant') && app(\App\Services\LicenseService::class)->moduleEnabled('billing'))
                @include('reports.partials.accountant_report', ['reportData' => $reportData])
            @endif

             
        </div>
    </div>
</div>
@endsection
