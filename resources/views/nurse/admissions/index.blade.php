@extends('layouts.modern')

@section('title', 'Admitted Patients')

@section('content')
<x-ui.page title="Admitted Patients" subtitle="Patients currently admitted to wards or beds.">
    <x-ui.card title="Search Admissions">
        <form method="GET" action="{{ route('nurse.admissions.index') }}" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
            <x-ui.input label="Search" type="search" name="q" value="{{ $search }}" placeholder="Hospital number, patient name, phone, or status" />
            <button type="submit" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
                <i class="bi bi-search"></i>
                Search
            </button>
        </form>
    </x-ui.card>

    <x-ui.card title="Current Admissions" subtitle="Total: {{ $admissions->count() }} admitted patients">
        @if($admissions->isEmpty())
            <x-ui.empty-state title="No Admitted Patients" message="No admitted patients matched the current filters." />
        @else
            <x-ui.table>
                <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                    <tr><th class="px-4 py-3">Hospital No</th><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Phone</th><th class="px-4 py-3">Ward / Bed</th><th class="px-4 py-3">Admission Date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Admitted By</th><th class="px-4 py-3 text-right">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-med-line bg-white">
                    @foreach($admissions as $admission)
                        @php($patient = $admission->patientVisit?->patient)
                        <tr class="hover:bg-med-canvas/50">
                            <td class="px-4 py-3"><x-ui.badge variant="info">{{ $patient?->hospital_number ?? 'N/A' }}</x-ui.badge></td>
                            <td class="px-4 py-3 font-semibold text-med-ink">{{ $patient?->name() ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-med-muted">{{ $patient?->demographic?->phone_number ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-med-muted">{{ $admission->bed?->ward?->name ?? 'N/A' }} / {{ $admission->bed?->bed_no ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-med-muted">{{ $admission->date ? date('M d, Y', strtotime($admission->date)) : 'N/A' }} {{ $admission->time }}</td>
                            <td class="px-4 py-3"><x-ui.badge variant="neutral">{{ str($admission->status ?? 'registered')->headline() }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-med-muted">{{ $admission->admittedBy?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($patient)
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('nurse.patient.show', $patient) }}" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">View Profile</a>
                                        <a href="{{ route('nurse.admissions.record-absconded', $admission) }}" class="mf-focus rounded-md border border-red-200 px-3 py-1.5 text-sm font-semibold text-med-danger transition hover:bg-red-50" onclick="return confirm('Are you sure you want to mark this patient as absconded?');">Absconded</a>
                                        <a href="{{ route('nurse.admissions.record-sama', $admission) }}" class="mf-focus rounded-md border border-orange-200 px-3 py-1.5 text-sm font-semibold text-orange-700 transition hover:bg-orange-50" onclick="return confirm('Are you sure you want to mark this patient as Sign Against Medical Advice?');">SAMA</a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif
    </x-ui.card>
</x-ui.page>
@endsection
