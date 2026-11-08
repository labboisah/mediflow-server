@extends('layouts.modern')

@section('title', 'Record Officer Dashboard')
@section('page-title', 'Record Officer Dashboard')
@section('page-subtitle', 'Patient registration, records, visits, and front desk activity.')

@section('content')
    <x-ui.page title="Record Officer Dashboard" subtitle="Patient registration, records, visits, and front desk activity.">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.card>
                <div class="flex items-center gap-4">
                    <span class="mf-icon-box"><i class="bi bi-people-fill"></i></span>
                    <div>
                        <p class="text-sm font-medium text-med-muted">Total Patients</p>
                        <p class="text-2xl font-bold text-med-ink">{{ number_format($totalPatients) }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center gap-4">
                    <span class="mf-icon-box"><i class="bi bi-stethoscope"></i></span>
                    <div>
                        <p class="text-sm font-medium text-med-muted">Today's Visits</p>
                        <p class="text-2xl font-bold text-med-ink">{{ number_format($todaysVisits) }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center gap-4">
                    <span class="mf-icon-box"><i class="bi bi-file-medical"></i></span>
                    <div>
                        <p class="text-sm font-medium text-med-muted">Active Records</p>
                        <p class="text-2xl font-bold text-med-ink">{{ number_format($activeRecords) }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center gap-4">
                    <span class="mf-icon-box"><i class="bi bi-person-check"></i></span>
                    <div>
                        <p class="text-sm font-medium text-med-muted">Walk-in Patients</p>
                        <p class="text-2xl font-bold text-med-ink">{{ number_format($walkInPatients) }}</p>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <x-ui.card title="Quick Actions">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('record.patients.register.form') }}" class="rounded-md border border-med-line bg-white p-4 transition hover:bg-med-canvas">
                    <i class="bi bi-file-earmark-plus text-xl text-med-primary"></i>
                    <p class="mt-3 font-semibold text-med-ink">Register Patient</p>
                    <p class="mt-1 text-sm text-med-muted">Add a new patient record</p>
                </a>
                <a href="{{ route('record.patients.index') }}" class="rounded-md border border-med-line bg-white p-4 transition hover:bg-med-canvas">
                    <i class="bi bi-list-check text-xl text-med-primary"></i>
                    <p class="mt-3 font-semibold text-med-ink">View Patients</p>
                    <p class="mt-1 text-sm text-med-muted">Browse all records</p>
                </a>
                <a href="{{ route('record.patient-register.index') }}" class="rounded-md border border-med-line bg-white p-4 transition hover:bg-med-canvas">
                    <i class="bi bi-file-earmark-spreadsheet text-xl text-med-primary"></i>
                    <p class="mt-3 font-semibold text-med-ink">Patient Register</p>
                    <p class="mt-1 text-sm text-med-muted">Filter and export records</p>
                </a>
                <a href="{{ route('record.patients.search') }}" class="rounded-md border border-med-line bg-white p-4 transition hover:bg-med-canvas">
                    <i class="bi bi-search text-xl text-med-primary"></i>
                    <p class="mt-3 font-semibold text-med-ink">Search Patient</p>
                    <p class="mt-1 text-sm text-med-muted">Find by phone or number</p>
                </a>
                <a href="{{ route('record.appointments.index') }}" class="rounded-md border border-med-line bg-white p-4 transition hover:bg-med-canvas">
                    <i class="bi bi-calendar-event text-xl text-med-primary"></i>
                    <p class="mt-3 font-semibold text-med-ink">Appointments</p>
                    <p class="mt-1 text-sm text-med-muted">Review scheduled visits</p>
                </a>
            </div>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-8" title="Recently Registered Patients">
                @if($recentPatients->isNotEmpty())
                    <x-ui.table class="shadow-none">
                        <thead class="bg-med-canvas">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Hospital No</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Patient</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Phone</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Registered</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-med-line">
                            @foreach($recentPatients as $patient)
                                <tr class="hover:bg-med-canvas">
                                    <td class="px-4 py-4"><x-ui.badge variant="info">{{ $patient->hospital_number }}</x-ui.badge></td>
                                    <td class="px-4 py-4 font-semibold text-med-ink">{{ $patient->demographic->full_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-4 text-sm text-med-muted">{{ $patient->demographic->phone_number ?? 'N/A' }}</td>
                                    <td class="px-4 py-4 text-sm text-med-muted">{{ $patient->registration_date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td class="px-4 py-4 text-right">
                                        <a href="{{ route('record.patients.show', $patient) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-muted hover:bg-med-canvas" title="View patient">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @else
                    <x-ui.empty-state title="No patients registered today" />
                @endif
            </x-ui.card>

            <x-ui.card class="xl:col-span-4" title="Upcoming Appointments">
                <div class="space-y-3">
                    @forelse($upcomingAppointments as $appointment)
                        <div class="rounded-md border border-med-line bg-white p-3">
                            <p class="font-semibold text-med-ink">{{ $appointment->patient?->demographic?->full_name ?? 'Unknown patient' }}</p>
                            <p class="mt-1 text-sm text-med-muted">{{ $appointment->appointment_date?->format('M d, Y H:i') ?? 'No date' }}</p>
                        </div>
                    @empty
                        <x-ui.empty-state title="No upcoming appointments" />
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </x-ui.page>
@endsection

