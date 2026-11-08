@extends('layouts.modern')

@section('title', 'Appointments')

@section('content')
<x-ui.page title="Appointments" subtitle="Review scheduled patient appointments across the facility.">
    <x-slot:actions>
        <a href="{{ route('record.patients.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
            Patient List
        </a>
    </x-slot:actions>

    <x-ui.card title="Scheduled Appointments" subtitle="{{ $appointments->total() }} appointment{{ $appointments->total() === 1 ? '' : 's' }} found.">
        @if($appointments->count() > 0)
            <x-ui.table>
                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                    <tr>
                        <th class="px-4 py-3">Patient</th>
                        <th class="px-4 py-3">Hospital No.</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Time</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Scheduled By</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-med-line bg-white">
                    @foreach($appointments as $appointment)
                        <tr class="hover:bg-med-canvas/60">
                            <td class="px-4 py-3 font-semibold text-med-ink">{{ $appointment->patient?->demographic?->full_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 font-semibold text-med-primary">{{ $appointment->patient?->hospital_number ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-med-muted">{{ $appointment->appointment_date?->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-med-muted">{{ $appointment->appointment_time ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge variant="{{ $appointment->status === 'Scheduled' ? 'success' : ($appointment->status === 'Cancelled' ? 'danger' : 'info') }}">
                                    {{ $appointment->status }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-med-muted">{{ $appointment->scheduledBy?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($appointment->patient)
                                    <a href="{{ route('record.patients.show', $appointment->patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">
                                        View Patient
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            @if($appointments->hasPages())
                <div class="mt-4">
                    {{ $appointments->links() }}
                </div>
            @endif
        @else
            <x-ui.empty-state title="No Appointments" message="Scheduled appointments will appear here after they are created from a patient profile." />
        @endif
    </x-ui.card>
</x-ui.page>
@endsection
