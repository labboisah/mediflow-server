@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() dit. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() dit. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('title', 'Edit Revenue')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-4">
                        <i class="bi bi-pencil"></i>
                        Edit Revenue Record
                    </h4>

                    <form method="POST" action="{{ route('admin.revenues.update', $revenue) }}">
                        @csrf
                        @method('PUT')

                        <div class="row gy-3">
                            <div class="col-md-6">
                                <label class="form-label">Revenue Category <span class="text-danger">*</span></label>
                                <select name="revenue_category_id" class="form-control" required>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected($revenue->revenue_category_id == $category->id)>
                                            {{ $category->name }}
                                        </option>
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
                                        <option value="{{ $department->id }}" @selected($revenue->department_id == $department->id)>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $revenue->title) }}" required>
                                @error('title')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $revenue->amount) }}" required>
                                @error('amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="revenue_date" class="form-control" value="{{ old('revenue_date', $revenue->revenue_date) }}" required>
                                @error('revenue_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Reference Number (Optional)</label>
                                <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number', $revenue->reference_number) }}" placeholder="e.g., Invoice #, Receipt #">
                                @error('reference_number')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description (Optional)</label>
                                <textarea name="description" class="form-control" rows="4">{{ old('description', $revenue->description) }}</textarea>
                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Revenue
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

