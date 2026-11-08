@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('title', 'Daily Activity Reports')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">User Activity Reports</h1>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            {{-- Report Generation Form --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Generate Report</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('reports.generate') }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-md-2">
                            <label for="date" class="form-label">Report Date</label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror"
                                   id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3">
                            <label for="from_date" class="form-label">From Date</label>
                            <input type="date" class="form-control @error('from_date') is-invalid @enderror"
                                   id="from_date" name="from_date" value="{{ old('from_date') }}">
                            @error('from_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3">
                            <label for="to_date" class="form-label">To Date</label>
                            <input type="date" class="form-control @error('to_date') is-invalid @enderror"
                                   id="to_date" name="to_date" value="{{ old('to_date') }}">
                            @error('to_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-file-earmark-arrow-down"></i> Generate Report
                            </button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
