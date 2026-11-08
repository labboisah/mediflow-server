@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() iew. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() iew. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')
<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Financial / Billing Report</h1>
            <p class="text-muted mb-0">Search billing activity by date, then review collection and service breakdowns.</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
            <button type="button" class="btn btn-outline-secondary" id="shareReportBtn"><i class="bi bi-share-fill"></i> Share</button>
            <a id="downloadCsvLink" class="btn btn-success" href="{{ route('reports.finance.export', array_filter(['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d'), 'today' => request()->has('today') ? 1 : null])) }}">
                <i class="bi bi-download"></i> Download CSV
            </a>
        </div>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.finance.search') }}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="today_checkbox" name="today" value="1" {{ $todayOnly ? 'checked' : '' }}>
                        <label class="form-check-label" for="today_checkbox">Today</label>
                    </div>
                </div>
                <div class="col-md-4 date-field">
                    <label for="start_date" class="form-label">From Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-3 date-field">
                    <label for="end_date" class="form-label">To Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select id="sort_by" name="sort_by" class="form-select">
                        <option value="users" {{ $sortBy === 'users' ? 'selected' : '' }}>Users</option>
                        <option value="departments" {{ $sortBy === 'departments' ? 'selected' : '' }}>Departments</option>
                        <option value="services" {{ $sortBy === 'services' ? 'selected' : '' }}>Services</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    @switch($sortBy)
        @case('users')
            @include('reports.finance.users')
            @break
        @case('departments')
            @include('reports.finance.department')
            @break
        @case('services')
            @include('reports.finance.services')
            @break
        @default
            <div class="alert alert-info">Please choose a report type and filter the results.</div>
    @endswitch
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const todayCheckbox = document.getElementById('today_checkbox');
        const dateFields = document.querySelectorAll('.date-field');
        const shareButton = document.getElementById('shareReportBtn');
        const downloadCsvLink = document.getElementById('downloadCsvLink');

        const toggleDateFields = () => {
            dateFields.forEach(el => el.style.display = todayCheckbox.checked ? 'none' : 'block');
        };

        if (todayCheckbox) {
            todayCheckbox.addEventListener('change', toggleDateFields);
            toggleDateFields();
        }

        if (shareButton) {
            shareButton.addEventListener('click', async () => {
                const shareUrl = new URL(window.location.href);

                if (navigator.share) {
                    await navigator.share({
                        title: 'Financial / Billing Report',
                        text: 'View the billing report for the selected period.',
                        url: shareUrl.toString(),
                    });
                } else {
                    try {
                        await navigator.clipboard.writeText(shareUrl.toString());
                        alert('Report link copied to clipboard.');
                    } catch (error) {
                        prompt('Copy this report link:', shareUrl.toString());
                    }
                }
            });
        }

        if (downloadCsvLink) {
            const params = new URLSearchParams(window.location.search);
            if (params.has('today')) {
                params.set('today', '1');
            }
            downloadCsvLink.href = '{{ route('reports.finance.export') }}' + '?' + params.toString();
        }
    });
</script>
@endsection

