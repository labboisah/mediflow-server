@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')
    <div class="container">
        <h1>Bills Management</h1>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 datatable" data-ajax="{{ route('admin.bills.index') }}" data-order='[[10,"desc"]]' data-refresh="15000">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Bill Number</th>
                                    <th>Patient Name</th>
                                    <th>Service Description</th>
                                    <th>Total Amount</th>
                                    <th>Discount</th>
                                    <th>Due Amount</th>
                                    <th>Status</th>
                                    <th>Consistency</th>
                                    <th>Issued By</th>
                                    <th>Created At</th>
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
@endsection
