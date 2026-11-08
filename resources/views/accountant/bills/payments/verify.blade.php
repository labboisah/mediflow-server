@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() erify. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() erify. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="h3 mb-4">Verify Bill For Payment</h1>

            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- lets display some information about patient and bill -->
                   
                    <form action="{{ route('accountant.bills.payments.verify-now') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="bill_no" class="form-label">Bill Number <span class="text-danger">*</span></label>
                            <input type="text" id="bill_no" name="bill_no" class="form-control @error('bill_no') is-invalid @enderror" placeholder="BLXXXXXX" value="{{ old('bill_no') }}" required>
                            @error('bill_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                       
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Confirm & Proceed to Payment
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

