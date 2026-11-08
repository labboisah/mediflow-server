{{-- Accountant-Specific Report Content --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="bi bi-calculator"></i> Accountant Activity Report</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Payments Recorded</h6>
                    <h3 class="mb-0 text-success">{{ $reportData['payments_recorded'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Bills Created</h6>
                    <h3 class="mb-0 text-info">{{ $reportData['bills_created'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Total Payments</h6>
                    <h3 class="mb-0 text-success">{{ $reportData['total_payment_amount'] ?? '0' }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Total Bills</h6>
                    <h3 class="mb-0 text-info">{{ $reportData['total_bill_amount'] ?? '0' }}</h3>
                </div>
            </div>
        </div>

        {{-- Payments Table --}}
        @if(!empty($reportData['payments_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-cash-coin"></i> Payments Recorded</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['payments_details'] ?? [] as $payment)
                                <tr>
                                    <td>{{ $payment['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $payment['amount'] ?? '0' }}</td>
                                    <td>{{ $payment['payment_method'] ?? 'N/A' }}</td>
                                    <td><span class="badge bg-success">{{ $payment['status'] ?? 'Completed' }}</span></td>
                                    <td>{{ $payment['time'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No payments recorded</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Bills Table --}}
        @if(!empty($reportData['bills_details']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-receipt"></i> Bills Created</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Bill Amount</th>
                                <th>Status</th>
                                <th>Date Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['bills_details'] ?? [] as $bill)
                                <tr>
                                    <td>{{ $bill['patient_name'] ?? 'N/A' }}</td>
                                    <td>{{ $bill['amount'] ?? '0' }}</td>
                                    <td>
                                        @switch($bill['status'] ?? 'pending')
                                            @case('paid')
                                                <span class="badge bg-success">Paid</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-danger">Cancelled</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>{{ $bill['date_created'] ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No bills created</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>