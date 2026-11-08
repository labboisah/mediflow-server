@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')
@php 
    $startDate = Carbon\Carbon::today();
    $endDate = Carbon\Carbon::today()->endOfDay();
    $todayOnly = Carbon\Carbon::today();
@endphp
<div class="container-fluid">
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.finance.search') }}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="today_checkbox" name="today" value="1" {{ old('today') ? 'checked' : '' }}>
                        <label class="form-check-label" for="today_checkbox">Today</label>
                    </div>
                </div>
                <div class="col-md-4 date-field">
                    <label for="start_date" class="form-label">From Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ old('start_date', $startDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3 date-field">
                    <label for="end_date" class="form-label">To Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select id="sort_by" name="sort_by" class="form-select">
                        <option value="users" {{ old('sort_by') === 'users' ? 'selected' : '' }}>Users</option>
                        <option value="departments" {{ old('sort_by') === 'departments' ? 'selected' : '' }}>Departments</option>
                        <option value="services" {{ old('sort_by') === 'services' ? 'selected' : '' }}>Services</option>
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

