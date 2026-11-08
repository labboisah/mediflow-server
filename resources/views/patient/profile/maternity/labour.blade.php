@php
    $labourRecords = $patient->labours()->with('recordedBy', 'visit')->latest('created_at')->get();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Labour Records</h5>
    <a href="{{ route('midwife.labour-management', $patient) }}" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-plus-circle me-1"></i> New Labour
    </a>
</div>

@if($labourRecords->isEmpty())
    <div class="alert alert-info mb-0">No labour records found for this patient.</div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Onset</th>
                    <th>Stage</th>
                    <th>Status</th>
                    <th>Blood Pressure</th>
                    <th>Recorded By</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($labourRecords as $record)
                    <tr>
                        <td>{{ $record->labour_onset_time?->format('M d, Y h:i A') }}</td>
                        <td>{{ str($record->stage)->headline() }}</td>
                        <td><span class="badge bg-secondary">{{ str($record->status)->headline() }}</span></td>
                        <td>{{ $record->blood_pressure ?? 'N/A' }}</td>
                        <td>{{ $record->recordedBy?->name ?? 'N/A' }}</td>
                        <td class="text-end">
                            @include('patient.profile.maternity._actions', [
                                'record' => $record,
                                'recordLabel' => 'labour',
                                'showRoute' => 'midwife.labour.show',
                                'editRoute' => 'midwife.labour.edit',
                                'destroyRoute' => 'midwife.labour.destroy',
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
