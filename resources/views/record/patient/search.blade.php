@extends('layouts.modern')

@section('title', 'Patient Search')

@section('content')
<x-ui.page title="Patient Search" subtitle="Find patient records by hospital number, phone number, first name, or last name.">
    <x-slot:actions>
        <a href="{{ route('record.patients.register.form') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
            Register Patient
        </a>
        <a href="{{ route('record.patients.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
            Patient List
        </a>
    </x-slot:actions>

    <div class="space-y-6">
        <x-ui.card>
            <form action="{{ route('record.patients.search') }}" method="GET" class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                <x-ui.input
                    label="Search term"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Hospital number, phone, first name, or last name"
                    required
                />

                <button type="submit" class="mf-focus inline-flex h-[42px] items-center justify-center rounded-md border border-med-primary bg-med-primary px-5 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
                    Search
                </button>
            </form>
        </x-ui.card>

        @if(request('q'))
            <x-ui.card title="Search Results" subtitle="{{ $patients->count() }} result{{ $patients->count() === 1 ? '' : 's' }} found for {{ request('q') }}">
                @if($patients->count() > 0)
                    <x-ui.table>
                        <thead class="bg-med-canvas text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                            <tr>
                                <th class="px-4 py-3">Hospital No.</th>
                                <th class="px-4 py-3">Patient</th>
                                <th class="px-4 py-3">Phone</th>
                                <th class="px-4 py-3">Registered</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-med-line bg-white">
                            @foreach($patients as $patient)
                                <tr class="hover:bg-med-canvas/60">
                                    <td class="px-4 py-3 font-semibold text-med-primary">{{ $patient->hospital_number }}</td>
                                    <td class="px-4 py-3 text-med-ink">{{ $patient->demographic?->full_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-med-muted">{{ $patient->demographic?->phone_number ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-med-muted">{{ $patient->registration_date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">
                                            View Record
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @else
                    <x-ui.empty-state title="No Patients Found" message="Try another hospital number, phone number, or patient name." />
                @endif
            </x-ui.card>
        @else
            <x-ui.empty-state title="Search For A Patient" message="Enter at least two characters to start searching patient records." />
        @endif
    </div>
</x-ui.page>
@endsection
