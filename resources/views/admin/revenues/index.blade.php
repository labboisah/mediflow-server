@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('title', 'Manage Revenue')

@section('header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h3 mb-0">Hospital Revenue</h1>
        <p class="text-muted mb-0">Track all revenue sources including services, donations, grants, and other income.</p>
    </div>
    <a href="{{ route('admin.revenues.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i> Record Revenue
    </a>
</div>
@endsection

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Title</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Reference #</th>
                        <th>Department</th>
                        <th>Recorded By</th>
                        <th>Action</th>
                    </tr>
        </thead>    
                <tbody>
                    @forelse($revenues as $revenue)
                        <tr>
                            <td><span class="badge bg-info">{{ optional($revenue->category)->name }}</span></td>
                            <td>{{ $revenue->title }}</td>
                            <td class="text-end fw-bold">₦ {{ number_format($revenue->amount, 2) }}</td>
                            <td>{{ $revenue->revenue_date }}</td>
                            <td>{{ $revenue->reference_number ?? '-' }}</td>
                            <td>{{ optional($revenue->department)->name ?? 'General' }}</td>
                            <td>{{ optional($revenue->createdBy)->name ?? 'System' }}</td>
                            <td>
                                <a href="{{ route('admin.revenues.edit', $revenue) }}" class="btn btn-warning btn-sm me-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.revenues.destroy', $revenue) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this revenue record?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No revenue recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($revenues->isNotEmpty())
        <div class="row mt-4 pt-3 border-top">
            <div class="col-md-3">
                <div class="stat-box p-3 bg-light rounded">
                    <p class="text-muted small mb-1">Total Revenue</p>
                    <h4 class="mb-0 text-success">₦ {{ number_format($revenues->sum('amount'), 2) }}</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box p-3 bg-light rounded">
                    <p class="text-muted small mb-1">Records Count</p>
                    <h4 class="mb-0">{{ $revenues->count() }}</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box p-3 bg-light rounded">
                    <p class="text-muted small mb-1">Average Revenue</p>
                    <h4 class="mb-0">₦ {{ number_format($revenues->avg('amount'), 2) }}</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box p-3 bg-light rounded">
                    <p class="text-muted small mb-1">Categories</p>
                    <h4 class="mb-0">{{ $revenues->groupBy('revenue_category_id')->count() }}</h4>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

