@extends('layouts.modern')

@section('title', 'Patient Search')

@section('content')
<x-ui.page title="Patient Search" subtitle="Find patients by hospital number, phone number, or name.">
    <x-ui.card title="Search Patient">
        <form action="{{ route('patient.search') }}" method="GET" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
            <x-ui.input label="Search" name="q" type="search" value="{{ request('q') }}" placeholder="Hospital number, phone number, first name, or last name" required />
            <button type="submit" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
                <i class="bi bi-search"></i>
                Search
            </button>
        </form>
    </x-ui.card>

    @if(request('q'))
        <x-ui.card title="Search Results" subtitle="Results for {{ request('q') }}">
            @if($patients->count() > 0)
                <x-ui.table>
                    <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                        <tr><th class="px-4 py-3">Hospital No</th><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Phone</th><th class="px-4 py-3">Registered</th><th class="px-4 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-med-line bg-white">
                        @foreach($patients as $patient)
                            <tr class="hover:bg-med-canvas/50">
                                <td class="px-4 py-3"><x-ui.badge variant="info">{{ $patient->hospital_number }}</x-ui.badge></td>
                                <td class="px-4 py-3 font-semibold text-med-ink">{{ $patient->demographic->full_name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $patient->demographic->phone_number ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ optional($patient->registration_date)->format('M d, Y') ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('patient.show', $patient) }}" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">View Details</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @else
                <x-ui.empty-state title="No Patients Found" message="No patients matched the current search term." />
            @endif
        </x-ui.card>
    @else
        <x-ui.card>
            <x-ui.empty-state title="Enter Search Term" message="Enter a search term to find a patient." />
        </x-ui.card>
    @endif
</x-ui.page>
@endsection
