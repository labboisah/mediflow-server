<div class="receipt-card">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h6 class="mb-1">Payment Receipt</h6>
            <small>Payment ID: <strong>{{ $payment->payment_id }}</strong></small>
        </div>
        <div class="text-end">
            <i class="bi bi-receipt" style="font-size:1.6rem;"></i>
        </div>
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between">
            <div>
                <p class="mb-1 text-muted small">Patient Name</p>
                <p class="mb-0 fw-bold">{{ $patientName }}</p>
            </div>
            <div>
                <p class="mb-1 text-muted small">Hospital Number</p>
                <p class="mb-0 fw-bold">{{ $hospitalNumber }}</p>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-3">
        <div>
            <p class="mb-1 text-muted small">Payment Date</p>
            <p class="mb-0 fw-bold">{{ $payment->payment_date->format('M d, Y h:i A') }}</p>
        </div>
        <div>
            <p class="mb-1 text-muted small">Status</p>
            <p class="mb-0 fw-bold">{{ ucfirst($payment->status) }}</p>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-3">
        <div>
            <p class="mb-1 text-muted small">Payment Method</p>
            <p class="mb-0 fw-bold">{{ $payment->paymentMethod->name }}</p>
        </div>
        <div>
            <p class="mb-1 text-muted small">Reference #</p>
            <p class="mb-0 fw-bold">{{ $payment->reference_number ?? 'N/A' }}</p>
        </div>
    </div>

    @if($payment->insurance_provider)
        <div class="mb-3">
            <p class="mb-1 text-muted small">Insurance Provider</p>
            <p class="mb-0 fw-bold">{{ $payment->insurance_provider }}</p>
        </div>
    @endif

    @if($payment->bill && ($payment->bill->serviceRequests->count() > 0 || $payment->bill->investigationRequests->count() > 0))
        <div class="mb-3">
            <p class="mb-1 text-muted small">Services & Items Paid For</p>
            <table class="table table-borderless table-sm mb-0 w-100">
                <tbody>
                @foreach($payment->bill->serviceRequests as $serviceRequest)
                    <tr>
                        <td class="small">{{ $serviceRequest->service->name }}</td>
                        <td class="text-end small">{{ number_format($serviceRequest->service->price, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="small text-muted">Status: {{ $serviceRequest->payment_status }}</td>
                        <td></td>
                    </tr>
                @endforeach
                @foreach($payment->bill->investigationRequests as $investigationRequest)
                    <tr>
                        <td class="small">{{ $investigationRequest->investigation->name }}</td>
                        <td class="text-end small">{{ number_format($investigationRequest->investigation->price, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="small text-muted">Status: {{ $investigationRequest->payment_status }}</td>
                        <td></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="d-flex justify-content-between mb-3">
        <p class="mb-1 text-muted small">Amount Paid</p>
        <p class="mb-0 fw-bold">{{ number_format($payment->amount, 2) }}</p>
    </div>

    @if($payment->bill)
        <div class="mb-3">
            <p class="mb-0 small"><strong>Bill Status:</strong> {{ $payment->bill->balance > 0 ? 'Partially Paid - Balance Due: ' . number_format($payment->bill->balance, 2) : 'Fully Paid' }}</p>
        </div>
    @endif

    <div class="text-center text-muted small">
        <p class="mb-1">This receipt is proof of payment. Please keep it for your records.</p>
        <p class="mb-0">Recorded by: <strong>{{ $payment->recordedBy->name }}</strong></p>
        <p class="mb-0">Receipt Date: <strong>{{ $receiptDate }}</strong></p>
    </div>
</div>
