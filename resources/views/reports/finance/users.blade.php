<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Users Billing Summary</h5>
        <p class="text-muted small mb-0">Amounts, discounts and due totals grouped by issuing user.</p>
    </div>
    <div class="card-body">
        @if(!empty($data['summary']) && $data['summary']->count())
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-end">Total Discount</th>
                            <th class="text-end">Total Due</th>
                            <th class="text-end">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['summary'] as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->user_name }}</td>
                                <td class="text-end">{{ number_format($item->total_amount, 2) }}</td>
                                <td class="text-end text-warning">{{ number_format($item->total_discount, 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($item->total_due, 2) }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#user-details-{{ $index }}" aria-expanded="false" aria-controls="user-details-{{ $index }}">
                                        Show details
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="user-details-{{ $index }}">
                                <td colspan="6">
                                    @php $details = $data['details'][$item->issued_by] ?? collect(); @endphp
                                    @if($details->count())
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Bill #</th>
                                                        <th>Patient</th>
                                                        <th class="text-end">Amount</th>
                                                        <th class="text-end">Discount</th>
                                                        <th class="text-end">Due Amount</th>
                                                        <th class="text-end">Services</th>
                                                        <th class="text-end">Issued</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($details as $bill)
                                                        <tr>
                                                            <td>{{ $bill->bill_number ?? 'N/A' }}</td>
                                                            <td>{{ $bill->patientName() }}</td>
                                                            <td class="text-end">{{ number_format($bill->amount, 2) }}</td>
                                                            <td class="text-end">{{ $bill->discount }}%</td>
                                                            <td class="text-end">{{ number_format($bill->due_amount, 2) }}</td>
                                                            <td class="text">
                                                                <ul class="mb-0">
                                                                @foreach($bill->billInvestigations as $billInvestigation)
                                                                    <li>{{ $billInvestigation->investigation->name}}<li>
                                                                @endforeach
                                                                @foreach($bill->billServices as $billService)
                                                                    <li>{{ $billService->service->name }}</li>
                                                                @endforeach  
                                                                </ul>  
                                                            </td>
                                                            <td class="text-end">{{ optional($bill->issued_date)->format('Y-m-d') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">No billing details available for this user.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-secondary mb-0">No user billing data found for the selected period.</div>
        @endif
    </div>
</div>
