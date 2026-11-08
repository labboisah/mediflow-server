<div wire:poll.10s>
    <x-ui.card title="Investigation Requests" subtitle="Requests refresh automatically every 10 seconds.">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div wire:loading.delay class="rounded-md border border-med-info/20 bg-med-info/10 px-3 py-2 text-sm font-medium text-med-info">
                Updating records...
            </div>

            <div class="sm:ml-auto sm:w-96">
                <x-ui.input
                    type="search"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Search patient, hospital number, investigation, bill, status"
                />
            </div>
        </div>

        <x-ui.table class="shadow-none">
            <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Patient</th>
                    <th class="px-4 py-3">Hospital No.</th>
                    <th class="px-4 py-3">Investigation</th>
                    <th class="px-4 py-3">Requested</th>
                    <th class="px-4 py-3">Requested By</th>
                    <th class="px-4 py-3">Payment</th>
                    <th class="px-4 py-3">Completed</th>
                    <th class="px-4 py-3">Performed By</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-med-line bg-white">
                @forelse ($requests as $index => $investigationRequest)
                    @php
                        $patientName = $investigationRequest->bill?->patientName() ?? 'N/A';
                        $hospitalNumber = $investigationRequest->patientVisit?->patient?->hospital_number ?? 'Walk-in Patient';
                        $isPaid = strtolower((string) $investigationRequest->bill?->status) === 'paid';
                    @endphp

                    <tr wire:key="radiology-request-{{ $investigationRequest->id }}">
                        <td class="px-4 py-4 text-med-muted">{{ $requests->firstItem() + $index }}</td>
                        <td class="px-4 py-4 font-semibold text-med-ink">{{ $patientName }}</td>
                        <td class="px-4 py-4 text-med-muted">{{ $hospitalNumber }}</td>
                        <td class="px-4 py-4 text-med-muted">{{ $investigationRequest->investigation?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-4 text-med-muted">
                            {{ $investigationRequest->requested_at ? \Carbon\Carbon::parse($investigationRequest->requested_at)->format('d M Y h:i A') : 'N/A' }}
                        </td>
                        <td class="px-4 py-4 text-med-muted">{{ $investigationRequest->requestedBy?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-4">
                            <x-ui.badge :variant="$isPaid ? 'success' : 'danger'">
                                {{ $isPaid ? 'Paid' : 'No Payment' }}
                            </x-ui.badge>
                        </td>
                        <td class="px-4 py-4 text-med-muted">
                            {{ $investigationRequest->completed_at ? \Carbon\Carbon::parse($investigationRequest->completed_at)->format('d M Y h:i A') : 'N/A' }}
                        </td>
                        <td class="px-4 py-4 text-med-muted">{{ $investigationRequest->performedBy?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap justify-end gap-2">
                                @if ($isPaid)
                                    @if (auth()->user()->hasRole('radiographer') && ! $investigationRequest->diagnosticReport?->performed_at)
                                        <form method="POST" action="{{ route('radiology.requests.markPerformed', $investigationRequest) }}">@csrf <button class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">Mark performed</button></form>
                                    @endif
                                    @if (auth()->user()->hasRole('radiologist') && $investigationRequest->diagnosticReport?->performed_at)
                                        @if ($investigationRequest->diagnosticReport?->reported_at)
                                            <a href="{{ route('radiology.requests.editResult', $investigationRequest) }}" class="text-sm font-semibold text-med-info hover:text-blue-700">Amend report</a>
                                        @else
                                            <a href="{{ route('radiology.requests.createResult', $investigationRequest) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">Report</a>
                                        @endif
                                    @endif
                                    @if ($investigationRequest->completed_at || $investigationRequest->diagnosticReport?->performed_at)
                                        <a href="{{ route('radiology.requests.show', $investigationRequest) }}" class="text-sm font-semibold text-med-success hover:text-green-700">View / Print</a>
                                    @endif
                                @else
                                    <span class="text-sm font-medium text-med-muted">Awaiting payment</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8">
                            <x-ui.empty-state title="No Requests Found" message="Radiology requests with payment records will appear here." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </x-ui.card>
</div>

