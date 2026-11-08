@extends('layouts.modern')

@section('title', 'ANC Patient Management')

@section('content')
    <x-ui.page title="Patient Management" subtitle="Manage active patients under midwife care and complete pending maternity service requests.">
        @if(count($requests) == 0)
            <x-ui.empty-state title="No ANC Records" message="There are no pending midwife service requests right now." />
        @else
            <x-ui.table>
                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Hospital No.</th>
                        <th class="px-4 py-3">Patient</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Age</th>
                        <th class="px-4 py-3">Gender</th>
                        <th class="px-4 py-3">Marital Status</th>
                        <th class="px-4 py-3">Next of Kin</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-med-line bg-white">
                    @forelse($requests as $request)
                        @if($request->patientVisit && $request->patientVisit->status == 'Active')
                            @php($patient = $request->patientVisit->patient)
                            <tr>
                                <td class="px-4 py-4 text-med-muted">{{ $loop->iteration }}</td>
                                <td class="px-4 py-4"><x-ui.badge variant="info">{{ $patient->hospital_number ?? 'N/A' }}</x-ui.badge></td>
                                <td class="px-4 py-4 font-semibold text-med-ink">{{ $patient->name() }}</td>
                                <td class="px-4 py-4 text-med-muted">{{ $patient->demographic->phone_number ?? 'N/A' }}</td>
                                <td class="px-4 py-4 text-med-muted">{{ $patient->age() ?? 'N/A' }}</td>
                                <td class="px-4 py-4 text-med-muted">{{ $patient->demographic->gender ?? 'N/A' }}</td>
                                <td class="px-4 py-4 text-med-muted">{{ $patient->demographic->marital_status ?? 'N/A' }}</td>
                                <td class="px-4 py-4 text-med-muted">
                                    <span class="block font-medium text-med-ink">{{ $patient->nextOfKin->name ?? 'N/A' }}</span>
                                    <span class="block text-xs">{{ $patient->nextOfKin->telephone ?? 'N/A' }}</span>
                                    <span class="block text-xs">{{ $patient->nextOfKin->relationship ?? 'N/A' }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('midwife.patient.show', $patient) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">Profile</a>
                                        <a href="{{ route('midwife.patient.complete', $request) }}" onclick="return confirm('Are you sure you want to mark this request as completed?');" class="text-sm font-semibold text-med-success hover:text-green-700">Complete</a>
                                        <a href="{{ route('midwife.patient.close-visit', $request->patientVisit) }}" onclick="return confirm('Are you sure you want to close this visit?');" class="text-sm font-semibold text-med-warning hover:text-orange-700">Close Visit</a>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8">
                                <x-ui.empty-state title="No Patients Found" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        @endif
    </x-ui.page>
@endsection
