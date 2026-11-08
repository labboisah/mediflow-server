@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="h3 mb-4">Record New Payment</h1>

            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- lets display some information about patient and bill -->
                    @if($patient)
                        <div class="alert alert-info mb-3">
                            <strong>Patient:</strong> {{ $patient->demographic->first_name }} {{ $patient->demographic->last_name }}<br>
                            <strong>Amount:</strong> {{ number_format($patient->payment()['total'], 2) }}<br>
                            <strong>Balance Due:</strong> {{ number_format($patient->payment()['pending'], 2) }}
                        </div>
                    @endif
                    <form action="{{ route('accountant.payments.store') }}" method="POST">
                        @csrf

                        <input type="hidden" value="{{$patient->id}}" name='patient_id'>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror" placeholder="0.00" step="0.01" min="0.01" max="{{ max(0, $patient->payment()['pending'] ?? 0) }}" value="{{ old('amount', max(0, $patient->payment()['pending'] ?? 0)) }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <input type="hidden" name="bill_id" value="{{ $bill->id ?? '' }}">
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select id="payment_method" name="payment_method" class="form-control @error('payment_method') is-invalid @enderror" required onchange="toggleInsuranceProvider()">
                                <option value="">-- Select Payment Method --</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}" @selected(old('payment_method') == $method)>{{ $method->name }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" id="insurance_provider_div" style="display: none;">
                            <label for="insurance_provider" class="form-label">Insurance Provider</label>
                            <input type="text" id="insurance_provider" name="insurance_provider" class="form-control @error('insurance_provider') is-invalid @enderror" placeholder="e.g., NHIS, Allianz, etc." value="{{ old('insurance_provider') }}">
                            <small class="text-muted">Name of the insurance provider</small>
                            @error('insurance_provider')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        

                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" id="payment_date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Record Payment
                            </button>
                            <a href="{{ route('accountant.payments.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleInsuranceProvider() {
        const method = document.getElementById('payment_method').value;
        const insuranceDiv = document.getElementById('insurance_provider_div');
        
        if (method === 'NHIS' || method === 'Private Insurance') {
            insuranceDiv.style.display = 'block';
        } else {
            insuranceDiv.style.display = 'none';
        }
    }

    function updateBillAmount() {
        const billSelect = document.getElementById('bill_id');
        const selectedOption = billSelect.options[billSelect.selectedIndex];
        const amount = parseFloat(selectedOption.dataset.amount);
        
        if (!isNaN(amount) && amount > 0) {
            document.getElementById('amount').value = amount.toFixed(2);
        } else {
            document.getElementById('amount').value = '';
        }
    }

    function updateOutstandingBills() {
        // This could be enhanced with AJAX to fetch bills for selected patient
        const patientId = document.getElementById('patient_id').value;
        // Placeholder for future AJAX implementation
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleInsuranceProvider();
        
        // Add event listener for bill selection changes
        const billSelect = document.getElementById('bill_id');
        billSelect.addEventListener('change', updateBillAmount);
        
        // Initialize amount if a bill is already selected (from query parameter)
        if (billSelect.value) {
            updateBillAmount();
        }
    });
</script>
@endsection

