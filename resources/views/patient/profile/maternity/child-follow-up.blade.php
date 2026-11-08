@php
    $followUpRecords = $patient->childFollowUps()->with('recordedBy', 'newborn', 'visit')->latest('follow_up_date_time')->get();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Child Follow-up Records</h5>
    <a href="{{ route('midwife.child-follow-up-management', $patient) }}" class="btn btn-sm btn-outline-dark">
        <i class="bi bi-plus-circle me-1"></i> New Follow-up
    </a>
</div>

@if($followUpRecords->isEmpty())
    <div class="alert alert-info mb-0">No child follow-up records found for this patient.</div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Period</th>
                    <th>Weight</th>
                    <th>Feeding</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($followUpRecords as $record)
                    <tr>
                        <td>{{ $record->follow_up_date_time?->format('M d, Y h:i A') }}</td>
                        <td>{{ $record->follow_up_period ? str($record->follow_up_period)->headline() : 'N/A' }}</td>
                        <td>{{ $record->weight ?? 'N/A' }}</td>
                        <td>{{ $record->feeding_type ?? 'N/A' }}</td>
                        <td><span class="badge bg-secondary">{{ str($record->health_status)->headline() }}</span></td>
                        <td class="text-end">
                            @include('patient.profile.maternity._actions', [
                                'record' => $record,
                                'recordLabel' => 'child follow-up',
                                'showRoute' => 'midwife.child-follow-up.show',
                                'editRoute' => 'midwife.child-follow-up.edit',
                                'destroyRoute' => 'midwife.child-follow-up.destroy',
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
