@php
    $antenatalRecords = $patient->antenatalCares()->with('recordedBy', 'visit')->latest('created_at')->get();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Antenatal Care Records</h5>
    <a href="{{ route('midwife.anc-management', $patient) }}" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-plus-circle me-1"></i> New ANC
    </a>
</div>

@if($antenatalRecords->isEmpty())
    <div class="alert alert-info mb-0">No antenatal care records found for this patient.</div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Gestation</th>
                    <th>Blood Pressure</th>
                    <th>Status</th>
                    <th>Recorded By</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($antenatalRecords as $record)
                    <tr>
                        <td>{{ $record->created_at?->format('M d, Y h:i A') }}</td>
                        <td>{{ $record->gestational_weeks ? $record->gestational_weeks . ' weeks' : 'N/A' }}</td>
                        <td>{{ $record->blood_pressure ?? 'N/A' }}</td>
                        <td><span class="badge bg-secondary">{{ str($record->status)->headline() }}</span></td>
                        <td>{{ $record->recordedBy?->name ?? 'N/A' }}</td>
                        <td class="text-end">
                            @include('patient.profile.maternity._actions', [
                                'record' => $record,
                                'recordLabel' => 'antenatal care',
                                'showRoute' => 'midwife.antenatal.show',
                                'editRoute' => 'midwife.antenatal.edit',
                                'destroyRoute' => 'midwife.antenatal.destroy',
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
