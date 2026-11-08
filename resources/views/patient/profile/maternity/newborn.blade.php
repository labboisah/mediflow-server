@php
    $newbornRecords = $patient->newborns()->with('recordedBy', 'delivery', 'visit')->latest('birth_date_time')->get();
    $newbornExaminations = $patient->newbornExaminations()->with('recordedBy', 'newborn')->latest('examination_date_time')->get();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Newborn Records</h5>
    <a href="{{ route('midwife.newborn-management', $patient) }}" class="btn btn-sm btn-outline-info">
        <i class="bi bi-plus-circle me-1"></i> New Newborn
    </a>
</div>

@if($newbornRecords->isEmpty())
    <div class="alert alert-info mb-0">No newborn records found for this patient.</div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Registration No.</th>
                    <th>Birth Date</th>
                    <th>Sex</th>
                    <th>Weight</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($newbornRecords as $record)
                    <tr>
                        <td>{{ $record->newborn_registration_number ?? 'N/A' }}</td>
                        <td>{{ $record->birth_date_time?->format('M d, Y h:i A') }}</td>
                        <td>{{ str($record->sex)->headline() }}</td>
                        <td>{{ $record->birth_weight ?? 'N/A' }}</td>
                        <td><span class="badge bg-secondary">{{ str($record->status)->headline() }}</span></td>
                        <td class="text-end">
                            @include('patient.profile.maternity._actions', [
                                'record' => $record,
                                'recordLabel' => 'newborn',
                                'showRoute' => 'midwife.newborn.show',
                                'editRoute' => 'midwife.newborn.edit',
                                'destroyRoute' => 'midwife.newborn.destroy',
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="mt-4">
    <h5 class="mb-3">Newborn Examination Records</h5>

    @if($newbornExaminations->isEmpty())
        <div class="alert alert-info mb-0">No newborn examination records found for this patient.</div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Newborn</th>
                        <th>Weight</th>
                        <th>Temperature</th>
                        <th>Status</th>
                        <th>Recorded By</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($newbornExaminations as $record)
                        <tr>
                            <td>{{ $record->examination_date_time?->format('M d, Y h:i A') }}</td>
                            <td>{{ $record->newborn?->newborn_registration_number ?? 'N/A' }}</td>
                            <td>{{ $record->weight ?? 'N/A' }}</td>
                            <td>{{ $record->temperature ?? 'N/A' }}</td>
                            <td><span class="badge bg-secondary">{{ str($record->exam_status)->headline() }}</span></td>
                            <td>{{ $record->recordedBy?->name ?? 'N/A' }}</td>
                            <td class="text-end">
                                @include('patient.profile.maternity._actions', [
                                    'record' => $record,
                                    'recordLabel' => 'newborn examination',
                                    'showRoute' => 'midwife.newborn-examination.show',
                                    'editRoute' => 'midwife.newborn-examination.edit',
                                    'destroyRoute' => 'midwife.newborn-examination.destroy',
                                ])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
