@extends('layouts.modern')

@section('title', 'Edit.Blade')
@section('page-title', 'Edit.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Edit Permission: ' . $permission->name)

@section('header')
<h1 class="h3 d-flex align-items-center mb-0">
    <i class="bi bi-pencil me-2 text-info"></i>
    Edit Permission: <span class="badge bg-success ms-2">{{ $permission->name }}</span>
</h1>
@endsection

@section('content')
<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('admin.permissions.update', $permission->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Permission Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $permission->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $permission->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-check-circle me-1"></i>
                            Update Permission
                        </button>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

