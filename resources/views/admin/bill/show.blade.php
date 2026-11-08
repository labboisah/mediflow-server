@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() how. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() how. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('title', 'Bill Details')

@section('content')
    @php
        $canManageFinance = auth()->user()?->hasRole('administrator') ?? false;
        $routePrefix = request()->routeIs('finance.*') ? 'finance' : 'admin';
    @endphp

    <div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Bill Details</h1>
                <div>
                    
                    @if($canManageFinance)
                        <a href="{{ route('admin.bills.edit', $bill) }}" class="btn btn-warning btn-sm">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    @endif
                    <a href="{{ route($routePrefix . '.bills.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        <!-- Add more bill details here -->
         <div class="cards shadow-sm mb-4" id="bill-preview">
                <div class="card-header bg-primary text-white p-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">Bill Details</h5>
                            <small>Bill #: <strong>{{ $bill->bill_number }}</strong></small>
                        </div>
                        <div class="col-auto text-end">
                            <i class="bi bi-file-earmark-text" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Patient Name</p>
                            <p class="fw-bold">{{ $patientName ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Hospital Number</p>
                            <p class="fw-bold">{{ $hospitalNumber ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Bill Number</p>
                            <p class="fw-bold">{{ $bill->bill_number }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Status</p>
                            <p>
                                @if($bill->status === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($bill->status === 'partial')
                                    <span class="badge bg-warning">Partial</span>
                                @elseif($bill->status === 'pending')
                                    <span class="badge bg-danger">Pending</span>
                                @else
                                    <span class="badge bg-secondary">Cancelled</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-4">
                            <div class="col-md-3">
                                <p class="text-muted small mb-1">Issued Date</p>
                                <p class="fw-bold">{{ $bill->issued_date->format('M d, Y') }}</p>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted small mb-1">Due Date</p>
                                <p class="fw-bold">{{ $bill->due_date->format('M d, Y') }}</p>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted small mb-1">Discount</p>
                                <p class="fw-bold">{{ number_format($bill->discount, 0) }}%</p>
                            </div>
                            <!-- consistency check -->
                            
                                <div class="col-md-3">
                                    @if(!$bill->isAmountConsistent())
                                    <p class="text-danger small">
                                        <i class="fas fa-exclamation-triangle"></i> The bill amount is inconsistent.
                                    </p>
                                    @else
                                    <p class="text-success small">
                                        <i class="fas fa-check-circle"></i> The bill amount is consistent.
                                    </p>
                                    @endif
                                </div>
                            
                    </div>

                    <hr>

                    {{-- Services Breakdown --}}
                        <div class="mb-4">
                            <p class="text-muted small mb-2">Services & Items</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Description</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Subtotal</th>
                                            <th class="text-end">Payment Status</th>
                                            @if($canManageFinance)
                                                <th class="text-end">
                                                    <a href="{{ route('admin.bills.services.create', [$bill]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-circle"></i> New</a>
                                                </th>
                                            @endif
                                        </tr>
                                        
                                    </thead>
                                    <tbody>
                                        @foreach($bill->billServices as $billService)
                                            <tr>
                                                <td>
                                                    <strong>{{ $billService->service->name }}</strong><br>
                                                    <small class="text-muted">{{ $billService->service->code }}</small>
                                                </td>
                                                <td class="text-end">{{ number_format($billService->unit_price, 2) }}</td>
                                                <td class="text-end">{{ $billService->quantity }}</td>
                                                <td class="text-end">{{ number_format($billService->subtotal, 2) }}</td>
                                                <td class="text-end">{{ ucfirst($billService->bill->status) }}</td>
                                                @if($canManageFinance)
                                                    <td>
                                                        <a href="{{ route('admin.bills.services.edit', [$billService]) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i> </a>
                                                        <a href="{{ route('admin.bills.services.destroy', [$billService]) }}" class="btn btn-sm btn-outline-danger" onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this service?')) { document.getElementById('delete-form-{{ $billService->id }}').submit(); }"><i class="bi bi-trash"></i> </a>
                                                        <form id="delete-form-{{ $billService->id }}" action="{{ route('admin.bills.services.destroy', [$billService]) }}" method="POST" style="display: none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold border-top">
                                            <td colspan="{{ $canManageFinance ? 4 : 3 }}" class="text-end">Total:</td>
                                            <td class="text-end">{{ number_format($bill->totalBillServices(), 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <hr>

                        {{-- investigation Breakdown --}}
                        <div class="mb-4">
                            <p class="text-muted small mb-2">Investigations & Items</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Description</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Subtotal</th>
                                            <th class="text-end">Payment Status</th>
                                            @if($canManageFinance)
                                                <th class="text-end">
                                                    <a href="{{ route('admin.bills.investigations.create', [$bill]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-circle"></i> New</a>
                                                </th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        

                                        @foreach($bill->billInvestigations as $billInvestigation)
                                            <tr>
                                                <td>
                                                    <strong>{{ $billInvestigation->investigation->name }}</strong><br>
                                                    <small class="text-muted">{{ $billInvestigation->investigation->code }}</small>
                                                </td>
                                                <td class="text-end">{{ number_format($billInvestigation->unit_price, 2) }}</td>
                                                <td class="text-end">{{ $billInvestigation->quantity }}</td>
                                                <td class="text-end">{{ number_format($billInvestigation->subtotal, 2) }}</td>
                                                <td class="text-end">{{ ucfirst($bill->status) }}</td>
                                                @if($canManageFinance)
                                                    <td class="text-end">
                                                        <a href="{{ route('admin.bills.investigations.edit', [$billInvestigation]) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i> </a>
                                                        <a href="{{ route('admin.bills.investigations.destroy', [$bill, $billInvestigation]) }}" class="btn btn-sm btn-outline-danger" onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this investigation?')) { document.getElementById('delete-investigation-form-{{ $billInvestigation->id }}').submit(); }"><i class="bi bi-trash"></i> </a>
                                                        <form id="delete-investigation-form-{{ $billInvestigation->id }}" action="{{ route('admin.bills.investigations.destroy', [$billInvestigation]) }}" method="POST" style="display: none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold border-top">
                                            <td colspan="{{ $canManageFinance ? 4 : 3 }}" class="text-end">Total:</td>
                                            <td class="text-end">{{ number_format($bill->totalBillInvestigations(), 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <hr>
                    

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <p class="text-muted small mb-1">Amount</p>
                            <h4 class="text-primary mb-0">{{ number_format($bill->amount, 2) }}</h4>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small mb-1">Amount Due</p>
                            <h4 class="text-primary mb-0">{{ number_format($bill->due_amount, 2) }}</h4>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small mb-1">Total Paid</p>
                            <h4 class="text-success mb-0">{{ number_format($bill->totalPaid(), 2) }}</h4>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small mb-1">Balance Due</p>
                            <h4 class="text-danger mb-0">{{ number_format($bill->balance, 2) }}</h4>
                        </div>
                    </div>

                    <div class="text-center text-muted small mt-4 pt-3 border-top">
                        <p class="mb-1">This bill is generated by the system. Please keep it for your records.</p>
                        <p class="mb-0">Issued by: <strong>{{ $bill->issuedBy->name }}</strong></p>
                        <p class="mb-0">Bill Date: <strong>{{ $bill->issue_date }}</strong></p>
                    </div>
                </div>
            </div>

            

            {{-- Payments History --}}
            
                <div class="card shadow-sm d-print-none">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Payment History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                        <th>Recorded By</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bill->payments as $payment)
                                        <tr>
                                            <td><strong>{{ $payment->payment_id }}</strong></td>
                                            <td>{{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ $payment->payment_method }}</td>
                                            <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                            <td>{{ $payment->recordedBy->name }}</td>
                                            <td>
                                                <a href="{{ route($routePrefix . '.payments.receipt', $payment) }}" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-printer"></i> Print Receipt
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            
        </div>
    </div>
</div>



    </div>
@endsection

