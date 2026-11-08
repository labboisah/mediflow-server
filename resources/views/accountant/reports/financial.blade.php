@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() inancial. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() inancial. param($m) $m.Value.ToUpper() lade')
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
            <a id="downloadCsvLink" class="btn btn-success" href="{{ route('accountant.reports.financial.export', array_filter(['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d'), 'today' => request()->has('today') ? 1 : null])) }}">
                <i class="bi bi-download"></i> Download CSV
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('accountant.reports.financial') }}" class="row g-3 align-items-end">
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
                <div class="col-md-4 date-field">
                    <label for="end_date" class="form-label">To Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="financialReportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab">Billing Summary</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sorting-tab" data-bs-toggle="tab" data-bs-target="#sorting" type="button" role="tab">Sort By</button>
        </li>
    </ul>

    <div class="tab-content" id="financialReportTabsContent">
        <div class="tab-pane fade show active" id="summary" role="tabpanel">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Total Revenue</p>
                            <h3 class="text-success">{{ number_format($totalRevenue, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Bills Generated</p>
                            <h3 class="text-primary">{{ number_format($totalBillsGenerated, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Outstanding Amount</p>
                            <h3 class="text-danger">{{ number_format($totalOutstanding, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Collection Rate</p>
                            <h3 class="text-info">
                                {{ $totalBillsGenerated > 0 ? round(($totalRevenue / $totalBillsGenerated) * 100, 2) : 0 }}%
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Revenue by Payment Method</h5>
                        </div>
                        <div class="card-body">
                            @if($revenueByMethod->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Payment Method</th>
                                                <th class="text-end">Amount</th>
                                                <th class="text-end">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($revenueByMethod as $method)
                                                <tr>
                                                    <td>{{ $method->payment_method }}</td>
                                                    <td class="text-end fw-bold">{{ number_format($method->total, 2) }}</td>
                                                    <td class="text-end">{{ $totalRevenue > 0 ? round(($method->total / $totalRevenue) * 100, 2) : 0 }}%</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold border-top">
                                                <td>Total</td>
                                                <td class="text-end">{{ number_format($totalRevenue, 2) }}</td>
                                                <td class="text-end">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted text-center py-4">No revenue data for this period</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Insurance Claims Summary</h5>
                        </div>
                        <div class="card-body">
                            @if($insuranceClaims->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Provider</th>
                                                <th>Method</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($insuranceClaims as $claim)
                                                <tr>
                                                    <td><strong>{{ $claim->insurance_provider }}</strong></td>
                                                    <td>{{ $claim->payment_method }}</td>
                                                    <td class="text-end fw-bold">{{ number_format($claim->total, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted text-center py-4">No insurance claims for this period</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Top 20 Patients by Revenue</h5>
                        </div>
                        <div class="card-body">
                            @if($revenueByPatient->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover datatable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Patient Name</th>
                                                <th>Hospital Number</th>
                                                <th class="text-end">Total Paid</th>
                                                <th class="text-end">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($revenueByPatient as $index => $item)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ optional($item->patient)->name ?? 'Walk-in' }}</td>
                                                    <td>{{ optional($item->patient)->hospital_number ?? 'N/A' }}</td>
                                                    <td class="text-end fw-bold">{{ number_format($item->total, 2) }}</td>
                                                    <td class="text-end">{{ $totalRevenue > 0 ? round(($item->total / $totalRevenue) * 100, 2) : 0 }}%</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted text-center py-4">No patient revenue data for this period</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="sorting" role="tabpanel">
            <div class="row mb-4">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Top Collectors</h5>
                        </div>
                        <div class="card-body">
                            @if($topCollectors->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th class="text-end">Total Collected</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topCollectors as $collector)
                                                <tr>
                                                    <td>{{ optional($collector->recordedBy)->name ?? 'Unknown' }}</td>
                                                    <td class="text-end fw-bold">{{ number_format($collector->total, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted text-center py-4">No collection activity for this period</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Top Departments</h5>
                        </div>
                        <div class="card-body">
                            @if($topDepartments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Department</th>
                                                <th class="text-end">Billed Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topDepartments as $department)
                                                <tr>
                                                    <td>{{ $department->name }}</td>
                                                    <td class="text-end fw-bold">{{ number_format($department->total, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted text-center py-4">No departmental billing data for this period</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Top Services</h5>
                        </div>
                        <div class="card-body">
                            @if($topServices->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Service</th>
                                                <th class="text-end">Billed Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topServices as $service)
                                                <tr>
                                                    <td>{{ $service->name }}</td>
                                                    <td class="text-end fw-bold">{{ number_format($service->total, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted text-center py-4">No service billing data for this period</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
            downloadCsvLink.href = '{{ route('accountant.reports.financial.export') }}' + '?' + params.toString();
        }
    });
</script>
@endsection
@endsection

