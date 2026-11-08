@extends('layouts.modern')

@section('title', 'Verify.Blade')
@section('page-title', 'Verify.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('content')
<div class="bg-white p-8 rounded-2xl shadow max-w-3xl mx-auto">
    <h2 class="text-2xl font-bold text-primary mb-4">Report Verification</h2>

    <p><strong>Reference:</strong> {{ $report->report_reference }}</p>
    <p><strong>Period:</strong> {{ $report->from_date }} - {{ $report->to_date }}</p>
    <p><strong>Net Revenue:</strong> ₦{{ number_format($report->net,2) }}</p>
    <p><strong>Profit:</strong> ₦{{ number_format($report->profit,2) }}</p>
</div>
@endsection
