@php
    $pendingPrescriptions = \App\Models\Prescription::with(['patientVisit.patient.demographic', 'prescribedBy', 'prescriptionItems.medicine.batches'])->where('status', 'submitted')->latest()->limit(10)->get();
    $todayPrescriptions = \App\Models\Prescription::whereDate('created_at', today())->count();
    $pendingPrescriptionCount = \App\Models\Prescription::where('status', 'submitted')->count();
    $dispensedToday = \App\Models\PharmacyDispense::whereDate('created_at', today())->count();
    $pharmacyDashboardUser = auth()->user();
    $pharmacyDashboardDepartment = strtolower((string) $pharmacyDashboardUser?->department?->name);
    $canManageInventory = $pharmacyDashboardUser?->hasRole('pharmacist') || ($pharmacyDashboardUser?->hasRole('head_of_department') && str_contains($pharmacyDashboardDepartment, 'pharmacy'));
    $lowStockCount = $canManageInventory ? \App\Models\MedicineBatch::where('quantity_remaining', '<=', 10)->count() : 0;
    $expiringBatches = $canManageInventory ? \App\Models\MedicineBatch::with('medicine')->whereDate('expiry_date', '>=', today())->whereDate('expiry_date', '<=', today()->addYears(3))->orderBy('expiry_date')->limit(10)->get() : collect();
@endphp

<x-ui.page title="Pharmacy Dashboard" subtitle="Prescription queue, dispensing activity, and pharmacy inventory alerts.">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.card><p class="text-sm font-medium text-med-muted">Prescriptions Today</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($todayPrescriptions) }}</p></x-ui.card>
        <x-ui.card><p class="text-sm font-medium text-med-muted">Pending Prescriptions</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($pendingPrescriptionCount) }}</p></x-ui.card>
        <x-ui.card><p class="text-sm font-medium text-med-muted">Dispensed Today</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($dispensedToday) }}</p></x-ui.card>
        @if($canManageInventory)<x-ui.card><p class="text-sm font-medium text-med-muted">Low Stock Medicines</p><p class="mt-2 text-2xl font-bold text-med-danger">{{ number_format($lowStockCount) }}</p></x-ui.card>@endif
    </div>

    <div class="grid gap-6 xl:grid-cols-12">
        <x-ui.card class="{{ $canManageInventory ? 'xl:col-span-8' : 'xl:col-span-12' }}" title="Recent Prescriptions">
            <x-ui.table class="shadow-none"><thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Medicine</th><th class="px-4 py-3">Doctor</th><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-med-line bg-white">@forelse($pendingPrescriptions as $prescription) @php $patient = $prescription->patientVisit?->patient; $items = $prescription->prescriptionItems; @endphp <tr><td class="px-4 py-4 font-semibold text-med-ink">{{ $patient?->demographic?->full_name ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $items->pluck('medicine.name')->filter()->implode(', ') ?: 'No medicine' }}</td><td class="px-4 py-4 text-med-muted">{{ $prescription->prescribedBy?->name ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $prescription->created_at?->format('d M Y') }}</td><td class="px-4 py-4 text-right"><a href="{{ route('patient.prescription.show', $prescription) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">View</a></td></tr> @empty <tr><td colspan="5" class="px-4 py-8"><x-ui.empty-state title="No Prescriptions" message="Submitted prescriptions will appear here." /></td></tr> @endforelse</tbody></x-ui.table>
        </x-ui.card>

        @if($canManageInventory)
            <x-ui.card class="xl:col-span-4" title="Expiry Alerts">
                <div class="divide-y divide-med-line">@forelse($expiringBatches as $drug)<div class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"><div><p class="font-semibold text-med-ink">{{ $drug->medicine?->name ?? 'N/A' }}</p><p class="text-sm text-med-muted">{{ $drug->batch_number }}</p></div><x-ui.badge :variant="$drug->expiryBadgeVariant()">{{ $drug->expiry_date }}</x-ui.badge></div>@empty<x-ui.empty-state title="No Expiry Alerts" />@endforelse</div>
            </x-ui.card>
        @endif
    </div>
</x-ui.page>
