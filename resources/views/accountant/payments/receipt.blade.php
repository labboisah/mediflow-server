@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() eceipt. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() eceipt. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')
@php
    $patientFirst = data_get($payment, 'bill.patientVisit.patient.demographic.first_name') ?? '';
    $patientLast = data_get($payment, 'bill.patientVisit.patient.demographic.last_name') ?? '';
    $patientName = trim(strtoupper($patientFirst . ' ' . $patientLast));
    $patientName = $patientName ?: strtoupper(data_get($payment, 'bill.walkinPatient.name', 'Walk-in Patient'));
    $hospitalNumber = data_get($payment, 'bill.patientVisit.patient.hospital_number', 'Walk-in Patient');
    $receiptDate = now()->format('M d, Y h:i A');
@endphp

<div class="container-fluid">
    {{-- Payment Receipt Header --}}
    <div class="row justify-content-center mb-4">
        <div class="col-md-10">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <strong>Payment Successful!</strong> Your payment has been recorded successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>

    {{-- Print Actions --}}
    <div class="row justify-content-center mb-3 d-print-none">
        <div class="col-md-10">
            <div class="card shadow-sm border-secondary">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <button onclick="window.print()" class="btn btn-primary w-100">
                                <i class="bi bi-printer"></i> Print A4 Receipt Copies
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button onclick="printThermalReceipt()" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-printer-fill"></i> Print Thermal Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Receipt Preview --}}
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm" id="receipt-preview">
                <div class="card-header bg-primary text-white p-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">Payment Receipt</h5>
                            <small>Payment ID: <strong>{{ $payment->payment_id }}</strong></small>
                        </div>
                        <div class="col-auto text-end">
                            <i class="bi bi-receipt" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Patient Name</p>
                            <p class="fw-bold">{{ $patientName }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Hospital Number</p>
                            <p class="fw-bold">{{ $hospitalNumber }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Payment Date</p>
                            <p class="fw-bold">{{ $payment->payment_date->format('M d, Y h:i A') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Payment Status</p>
                            <span class="badge bg-success fs-6">{{ ucfirst($payment->status) }}</span>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Payment Method</p>
                            <p class="fw-bold">{{ $payment->paymentMethod->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Reference Number</p>
                            <p class="fw-bold">{{ $payment->reference_number ?? 'N/A' }}</p>
                        </div>
                    </div>

                    @if($payment->insurance_provider)
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p class="text-muted small mb-1">Insurance Provider</p>
                                <p class="fw-bold">{{ $payment->insurance_provider }}</p>
                            </div>
                        </div>
                    @endif

                    <hr>

                    @if($payment->bill && ($payment->bill->serviceRequests->count() > 0 || $payment->bill->investigationRequests->count() > 0))
                        <div class="mb-4">
                            <p class="text-muted small mb-2">Services & Items Paid For</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Description</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">Payment Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($payment->bill->serviceRequests as $serviceRequest)
                                            <tr>
                                                <td>
                                                    <strong>{{ $serviceRequest->service->name }}</strong><br>
                                                    <small class="text-muted">{{ $serviceRequest->service->code }}</small>
                                                </td>
                                                <td class="text-end">{{ number_format($serviceRequest->service->price, 2) }}</td>
                                                <td class="text-end">{{ $serviceRequest->payment_status }}</td>
                                            </tr>
                                        @endforeach
                                        @foreach($payment->bill->investigationRequests as $investigationRequest)
                                            <tr>
                                                <td>
                                                    <strong>{{ $investigationRequest->investigation->name }}</strong><br>
                                                    <small class="text-muted">{{ $investigationRequest->investigation->code }}</small>
                                                </td>
                                                <td class="text-end">{{ number_format($investigationRequest->investigation->price, 2) }}</td>
                                                <td class="text-end">{{ $investigationRequest->payment_status }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <hr>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-8 text-end">
                            <p class="text-muted small mb-1">Amount Paid</p>
                        </div>
                        <div class="col-md-4">
                            <h4 class="text-success mb-0">{{ number_format($payment->amount, 2) }}</h4>
                        </div>
                    </div>

                    @if($payment->bill)
                        <div class="alert alert-info small mb-3">
                            <strong>Bill Status:</strong>
                            {{ $payment->bill->balance > 0 ? 'Partially Paid - Balance Due: ' . number_format($payment->bill->balance, 2) : 'Fully Paid' }}
                        </div>
                    @endif

                    <div class="text-center text-muted small mt-4 pt-3 border-top">
                        <p class="mb-1">This receipt is proof of payment. Please keep it for your records.</p>
                        <p class="mb-0">Recorded by: <strong>{{ $payment->recordedBy->name }}</strong></p>
                        <p class="mb-0">Receipt Date: <strong>{{ $receiptDate }}</strong></p>
                    </div>
                </div>
            </div>

            <div class="mt-3 text-center d-print-none">
                @if($payment->bill)
                    <a href="{{ route('accountant.bills.show', $payment->bill) }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-file-earmark"></i> View Full Bill
                    </a>
                @endif

                @if($payment->bill && $payment->bill->patientVisit)
                    <a href="{{ route('accountant.patient-payment-history', $payment->bill->patientVisit->patient) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-history"></i> Payment History
                    </a>
                @endif
            </div>

            <div class="card shadow-sm border-secondary mt-4 d-print-none" id="thermal-receipt-preview-card">
                <div class="card-header bg-secondary text-white py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Thermal Receipt Preview</h6>
                            <small class="text-white-50">Review before printing</small>
                        </div>
                        <button type="button" onclick="printThermalReceipt()" class="btn btn-sm btn-light">
                            <i class="bi bi-printer-fill"></i> Print Thermal
                        </button>
                    </div>
                </div>
                <div class="card-body p-1">
                    <div class="thermal-receipt-preview">
                        <div class="text-center mb-3">
                            <h5 class="mb-1">{{ strtoupper(config('app.title')) ?? 'FATIMA YAHAYA HOSPITAL, SIFAWA' }}</h5>
                            <h6>{{strtoupper(config('app.address')) ?? 'No 5. Birnin Kebbi Road, Sifawa, Bodinga LG, Sokoto State'}}</h6>
                            <p class="small mb-1">PAYMENT RECEIPT</p>
                            <div class="divider"></div>
                        </div>

                        <div class="mb-2 small">
                            <p class="mb-1"><strong>Receipt:</strong> {{ $payment->payment_id }}</p>
                            <p class="mb-1"><strong>Date:</strong> {{ $receiptDate }}</p>
                            <p class="mb-1"><strong>Patient:</strong> {{ $patientName }}</p>
                            <p class="mb-1"><strong>Hospital No:</strong> {{ $hospitalNumber }}</p>
                            <p class="mb-1"><strong>Method:</strong> {{ $payment->paymentMethod->name }}</p>
                            <p class="mb-0"><strong>Bill Number #:</strong> {{ $payment->bill->bill_number ?? 'N/A' }}</p>
                        </div>

                        <div class="divider"></div>
                        <table class="w-100">
                            @foreach($payment->bill->serviceRequests as $serviceRequest)
                                <tr>
                                    <td style="width:65%;">{{ Str::limit($serviceRequest->service->name, 20) }}</td>
                                    <td class="text-end">{{ number_format($serviceRequest->service->price, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="small text-muted">Status: {{ $serviceRequest->payment_status }}</td>
                                    <td></td>
                                </tr>
                            @endforeach
                            @foreach($payment->bill->investigationRequests as $investigationRequest)
                                <tr>
                                    <td>{{ Str::limit($investigationRequest->investigation->name, 20) }}</td>
                                    <td class="text-end">{{ number_format($investigationRequest->investigation->price, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="small text-muted">Status: {{ $investigationRequest->payment_status }}</td>
                                    <td></td>
                                </tr>
                            @endforeach
                        </table>
                        <div class="divider"></div>

                        <p class="mb-1"><strong>Total Paid:</strong> {{ number_format($payment->amount, 2) }}</p>
                        @if($payment->bill)
                            <p class="mb-1"><strong>Bill Status:</strong> {{ $payment->bill->balance > 0 ? 'Balance: ' . number_format($payment->bill->balance, 2) : 'Fully Paid' }}</p>
                        @endif
                        <div class="divider"></div>
                        <p class="small mb-0">Recorded by: {{ $payment->recordedBy->name }}</p>
                        <p class="small mb-0">Thank you for your payment.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Print-only receipt copies for A4 page --}}
<div id="receipt-print-copies" class="d-none">
    <div class="receipt-copy">
        @include('accountant.payments.partials.receipt-content', ['copyIndex' => 1])
    </div>
    <div class="receipt-copy">
        @include('accountant.payments.partials.receipt-content', ['copyIndex' => 2])
    </div>
</div>

{{-- Thermal receipt template --}}
<div id="thermal-receipt-template" class="d-none">
    <div class="thermal-receipt">
        <div class="text-center">
            <h5 class="mb-1">{{ strtoupper(config('app.title')) ?? 'FATIMA YAHAYA HOSPITAL, SIFAWA' }}</h5>
            <h6>{{strtoupper(config('app.address')) ?? 'No 5. Birnin Kebbi Road, Sifawa, Bodinga LG, Sokoto State'}}</h6>
            <p class="small mb-1">PAYMENT RECEIPT</p>
            <div class="divider"></div>
        </div>

        <p><strong>Receipt:</strong> {{ $payment->payment_id }}</p>
        <p><strong>Date:</strong> {{ $receiptDate }}</p>
        <p><strong>Patient:</strong> {{ $patientName }}</p>
        <p><strong>Hospital No:</strong> {{ $hospitalNumber }}</p>
        <p><strong>Method:</strong> {{ $payment->paymentMethod->name }}</p>
        <p><strong>Bill No #:</strong> {{ $payment->bill->bill_number ?? 'N/A' }}</p>

        <div class="divider"></div>
        <table>
            @foreach($payment->bill->serviceRequests as $serviceRequest)
                <tr>
                    <td style="width:65%;">{{ Str::limit($serviceRequest->service->name, 20) }}</td>
                    <td class="text-right">{{ number_format($serviceRequest->service->price, 2) }}</td>
                </tr>
                <tr>
                    <td class="small text-muted">Status: {{ $serviceRequest->payment_status }}</td>
                    <td></td>
                </tr>
            @endforeach
            @foreach($payment->bill->investigationRequests as $investigationRequest)
                <tr>
                    <td>{{ Str::limit($investigationRequest->investigation->name, 20) }}</td>
                    <td class="text-right">{{ number_format($investigationRequest->investigation->price, 2) }}</td>
                </tr>
                <tr>
                    <td class="small text-muted">Status: {{ $investigationRequest->payment_status }}</td>
                    <td></td>
                </tr>
            @endforeach
        </table>
        <div class="divider"></div>

        <p><strong>Total Paid:</strong> {{ number_format($payment->amount, 2) }}</p>
        @if($payment->bill)
            <p><strong>Bill Status:</strong> {{ $payment->bill->balance > 0 ? 'Balance: ' . number_format($payment->bill->balance, 2) : 'Fully Paid' }}</p>
        @endif

        <div class="divider"></div>
        <p class="small" style="margin-bottom:0;">Recorded by: {{ $payment->recordedBy->name }}</p>
        <p class="small" style="margin-top:0;">Thank you for your patronage.</p>
    </div>
</div>

<style>
    @media print {
        body {
            background: white !important;
        }

        .d-print-none,
        .btn,
        .alert,
        .card-footer,
        .mt-3,
        #receipt-preview {
            display: none !important;
        }

        #receipt-print-copies {
            display: grid !important;
            width: 100%;
            grid-template-rows: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .receipt-copy {
            page-break-inside: avoid;
            break-inside: avoid;
            page-break-after: avoid;
            border: 1px solid #ccc;
            padding: 16px;
            width: 100%;
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }
    }

    @media screen {
        .thermal-receipt {
            font-family: monospace;
        }
    }

    .thermal-receipt .divider,
    .thermal-receipt-preview .divider {
        border-top: 1px dashed #000;
        margin: 8px 0;
    }

    .thermal-receipt table,
    .thermal-receipt-preview table {
        width: 100%;
        border-collapse: collapse;
    }

    .thermal-receipt td,
    .thermal-receipt-preview td {
        padding: 2px 0;
    }

    .thermal-receipt .text-right,
    .thermal-receipt-preview .text-right {
        text-align: right;
    }

    .thermal-receipt-preview p {
        margin-bottom: 0.25rem;
    }
</style>

<script>
    function printThermalReceipt() {
        var template = document.getElementById('thermal-receipt-template');
        if (!template) {
            return alert('Thermal receipt template not found.');
        }

        var newWindow = window.open('', '_blank', 'width=360,height=640');
        if (!newWindow) {
            return alert('Please allow popups to print the thermal receipt.');
        }

        newWindow.document.write('<html><head><title>Thermal Receipt</title>');
        newWindow.document.write('<style>body{margin:8px;font-family:monospace;color:#000;} .divider{border-top:1px dashed #000;margin:8px 0;} table{width:100%;border-collapse:collapse;} td{padding:2px 0;} .text-right{text-align:right;}</style>');
        newWindow.document.write('</head><body>');
        newWindow.document.write(template.innerHTML);
        newWindow.document.write('</body></html>');
        newWindow.document.close();
        newWindow.focus();

        setTimeout(function() {
            newWindow.print();
            newWindow.close();
        }, 300);
    }
</script>
@endsection

