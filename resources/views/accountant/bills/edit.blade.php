@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() dit. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() dit. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="h3 mb-4">Edit Bill</h1>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('accountant.bills.update', $bill) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="alert alert-info">
                            Patient:
                            <strong>
                                {{ $bill->walkinPatient?->name ?? $bill->patientVisit?->patient?->demographic?->full_name ?? 'N/A' }}
                            </strong>
                            <span class="text-muted">
                                {{ $bill->walkinPatient ? '(Walk-in)' : '(' . ($bill->patientVisit?->patient?->hospital_number ?? 'No hospital number') . ')' }}
                            </span>
                        </div>

                        <div class="mb-3">
                            <label for="service_description" class="form-label">Service Description <span class="text-danger">*</span></label>
                            <textarea id="service_description" name="service_description" class="form-control @error('service_description') is-invalid @enderror" rows="4" required>{{ old('service_description', $bill->service_description) }}</textarea>
                            <small class="text-muted">Describe the service(s) rendered</small>
                            @error('service_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror" placeholder="0.00" step="0.01" value="{{ old('amount', $bill->amount) }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="discount" class="form-label">Discount (%)</label>
                            <select id="discount" name="discount" class="form-control @error('discount') is-invalid @enderror">
                                @for($percent = 0; $percent <= 100; $percent++)
                                    <option value="{{ $percent }}" @selected(old('discount', $bill->discount) == $percent)>{{ $percent }} %</option>
                                @endfor
                            </select>
                            @error('discount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="issued_date" class="form-label">Issued Date <span class="text-danger">*</span></label>
                                    <input type="date" id="issued_date" name="issued_date" class="form-control @error('issued_date') is-invalid @enderror" value="{{ old('issued_date', $bill->issued_date->format('Y-m-d')) }}" required>
                                    @error('issued_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $bill->due_date->format('Y-m-d')) }}" required>
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required>
                                <option value="pending" @selected($bill->status === 'pending')>Pending</option>
                                <option value="partial" @selected($bill->status === 'partial')>Partial</option>
                                <option value="paid" @selected($bill->status === 'paid')>Paid</option>
                                <option value="cancelled" @selected($bill->status === 'cancelled')>Cancelled</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Bill
                            </button>
                            <a href="{{ route('accountant.bills.show', $bill) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

