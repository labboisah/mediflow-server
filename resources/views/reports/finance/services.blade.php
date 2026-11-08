<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Service Billing Summary</h5>
        <p class="text-muted small mb-0">Service revenue totals with collapsible detail records.</p>
    </div>
    <div class="card-body">
        @if(!empty($data['summary']) && $data['summary']->count())
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Service</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['summary'] as $index => $service)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $service->service_name }}</td>
                                <td class="text-end">{{ number_format($service->total_amount, 2) }}</td>
                                <td class="text-end">{{ $service->total_quantity }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#service-details-{{ $index }}" aria-expanded="false" aria-controls="service-details-{{ $index }}">
                                        Show details
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="service-details-{{ $index }}">
                                <td colspan="5">
                                    @php $details = $data['details'][$service->service_id] ?? collect(); @endphp
                                    @if($details->count())
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Bill #</th>
                                                        <th>Patient</th>
                                                        <th class="text-end">Quantity</th>
                                                        <th class="text-end">Amount</th>
                                                        <th class="text-end">Issued</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($details as $record)
                                                        <tr>
                                                            <td>{{ $record->bill_number ?? 'N/A' }}</td>
                                                            <td>{{ $record->patient_name ?? 'Unknown' }}</td>
                                                            <td class="text-end">{{ $record->quantity }}</td>
                                                            <td class="text-end">{{ number_format($record->amount, 2) }}</td>
                                                            <td class="text-end">{{ \Carbon\Carbon::parse($record->issued_date)->format('Y-m-d') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">No service details available for this service.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-secondary mb-0">No service billing data found for the selected period.</div>
        @endif
    </div>
</div>
