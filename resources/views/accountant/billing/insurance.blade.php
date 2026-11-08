@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() nsurance. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() nsurance. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Insurance Billing Management</h1>

    {{-- Insurance Statistics --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">NHIS Claims Processed</p>
                    <h3 class="text-primary">{{ number_format($nhisPayments, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">Private Insurance Claims</p>
                    <h3 class="text-info">{{ number_format($privateInsurancePayments, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">Total Insurance Billing</p>
                    <h3 class="text-success">{{ number_format($nhisPayments + $privateInsurancePayments, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Insurance & NHIS Claims</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Patient</th>
                            <th>Insurance Provider</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Reference #</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($insurancePayments as $payment)
                            <tr>
                                <td><strong>{{ $payment->payment_id }}</strong></td>
                                <td>{{ $payment->patient->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $payment->insurance_provider === 'NHIS' ? 'primary' : 'info' }}">
                                        {{ $payment->insurance_provider }}
                                    </span>
                                </td>
                                <td>{{ $payment->payment_method }}</td>
                                <td class="fw-bold text-success">{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->reference_number ?? '-' }}</td>
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    No insurance claims recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    @if($insurancePayments->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $insurancePayments->links() }}
        </div>
    @endif
</div>
@endsection

