@php
    $deliveryRecords = $patient->deliveries()->with('deliveredBy', 'labour', 'visit')->latest('delivery_date_time')->get();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Delivery Records</h5>
    <a href="{{ route('midwife.delivery-management', $patient) }}" class="btn btn-sm btn-outline-danger">
        <i class="bi bi-plus-circle me-1"></i> New Delivery
    </a>
</div>

@if($deliveryRecords->isEmpty())
    <div class="alert alert-info mb-0">No delivery records found for this patient.</div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Babies</th>
                    <th>Status</th>
                    <th>Delivered By</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveryRecords as $record)
                    <tr>
                        <td>{{ $record->delivery_date_time?->format('M d, Y h:i A') }}</td>
                        <td>{{ str($record->delivery_type)->headline() }}</td>
                        <td>{{ $record->number_of_babies }}</td>
                        <td><span class="badge bg-secondary">{{ str($record->delivery_status)->headline() }}</span></td>
                        <td>{{ $record->deliveredBy?->name ?? 'N/A' }}</td>
                        <td class="text-end">
                            @include('patient.profile.maternity._actions', [
                                'record' => $record,
                                'recordLabel' => 'delivery',
                                'showRoute' => 'midwife.delivery.show',
                                'editRoute' => 'midwife.delivery.edit',
                                'destroyRoute' => 'midwife.delivery.destroy',
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
