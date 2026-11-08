@extends('layouts.modern')

@section('title', 'Index.Blade')
@section('page-title', 'Index.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Admin Dashboard')

@section('header')
<h1 class="h3 d-flex align-items-center">
    <i class="bi bi-shield-lock-fill me-2 text-danger"></i>
    Admin Panel
</h1>
@endsection

@section('content')

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase small text-muted">Total Roles</div>
                        <h3 class="mb-0">{{ $rolesCount }}</h3>
                    </div>
                    <div class="text-primary fs-3"><i class="bi bi-briefcase-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase small text-muted">Total Permissions</div>
                        <h3 class="mb-0">{{ $permissionsCount }}</h3>
                    </div>
                    <div class="text-success fs-3"><i class="bi bi-key-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase small text-muted">Total Users</div>
                        <h3 class="mb-0">{{ $usersCount }}</h3>
                    </div>
                    <div class="text-warning fs-3"><i class="bi bi-people-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase small text-muted">Active Temp Permissions</div>
                        <h3 class="mb-0">{{ $activeTempPermissionsCount }}</h3>
                    </div>
                    <div class="text-info fs-3"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center">
                    <i class="bi bi-briefcase-fill me-2 text-primary"></i>
                    Manage Roles
                </h5>
                <p class="text-muted small">Define roles and assign permissions</p>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-primary btn-sm">Go to Roles</a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center">
                    <i class="bi bi-key-fill me-2 text-success"></i>
                    Manage Permissions
                </h5>
                <p class="text-muted small">Create and manage system permissions</p>
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-success btn-sm">Go to Permissions</a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center">
                    <i class="bi bi-people-fill me-2 text-warning"></i>
                    Manage Users
                </h5>
                <p class="text-muted small">Assign roles to users</p>
                <a href="{{ route('admin.users.index') }}" class="btn btn-warning btn-sm">Go to Users</a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center">
                    <i class="bi bi-clock-history me-2 text-info"></i>
                    Temporary Permissions
                </h5>
                <p class="text-muted small">Grant temporary access (auto-expires)</p>
                <a href="{{ route('admin.temporary-permissions.index') }}" class="btn btn-info btn-sm">Manage</a>
            </div>
        </div>
    </div>
</div>

@endsection

