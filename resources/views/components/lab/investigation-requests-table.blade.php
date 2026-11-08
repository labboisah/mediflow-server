<div wire:poll.10s>
    <x-ui.card title="Investigation Requests" subtitle="Live request list for the current laboratory department.">
        <div class="mb-5"><x-ui.input label="Search" type="search" wire:model.live.debounce.500ms="search" placeholder="Search patient, hospital number, investigation, status" /></div>
        <p wire:loading.delay class="mb-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">Updating records...</p>
        <x-ui.table class="shadow-none"><thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Hospital No</th><th class="px-4 py-3">Investigation</th><th class="px-4 py-3">Requested At</th><th class="px-4 py-3">Requested By</th><th class="px-4 py-3">Payment</th><th class="px-4 py-3">Completed At</th><th class="px-4 py-3">Performed By</th><th class="px-4 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y divide-med-line bg-white">
            @forelse ($requests as $index => $investigationRequest)
                @php
                    $patientName = $investigationRequest->bill?->patientName() ?? 'N/A';
                    $hospitalNumber = $investigationRequest->patientVisit?->patient?->hospital_number ?? 'Walk in Patient';
                @endphp
                <tr wire:key="investigation-request-{{ $investigationRequest->id }}"><td class="px-4 py-4 text-med-muted">{{ $requests->firstItem() + $index }}</td><td class="px-4 py-4 font-semibold text-med-ink">{{ $patientName }}</td><td class="px-4 py-4 text-med-muted">{{ $hospitalNumber }}</td><td class="px-4 py-4 text-med-muted">{{ $investigationRequest->investigation?->name ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $investigationRequest->requested_at ? \Carbon\Carbon::parse($investigationRequest->requested_at)->format('d M Y h:i A') : 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $investigationRequest->requestedBy?->name ?? 'N/A' }}</td><td class="px-4 py-4">@if ($investigationRequest->bill?->status === 'paid')<x-ui.badge variant="success">Paid</x-ui.badge>@else<x-ui.badge variant="danger">No Payment Recorded</x-ui.badge>@endif</td><td class="px-4 py-4 text-med-muted">{{ $investigationRequest->completed_at ? \Carbon\Carbon::parse($investigationRequest->completed_at)->format('d M Y h:i A') : 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $investigationRequest->performedBy?->name ?? 'N/A' }}</td><td class="px-4 py-4 text-right">@if ($investigationRequest->bill?->status === 'paid')<span class="text-sm text-med-muted">Use grouped result entry</span>@else<span class="text-sm text-med-muted">No Payment Recorded</span>@endif</td></tr>
            @empty
                <tr><td colspan="10" class="px-4 py-8"><x-ui.empty-state title="No Investigation Requests" message="No investigation requests matched the current search." /></td></tr>
            @endforelse
        </tbody></x-ui.table>
        <div class="mt-5">{{ $requests->links() }}</div>
    </x-ui.card>
</div>
