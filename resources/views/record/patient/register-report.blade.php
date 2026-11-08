@extends('layouts.modern')

@section('title', 'Patient Register')

@section('content')
@php
    $exportParams = request()->only(['start_date', 'end_date', 'search', 'gender', 'patient_type']);
    $statCards = [
        ['label' => 'Total', 'value' => $summary['total'] ?? 0],
        ['label' => 'Registered', 'value' => $summary['registered'] ?? 0],
        ['label' => 'Walk-in', 'value' => $summary['walk_in'] ?? 0],
        ['label' => 'Male', 'value' => $summary['male'] ?? 0],
        ['label' => 'Female', 'value' => $summary['female'] ?? 0],
        ['label' => 'Other', 'value' => $summary['other'] ?? 0],
    ];
@endphp

<x-ui.page title="Patient Register" subtitle="{{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }}">
    <x-slot:actions>
        <a href="{{ route('record.patient-register.csv', $exportParams) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">CSV</a>
        <a href="{{ route('record.patient-register.pdf', $exportParams) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-danger bg-white px-3 py-2 text-sm font-semibold text-med-danger transition hover:bg-red-50">PDF</a>
    </x-slot:actions>

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
            @foreach($statCards as $card)
                <x-ui.card>
                    <p class="text-sm font-medium text-med-muted">{{ $card['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($card['value']) }}</p>
                </x-ui.card>
            @endforeach
        </div>

        <x-ui.card title="Filters" subtitle="Narrow the register by patient details, date, gender, or type.">
            <form method="GET" action="{{ route('record.patient-register.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6 xl:items-end">
                <div class="xl:col-span-2">
                    <x-ui.input label="Search" name="search" type="search" value="{{ request('search') }}" placeholder="Hospital no, name, phone" />
                </div>
                <x-ui.input label="From" name="start_date" type="date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}" />
                <x-ui.input label="To" name="end_date" type="date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}" />
                <x-ui.select label="Gender" name="gender">
                    <option value="">All</option>
                    @foreach(['Male', 'Female', 'Other'] as $gender)
                        <option value="{{ $gender }}" @selected(request('gender') === $gender)>{{ $gender }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select label="Type" name="patient_type">
                    <option value="">All</option>
                    <option value="registered" @selected(request('patient_type') === 'registered')>Registered</option>
                    <option value="walk_in" @selected(request('patient_type') === 'walk_in')>Walk-in</option>
                </x-ui.select>
                <div class="xl:col-span-6 flex flex-wrap justify-end gap-3">
                    <a href="{{ route('record.patient-register.index') }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-4 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Reset</a>
                    <button type="submit" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">Apply Filters</button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Register Entries" subtitle="{{ $patients->total() }} patient{{ $patients->total() === 1 ? '' : 's' }} in this filtered register.">
            @if($patients->count() > 0)
                <x-ui.table>
                    <thead class="bg-med-canvas text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                        <tr>
                            <th class="px-4 py-3">Hospital No.</th>
                            <th class="px-4 py-3">Patient</th>
                            <th class="px-4 py-3">Gender</th>
                            <th class="px-4 py-3">Age</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">File Type</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Registered</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-med-line bg-white">
                        @foreach($patients as $patient)
                            <tr class="hover:bg-med-canvas/60">
                                <td class="px-4 py-3 font-semibold text-med-primary">{{ $patient->hospital_number }}</td>
                                <td class="px-4 py-3"><p class="font-semibold text-med-ink">{{ $patient->demographic?->full_name ?? 'N/A' }}</p><p class="mt-1 text-xs text-med-muted">{{ $patient->demographic?->email ?? 'No email' }}</p></td>
                                <td class="px-4 py-3 text-med-muted">{{ $patient->demographic?->gender ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $patient->demographic?->age ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $patient->demographic?->phone_number ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $patient->fileType?->name ?? 'General file' }}</td>
                                <td class="px-4 py-3"><x-ui.badge variant="{{ $patient->is_walkIn ? 'warning' : 'success' }}">{{ $patient->is_walkIn ? 'Walk-in' : 'Registered' }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-med-muted">{{ $patient->registration_date?->format('M d, Y') ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('record.patients.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>

                @if($patients->hasPages())
                    <div class="mt-4">{{ $patients->links() }}</div>
                @endif
            @else
                <x-ui.empty-state title="No Patients Found" message="No patients match this register period or filter." />
            @endif
        </x-ui.card>
    </div>
</x-ui.page>
@endsection
