@extends('layouts.modern')

@section('title', 'Patient History - ' . ($patient->demographic->full_name ?? 'Patient'))

@section('content')
<x-ui.page title="Patient History" subtitle="{{ $patient->demographic->full_name ?? 'Unknown Patient' }} | {{ $patient->hospital_number }}">
    <x-slot:actions>
        <a href="{{ route('patient.show', $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Back to Profile</a>
    </x-slot:actions>

    @if(\Illuminate\Support\Facades\Schema::hasTable('specialist_consultations') && auth()->user()->hasPermission('specialist_consultation.read') && app(\App\Services\SpecialistAccess::class)->patients(auth()->user())->whereKey($patient->id)->exists())
        <a class="text-med-primary underline" href="{{ route('clinical-history.specialist',$patient) }}">Specialist clinical history</a>
    @endif
    <div class="space-y-5">
        @forelse($visits as $visit)
            <x-ui.card title="Visit {{ optional($visit->visit_date)->format('M d, Y') ?? optional($visit->created_at)->format('M d, Y') }}" subtitle="{{ $visit->visit_type ?? 'Clinical visit' }} | {{ ucfirst($visit->status ?? 'active') }}">
                <div class="grid gap-4 md:grid-cols-3">
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Reason</p><p class="mt-1 text-sm text-med-ink">{{ $visit->reason_for_visit ?? 'N/A' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Created</p><p class="mt-1 text-sm text-med-ink">{{ optional($visit->created_at)->format('M d, Y h:i A') }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-med-muted">Status</p><p class="mt-1"><x-ui.badge :variant="$visit->status === 'discharged' ? 'neutral' : 'success'">{{ ucfirst($visit->status ?? 'active') }}</x-ui.badge></p></div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card>
                <x-ui.empty-state title="No Visit History" message="No visits have been recorded for this patient yet." />
            </x-ui.card>
        @endforelse
    </div>

    @if($visits->hasPages())
        <div>{{ $visits->links() }}</div>
    @endif
</x-ui.page>
@endsection
