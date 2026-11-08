@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Payment History</h1>
        <a href="{{ route('accountant.bills.payments.verify') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Record New Payment
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">All Payments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Patient</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Bill Number</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Recorded By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>
                                    <strong>{{ $payment->payment_id }}</strong>
                                </td>
                                @if($payment->bill &&$payment->bill->walkinPatient)
                                    <td>{{ $payment->bill->walkinPatient->name}} </td>
                                @else
                                    <td>{{ $payment->bill->patientVisit->demographic->first_name ?? ''}} {{ $payment->bill->patientVisit->demographic->last_name ?? ''}}</td>
                                @endif
                                
                                <td class="fw-bold text-success">{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->paymentMethod->name }}</td>
                                <td>{{ $payment->bill->bill_number ?? '-' }}</td>
                                <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                <td>
                                    @if($payment->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($payment->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($payment->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">Reversed</span>
                                    @endif
                                </td>
                                <td>{{ $payment->recordedBy->name }}</td>
                                <td></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No payments recorded. <a href="{{ route('accountant.bills.payments.verify') }}">Record one now</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    @if($payments->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection

