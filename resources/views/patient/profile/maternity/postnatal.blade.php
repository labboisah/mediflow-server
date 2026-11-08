@php
    $postnatalRecords = $patient->postnatalExaminations()->with('recordedBy', 'delivery', 'visit')->latest('examination_date_time')->get();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Postnatal Records</h5>
    <a href="{{ route('midwife.postnatal-management', $patient) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-plus-circle me-1"></i> New Postnatal
    </a>
</div>

@if($postnatalRecords->isEmpty())
    <div class="alert alert-info mb-0">No postnatal records found for this patient.</div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Exam Time</th>
                    <th>Blood Pressure</th>
                    <th>Status</th>
                    <th>Recorded By</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($postnatalRecords as $record)
                    <tr>
                        <td>{{ $record->examination_date_time?->format('M d, Y h:i A') }}</td>
                        <td>{{ $record->examination_time ? str($record->examination_time)->headline() : 'N/A' }}</td>
                        <td>{{ $record->blood_pressure ?? 'N/A' }}</td>
                        <td><span class="badge bg-secondary">{{ str($record->recovery_status)->headline() }}</span></td>
                        <td>{{ $record->recordedBy?->name ?? 'N/A' }}</td>
                        <td class="text-end">
                            @include('patient.profile.maternity._actions', [
                                'record' => $record,
                                'recordLabel' => 'postnatal',
                                'showRoute' => 'midwife.postnatal-examination.show',
                                'editRoute' => 'midwife.postnatal-examination.edit',
                                'destroyRoute' => 'midwife.postnatal-examination.destroy',
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
