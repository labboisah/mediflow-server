@extends('layouts.modern')

@section('title', 'Create.Blade')
@section('page-title', 'Create.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Create Permission')

@section('header')
<h1 class="h3 d-flex align-items-center mb-0">
    <i class="bi bi-plus-circle me-2 text-success"></i>
    Create New Permission
</h1>
@endsection

@section('content')
<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('admin.permissions.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Permission Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="e.g., manage-users" value="{{ old('name') }}" required>
                        <small class="form-text text-muted">Use lowercase with hyphens, e.g., manage-patients, view-records</small>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="What does this permission allow?">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>
                            Create Permission
                        </button>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

