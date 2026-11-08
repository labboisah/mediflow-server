@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('title', 'Bills Management')



@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Bills Management</h1>
        <div class="dropdown">
            <a class="btn btn-primary" href="{{ route('accountant.bills.create') }}">
                <i class="bi bi-plus-lg me-2"></i> New Bill
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-success shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-uppercase text-muted small">Today's Bills</div>
                    <div class="h4 mb-0" id="daily-bill-count">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-uppercase text-muted small">Total Amount</div>
                    <div class="h4 mb-0"><span class="fas fa-naira-sign"></span> <span id="daily-total-amount">0.00</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-uppercase text-muted small">Total Discount</div>
                    <div class="h4 mb-0 text-danger"><span class="fas fa-naira-sign"></span> <span id="daily-total-discount">0.00</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-uppercase text-muted small">Total Due</div>
                    <div class="h4 mb-0 text-success"><span class="fas fa-naira-sign"></span> <span id="daily-due-amount">0.00</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">All Bills</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable" data-ajax="{{ route('accountant.bills.index') }}" data-order='[[6,"desc"]]' data-refresh="15000">
                    <thead>
                        <tr>
                            <th>Bill Number</th>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Discount</th>
                            <th>Due Amount</th>
                            <th>Issued Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables will populate rows via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = document.querySelector('table.datatable');
        if (!table) {
            return;
        }

        const updateSummary = function (summary) {
            if (!summary) {
                return;
            }

            document.getElementById('daily-bill-count').textContent = summary.bill_count ?? 0;
            document.getElementById('daily-total-amount').textContent = Number(summary.total_amount ?? 0).toFixed(2);
            document.getElementById('daily-total-discount').textContent = Number(summary.total_discount ?? 0).toFixed(2);
            document.getElementById('daily-due-amount').textContent = Number(summary.due_amount ?? 0).toFixed(2);
        };

        $(table).on('xhr.dt', function (event, settings, json) {
            if (json && json.summary) {
                updateSummary(json.summary);
            }
        });
    });
</script>
@push('vite')
    @vite('resources/js/datatable.js')
@endpush
@endsection

