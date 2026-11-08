@extends('layouts.modern')

@section('title', 'Admin Patient Register')

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

    <x-ui.page title="Admin Patient Register" subtitle="{{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }}">
        <x-slot:actions>
            <a href="{{ route('admin.patient-register.csv', $exportParams) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                <i class="bi bi-file-earmark-spreadsheet"></i> CSV
            </a>
            <a href="{{ route('admin.patient-register.pdf', $exportParams) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-danger bg-white px-3 py-2 text-sm font-semibold text-med-danger transition hover:bg-red-50">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>
        </x-slot:actions>

        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                @foreach($statCards as $card)
                    <x-ui.card>
                        <p class="text-sm font-medium text-med-muted">{{ $card['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($card['value']) }}</p>
                    </x-ui.card>
                @endforeach
            </div>

            <x-ui.card title="Filters" subtitle="Search and narrow the register by date, gender, or patient type.">
                <form method="GET" action="{{ route('admin.patient-register.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-12 xl:items-end">
                    <div class="xl:col-span-3">
                        <x-ui.input label="Search" name="search" type="search" value="{{ request('search') }}" placeholder="Hospital no, name, phone" />
                    </div>
                    <div class="xl:col-span-2">
                        <x-ui.input label="From" name="start_date" type="date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}" />
                    </div>
                    <div class="xl:col-span-2">
                        <x-ui.input label="To" name="end_date" type="date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}" />
                    </div>
                    <div class="xl:col-span-2">
                        <x-ui.select label="Gender" name="gender">
                            <option value="">All</option>
                            @foreach(['Male', 'Female', 'Other'] as $gender)
                                <option value="{{ $gender }}" @selected(request('gender') === $gender)>{{ $gender }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div class="xl:col-span-2">
                        <x-ui.select label="Type" name="patient_type">
                            <option value="">All</option>
                            <option value="registered" @selected(request('patient_type') === 'registered')>Registered</option>
                            <option value="walk_in" @selected(request('patient_type') === 'walk_in')>Walk-in</option>
                        </x-ui.select>
                    </div>
                    <div class="flex flex-wrap gap-2 xl:col-span-1">
                        <a href="{{ route('admin.patient-register.index') }}" class="mf-focus inline-flex flex-1 items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas" title="Reset filters" aria-label="Reset patient register filters">
                            <i class="bi bi-x-circle"></i>
                        </a>
                        <x-ui.button type="submit" class="flex-1" title="Apply filters"><i class="bi bi-funnel"></i></x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card title="Register Entries" subtitle="{{ number_format($patients->total()) }} patients in this filtered register.">
                @if($patients->count())
                    <x-ui.table>
                        <thead class="bg-med-canvas text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                            <tr>
                                <th class="px-4 py-3">Hospital No.</th>
                                <th class="px-4 py-3">Patient</th>
                                <th class="px-4 py-3">Gender</th>
                                <th class="px-4 py-3">Age</th>
                                <th class="px-4 py-3">Phone</th>
                                <th class="px-4 py-3">File Type</th>
                                <th class="px-4 py-3">Visits</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Registered</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-med-line bg-white">
                            @foreach($patients as $patient)
                                <tr class="hover:bg-med-canvas/70">
                                    <td class="px-4 py-4"><x-ui.badge variant="info">{{ $patient->hospital_number }}</x-ui.badge></td>
                                    <td class="px-4 py-4">
                                        <p class="font-semibold text-med-ink">{{ $patient->demographic?->full_name ?? 'N/A' }}</p>
                                        <p class="mt-1 text-sm text-med-muted">{{ $patient->demographic?->email ?? 'No email' }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-med-muted">{{ $patient->demographic?->gender ?? 'N/A' }}</td>
                                    <td class="px-4 py-4 text-med-muted">{{ $patient->demographic?->age ?? 'N/A' }}</td>
                                    <td class="px-4 py-4 text-med-muted">{{ $patient->demographic?->phone_number ?? 'N/A' }}</td>
                                    <td class="px-4 py-4 text-med-muted">{{ $patient->fileType?->name ?? 'General file' }}</td>
                                    <td class="px-4 py-4 text-med-muted">{{ number_format($patient->patient_visits_count) }}</td>
                                    <td class="px-4 py-4">
                                        <x-ui.badge :variant="$patient->is_walkIn ? 'warning' : 'success'">{{ $patient->is_walkIn ? 'Walk-in' : 'Registered' }}</x-ui.badge>
                                    </td>
                                    <td class="px-4 py-4 text-med-muted">{{ $patient->registration_date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td class="px-4 py-4 text-right">
                                        <a href="{{ route('admin.patient-register.summary', $patient) }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-white px-3 py-2 text-sm font-semibold text-med-primary transition hover:bg-med-primary hover:text-white">
                                            <i class="bi bi-compass"></i> Explore Summary
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @else
                    <x-ui.empty-state title="No patients found" message="No patients match the selected register period and filters." />
                @endif

                @if($patients->hasPages())
                    <div class="mt-5 border-t border-med-line pt-4">
                        {{ $patients->links() }}
                    </div>
                @endif
            </x-ui.card>
        </div>
    </x-ui.page>
@endsection
