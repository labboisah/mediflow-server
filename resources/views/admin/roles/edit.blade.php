@extends('layouts.modern')

@section('title', 'Edit.Blade')
@section('page-title', 'Edit.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Edit Role: ' . $role->name)

@section('header')
<h1 class="h3 d-flex align-items-center mb-0">
    <i class="bi bi-pencil me-2 text-info"></i>
    Edit Role: <span class="badge bg-primary ms-2">{{ $role->name }}</span>
</h1>
@endsection

@section('content')
<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $role->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $role->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div class="border rounded p-3">
                            @forelse ($permissions as $permission)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="permission_{{ $permission->id }}" name="permissions[]" value="{{ $permission->id }}" 
                                        @if(in_array($permission->id, $rolePermissions)) checked @endif>
                                    <label class="form-check-label" for="permission_{{ $permission->id }}">
                                        {{ $permission->name }}
                                        <small class="text-muted">{{ $permission->description }}</small>
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted">No permissions available.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-check-circle me-1"></i>
                            Update Role
                        </button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

