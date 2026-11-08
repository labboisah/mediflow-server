@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('title', 'Record Revenue')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-4">
                        <i class="bi bi-cash-coins"></i>
                        Record Hospital Revenue
                    </h4>

                    <form method="POST" action="{{ route('admin.revenues.store') }}">
                        @csrf

                        <div class="row gy-3">
                            <div class="col-md-6">
                                <label class="form-label">Revenue Category <span class="text-danger">*</span></label>
                                <select name="revenue_category_id" class="form-control" required>
                                    <option value="">Select a category...</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('revenue_category_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Department (Optional)</label>
                                <select name="department_id" class="form-control">
                                    <option value="">General / All Departments</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                                @error('title')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                                @error('amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="revenue_date" class="form-control" value="{{ old('revenue_date', date('Y-m-d')) }}" required>
                                @error('revenue_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Reference Number (Optional)</label>
                                <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}" placeholder="e.g., Invoice #, Receipt #">
                                @error('reference_number')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description (Optional)</label>
                                <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Record Revenue
                            </button>
                            <a href="{{ route('admin.revenues.index') }}" class="btn btn-secondary ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

