@extends('layouts.modern')

@section('title', 'Index.Blade')
@section('page-title', 'Index.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Statistics Report')

@section('content')
@php
    $exportParams = request()->only(['start_date', 'end_date', 'search', 'gender']);
    $total = collect($statistics)->sum('value');
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 d-flex align-items-center">
            <i class="bi bi-bar-chart-line text-success me-2"></i>
            Statistics Report
        </h1>
        <p class="text-muted mb-0">{{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }}</p>
    </div>

    <a href="{{ route('medical-director.statistics.pdf', $exportParams) }}" class="btn btn-outline-danger">
        <i class="bi bi-file-earmark-pdf me-1"></i>
        PDF
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('medical-director.statistics.index') }}" class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="search" class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" id="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Hospital no, name, phone">
                </div>
            </div>

            <div class="col-6 col-lg-2">
                <label for="start_date" class="form-label">From</label>
                <input type="date" id="start_date" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}" class="form-control">
            </div>

            <div class="col-6 col-lg-2">
                <label for="end_date" class="form-label">To</label>
                <input type="date" id="end_date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}" class="form-control">
            </div>

            <div class="col-6 col-lg-2">
                <label for="gender" class="form-label">Gender</label>
                <select id="gender" name="gender" class="form-select">
                    <option value="">All</option>
                    <option value="Male" @selected(request('gender') === 'Male')>Male</option>
                    <option value="Female" @selected(request('gender') === 'Female')>Female</option>
                    <option value="Other" @selected(request('gender') === 'Other')>Other</option>
                </select>
            </div>

            <div class="col-6 col-lg-2 d-grid">
                <button type="submit" class="btn btn-success" title="Apply filters">
                    <i class="bi bi-funnel me-1"></i>
                    Search
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Activities</div>
                <div class="display-6 fw-semibold mb-0">{{ number_format($total) }}</div>
            </div>
        </div>
    </div>

    @foreach($statistics as $stat)
        <div class="col-6 col-lg-4 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded bg-success-subtle text-success d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                        <i class="bi {{ $stat['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="text-muted small">{{ $stat['label'] }}</div>
                        <div class="h4 mb-0">{{ number_format($stat['value']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Statistic</th>
                    <th class="text-end">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistics as $stat)
                    <tr>
                        <td>
                            <i class="bi {{ $stat['icon'] }} text-success me-2"></i>
                            {{ $stat['label'] }}
                        </td>
                        <td class="text-end fw-semibold">{{ number_format($stat['value']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

