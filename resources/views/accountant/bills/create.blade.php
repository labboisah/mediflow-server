@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h1 class="h3 mb-4">Create New Bill</h1>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('accountant.bills.store') }}" method="POST" id="billForm">
                        @csrf
                        <div class="row">
                            <!-- hosppitan no input -->
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="hospital_number" class="form-label">Hospital Number</label>
                                    <input type="text" id="hospital_number" name="hospital_number" class="form-control" value="{{ old('hospital_number') }}" placeholder="Enter hospital number">
                                </div>
                            </div>

                            <!-- name, phone, and address for walkin patient-->
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="walkin_name" class="form-label">Name</label>
                                    <input type="text" id="walkin_name" name="walkin_name" class="form-control" value="{{ old('walkin_name') }}" placeholder="Enter name">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="walkin_phone" class="form-label">Phone</label>
                                    <input type="text" id="walkin_phone" name="walkin_phone" class="form-control" value="{{ old('walkin_phone') }}" placeholder="Enter phone number">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="walkin_email" class="form-label">Email</label>
                                    <input type="email" id="walkin_email" name="walkin_email" class="form-control" value="{{ old('walkin_email') }}" placeholder="Enter email">
                                </div>
                            </div>

                        </div>
                        
                        <div class="row">   
                            <!-- services and investigations -->
                            <div class="col-md-7">
                                {{-- Services Selection --}}
                                <div class="mb-4">
                                    <label class="form-label mb-3">Select Services <span class="text-danger">*</span></label>

                                    <div id="services-container">
                                        <div class="service-row mb-3 row g-2" data-service-index="0">
                                            <div class="col-md-6">
                                                <select class="form-control service-select @error('services.0.id') is-invalid @enderror" name="services[0][id]" onchange="calculateTotal()">
                                                    <option value="">-- Select Service --</option>
                                                    @foreach($services as $category => $categoryServices)
                                                        <optgroup label="{{ $category }}">
                                                            @foreach($categoryServices as $service)
                                                                <option value="{{ $service->id }}" data-price="{{ $service->price }}" data-name="{{ $service->name }}">
                                                                    {{ $service->name }} - <span class="fas fa-naira-sign"></span> {{ number_format($service->price, 2) }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                                @error('services.0.id')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" min="1" step="1" class="form-control quantity-input" name="services[0][quantity]" value="1" onchange="calculateTotal()" />
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-danger btn-sm remove-service" onclick="this.closest('.service-row').remove(); calculateTotal();" style="display: none;">
                                                    <i class="bi bi-trash"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-service-btn" onclick="addServiceRow()">
                                        <i class="bi bi-plus-circle"></i> Service
                                    </button>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label mb-3">Select Investigations</label>

                                    <div id="investigations-container">
                                        
                                    </div>

                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-investigation-btn" onclick="addInvestigationRow()">
                                        <i class="bi bi-plus-circle"></i> Investigation
                                    </button>
                                </div>
                                
                            </div>
                            <!-- calculator -->
                             <div class="col-md-5">
                                     {{-- Bill Summary Table --}}
                                <div id="summary-section" style="display: none;" class="mb-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3">Bill Summary</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Item</th>
                                                            <th class="text-end">Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="summary-tbody">
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="fw-bold border-top">
                                                            <td>Total Amount Due:</td>
                                                            <td class="text-end fs-5 text-success">
                                                                <span class="fas fa-naira-sign"></span> <span id="total-amount">0.00</span>
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Apply Discount</label>
                                    <select name="discount" id="discount" class="form-control @error('discount') is-invalid @enderror" onchange="calculateTotal()">
                                        @for($percent = 0; $percent <= 100; $percent++)
                                            <option value="{{$percent}}" @selected(old('discount', 0) == $percent)>{{$percent}} %</option>
                                        @endfor
                                    </select>
                                    @error('discount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="issued_date" class="form-label">Issued Date <span class="text-danger">*</span></label>
                                    <input type="date" id="issued_date" name="issued_date" class="form-control @error('issued_date') is-invalid @enderror" value="{{ old('issued_date', date('Y-m-d')) }}" required>
                                    @error('issued_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', date('Y-m-d', strtotime('+5 days'))) }}" required>
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                                <i class="bi bi-check-circle"></i> Create Bill
                            </button>
                            <a href="{{ route('accountant.bills.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                            </div>
                            
                        </div>
                       
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let serviceIndex = 0;
    let investigationIndex = 0;

    function addServiceRow() {
        serviceIndex++;
        const html = `
            <div class="service-row mb-3 row g-2" data-service-index="${serviceIndex}">
                <div class="col-md-8">
                    <select class="form-control service-select" name="services[${serviceIndex}][id]" required onchange="calculateTotal()">
                        <option value="">-- Select Service --</option>
                        @foreach($services as $category => $categoryServices)
                            <optgroup label="{{ $category }}">
                                @foreach($categoryServices as $service)
                                    <option value="{{ $service->id }}" data-price="{{ $service->price }}" data-name="{{ $service->name }}">
                                        {{ $service->name }} - <span class="fas fa-naira-sign"></span> {{ number_format($service->price, 2) }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" min="1" step="1" class="form-control quantity-input" name="services[${serviceIndex}][quantity]" value="1" onchange="calculateTotal()" />
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-danger btn-sm remove-service" onclick="this.closest('.service-row').remove(); calculateTotal();">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
            </div>
        `;
        
        document.getElementById('services-container').insertAdjacentHTML('beforeend', html);
        calculateTotal();
    }

    function addInvestigationRow() {
        investigationIndex++;
        const html = `
            <div class="investigation-row mb-3 row g-2" data-investigation-index="${investigationIndex}">
                <div class="col-md-8">
                    <select class="form-control investigation-select" name="investigations[${investigationIndex}][id]" required onchange="calculateTotal()">
                        <option value="">-- Select Investigation --</option>
                        @foreach($investigations as $category => $categoryInvestigations)
                            <optgroup label="{{ $category }}">
                                @foreach($categoryInvestigations as $investigation)
                                    <option value="{{ $investigation->id }}" data-price="{{ $investigation->price }}" data-name="{{ $investigation->name }}">
                                        {{ $investigation->name }} - <span class="fas fa-naira-sign"></span> {{ number_format($investigation->price, 2) }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" min="1" step="1" class="form-control quantity-input" name="investigations[${investigationIndex}][quantity]" value="1" onchange="calculateTotal()" />
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-danger btn-sm remove-investigation" onclick="this.closest('.investigation-row').remove(); calculateTotal();">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
            </div>
        `;
        document.getElementById('investigations-container').insertAdjacentHTML('beforeend', html);
        calculateTotal();
    }

    function calculateTotal() {
        const serviceRows = document.querySelectorAll('.service-row');
        const investigationRows = document.querySelectorAll('.investigation-row');
        let totalAmount = 0;
        let summaryHtml = '';
        let hasItems = false;

        serviceRows.forEach(row => {
            const select = row.querySelector('.service-select');
            const quantityInput = row.querySelector('.quantity-input');
            const quantity = Math.max(1, parseInt(quantityInput?.value || '1', 10));

            if (select && select.value) {
                hasItems = true;
                const option = select.options[select.selectedIndex];
                const price = parseFloat(option.dataset.price);
                const lineTotal = price * quantity;
                totalAmount += lineTotal;
                const itemName = option.dataset.name || option.text.split(' - ')[0];

                summaryHtml += `
                    <tr>
                        <td>${itemName}${quantity > 1 ? ` × ${quantity}` : ''}</td>
                        <td class="text-end fw-bold"><span class="fas fa-naira-sign"></span> ${lineTotal.toFixed(2)}</td>
                    </tr>
                `;
            }
        });

        investigationRows.forEach(row => {
            const select = row.querySelector('.investigation-select');
            const quantityInput = row.querySelector('.quantity-input');
            const quantity = Math.max(1, parseInt(quantityInput?.value || '1', 10));

            if (select && select.value) {
                hasItems = true;
                const option = select.options[select.selectedIndex];
                const price = parseFloat(option.dataset.price);
                const lineTotal = price * quantity;
                totalAmount += lineTotal;
                const itemName = option.dataset.name || option.text.split(' - ')[0];

                summaryHtml += `
                    <tr>
                        <td>${itemName}${quantity > 1 ? ` × ${quantity}` : ''} (Investigation)</td>
                        <td class="text-end fw-bold"><span class="fas fa-naira-sign"></span> ${lineTotal.toFixed(2)}</td>
                    </tr>
                `;
            }
        });

        const discountSelect = document.querySelector('select[name="discount"]');
        const discountPercent = discountSelect ? parseFloat(discountSelect.value) : 0;
        const discountAmount = totalAmount * (discountPercent / 100);
        const payableAmount = totalAmount - discountAmount;

        if (discountPercent > 0) {
            summaryHtml += `
                <tr class="fw-bold border-top">
                    <td>Discount (${discountPercent.toFixed(0)}%)</td>
                    <td class="text-end text-danger">-<span class="fas fa-naira-sign"></span> ${discountAmount.toFixed(2)}</td>
                </tr>
            `;
        }

        document.getElementById('summary-tbody').innerHTML = summaryHtml;
        document.getElementById('total-amount').textContent = payableAmount.toFixed(2);
        
        const summarySection = document.getElementById('summary-section');
        const submitBtn = document.getElementById('submit-btn');
        
        if (hasItems) {
            summarySection.style.display = 'block';
            submitBtn.disabled = false;
        } else {
            summarySection.style.display = 'none';
            submitBtn.disabled = true;
        }

        const serviceButtons = document.querySelectorAll('.remove-service');
        if (serviceButtons.length > 0) {
            serviceButtons.forEach((btn) => {
                btn.style.display = (serviceButtons.length > 1) ? 'block' : 'none';
            });
        }

        const investigationButtons = document.querySelectorAll('.remove-investigation');
        if (investigationButtons.length > 0) {
            investigationButtons.forEach((btn) => {
                btn.style.display = 'block';
            });
        }
    }

    const patientLookupUrl = '{{ route('accountant.bills.patient-details') }}';
    let patientLookupTimer = null;

    function updatePatientFeedback(message, isError = false) {
        const feedback = document.getElementById('patient-lookup-feedback');
        if (!feedback) {
            return;
        }
        feedback.textContent = message;
        feedback.classList.toggle('text-danger', isError);
        feedback.classList.toggle('text-muted', !isError);
    }

    function populateWalkinFields(data) {
        document.getElementById('walkin_name').value = data.name || '';
        document.getElementById('walkin_phone').value = data.phone || '';
        document.getElementById('walkin_email').value = data.email || '';
    }

    function resetWalkinFields() {
        document.getElementById('walkin_name').value = '';
        document.getElementById('walkin_phone').value = '';
        document.getElementById('walkin_email').value = '';
    }

    async function fetchPatientDetails(hospitalNumber) {
        if (!hospitalNumber) {
            updatePatientFeedback('Enter hospital number to load registered patient details.');
            resetWalkinFields();
            return;
        }

        try {
            const response = await fetch(`${patientLookupUrl}?hospital_number=${encodeURIComponent(hospitalNumber)}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                updatePatientFeedback('Unable to look up patient details right now.', true);
                return;
            }

            const result = await response.json();
            if (result.found) {
                populateWalkinFields(result);
                updatePatientFeedback('Registered patient found. Details populated for billing.');
            } else {
                updatePatientFeedback('No registered patient found. Use walk-in details.');
                resetWalkinFields();
            }
        } catch (error) {
            updatePatientFeedback('Patient lookup failed. Check your connection.', true);
            console.error(error);
        }
    }

    document.getElementById('hospital_number').addEventListener('input', function() {
        clearTimeout(patientLookupTimer);
        const hospitalNumber = this.value.trim();
        if (hospitalNumber.length === 0) {
            updatePatientFeedback('Enter hospital number to load registered patient details.');
            resetWalkinFields();
            return;
        }
        patientLookupTimer = setTimeout(() => fetchPatientDetails(hospitalNumber), 600);
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        calculateTotal();
        
        // Add event listeners to initial select
        document.querySelector('.service-select').addEventListener('change', calculateTotal);
        const discountField = document.querySelector('select[name="discount"]');
        if (discountField) {
            discountField.addEventListener('change', calculateTotal);
        }
    });
</script>
@endsection

