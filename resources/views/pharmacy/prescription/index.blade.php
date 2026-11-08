@extends('layouts.modern')

@section('title', 'Pharmacy Prescriptions')

@section('content')
    <x-ui.page title="Prescriptions" subtitle="Submitted prescriptions awaiting pharmacy action.">
        <x-slot:actions><a href="{{ route('pharmacy.transactions.create') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-med-primaryDark"><i class="bi bi-receipt"></i>New Transaction</a></x-slot:actions>
        <x-ui.card title="Prescription Queue" subtitle="{{ $prescriptions->total() }} submitted prescription{{ $prescriptions->total() === 1 ? '' : 's' }} found.">
            <x-ui.table class="shadow-none"><thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Doctor</th><th class="px-4 py-3">Medicines</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-med-line bg-white">
                @forelse($prescriptions as $prescription)
                    @php $patient = $prescription->patientVisit?->patient; $items = $prescription->prescriptionItems; $amount = $items->sum(fn($item) => $item->medicine?->latestSellingPrice() ?? 0); @endphp
                    <tr><td class="px-4 py-4"><p class="font-semibold text-med-ink">{{ $patient?->demographic?->full_name ?? 'N/A' }}</p><p class="text-sm text-med-muted">{{ $patient?->hospital_number ?? '' }}</p></td><td class="px-4 py-4 text-med-muted">{{ $prescription->prescribedBy?->name ?? 'N/A' }}<p class="text-sm">{{ $prescription->prescribedBy?->department?->name ?? '' }}</p></td><td class="px-4 py-4 text-med-muted">{{ $items->pluck('medicine.name')->filter()->take(4)->implode(', ') ?: 'No medicine' }}@if($items->count() > 4)<span class="text-med-muted"> +{{ $items->count() - 4 }} more</span>@endif</td><td class="px-4 py-4 text-right font-semibold text-med-ink">&#8358;{{ number_format($amount, 2) }}</td><td class="px-4 py-4 text-med-muted">{{ $prescription->created_at?->format('M d, Y h:i A') }}</td><td class="px-4 py-4 text-right"><a href="{{ route('pharmacy.prescriptions.show', $prescription) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">View</a></td></tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8"><x-ui.empty-state title="No Submitted Prescriptions" message="Submitted prescriptions awaiting pharmacy action will appear here." /></td></tr>
                @endforelse
            </tbody></x-ui.table>
            @if($prescriptions->hasPages())<div class="mt-5">{{ $prescriptions->links() }}</div>@endif
        </x-ui.card>
    </x-ui.page>
@endsection
