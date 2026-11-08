@extends('layouts.modern')

@section('title', 'Index.Blade')
@section('page-title', 'Index.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Permissions')

@section('header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="h3 d-flex align-items-center mb-0">
        <i class="bi bi-key-fill me-2 text-success"></i>
        Manage Permissions
    </h1>
    <a href="{{ route('admin.permissions.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i>
        New Permission
    </a>
</div>
@endsection

@section('content')

@if ($message = Session::get('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 datatable">
            <thead class="table-light">
                <tr>
                    <th>Permission Name</th>
                    <th>Description</th>
                    <th>Assigned to Roles</th>
                    <th class="no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($permissions as $permission)
                    <tr>
                        <td>
                            <span class="badge bg-success">{{ $permission->name }}</span>
                        </td>
                        <td>{{ $permission->description ?? '-' }}</td>
                        <td>
                            <small>
                                @forelse ($permission->roles as $role)
                                    <span class="badge bg-light text-dark">{{ $role->name }}</span>
                                @empty
                                    <span class="text-muted">Not assigned</span>
                                @endforelse
                            </small>
                        </td>
                        <td>
                            <a href="{{ route('admin.permissions.edit', $permission->id) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.permissions.destroy', $permission->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this permission?');">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No permissions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $permissions->links() }}
</div>
@endsection

