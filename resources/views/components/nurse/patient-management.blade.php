<div wire:poll.visible.15s="refreshList">
    <x-ui.page :title="$pageTitle ?? 'Workspace'" :subtitle="$pageSubtitle ?? config('app.name')">
        <x-slot:actions>
            <button type="button" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas" wire:click="resetFilters">
                Reset Filters
            </button>
        </x-slot:actions>

        <div class="space-y-6">
            <x-ui.card title="Patient Search" subtitle="Search all registered patients, including those without a current department request.">
                <x-ui.input
                    label="Search all patients"
                    name="clinical_patient_search"
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Hospital number, patient name, phone, email, or next of kin"
                />
            </x-ui.card>

            @if(trim($search) !== '')
                <x-ui.card title="All Patient Search Results" subtitle="{{ $allPatients->count() }} patient{{ $allPatients->count() === 1 ? '' : 's' }} found.">
                    @if($allPatients->count() > 0)
                        <x-ui.table>
                            <thead class="bg-med-canvas text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                                <tr>
                                    <th class="px-4 py-3">Patient</th>
                                    <th class="px-4 py-3">Contact</th>
                                    <th class="px-4 py-3">Next Of Kin</th>
                                    <th class="px-4 py-3">Last Visit</th>
                                    <th class="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-med-line bg-white">
                                @foreach($allPatients as $patient)
                                    @php
                                        $demographic = $patient->demographic;
                                        $nextOfKin = $patient->nextOfKin;
                                        $lastVisit = $patient->patientVisits->first();
                                    @endphp
                                    <tr wire:key="clinical-all-patient-search-{{ $profileRoute }}-{{ $patient->id }}" class="hover:bg-med-canvas/60">
                                        <td class="px-4 py-3"><p class="font-semibold text-med-ink">{{ $demographic?->full_name ?? 'N/A' }}</p><p class="mt-1 text-xs text-med-muted">{{ $patient->hospital_number ?? 'N/A' }}</p></td>
                                        <td class="px-4 py-3"><p class="text-med-ink">{{ $demographic?->phone_number ?? 'N/A' }}</p><p class="mt-1 text-xs text-med-muted">{{ $demographic?->gender ?? 'N/A' }}@if($demographic?->date_of_birth), {{ $demographic->date_of_birth->age }} yrs @endif</p></td>
                                        <td class="px-4 py-3"><p class="text-med-ink">{{ $nextOfKin?->name ?? 'N/A' }}</p><p class="mt-1 text-xs text-med-muted">{{ $nextOfKin?->telephone ?? 'N/A' }}</p></td>
                                        <td class="px-4 py-3"><p class="text-med-ink">{{ $lastVisit?->visit_type ?? 'No visit' }}</p><p class="mt-1 text-xs text-med-muted">{{ $lastVisit?->created_at?->format('M d, Y') ?? '' }}</p></td>
                                        <td class="px-4 py-3 text-right"><a href="{{ route($profileRoute, $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">View Profile</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    @else
                        <x-ui.empty-state title="No Patients Found" message="No registered patients match this search." />
                    @endif
                </x-ui.card>
            @endif

            @if(! auth()->user()->department_id)
                <div class="rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                    Your account is not attached to a department, so patient requests cannot be loaded.
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <x-ui.card><p class="text-sm font-medium text-med-muted">Filtered Requests</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['total']) }}</p></x-ui.card>
                    <x-ui.card><p class="text-sm font-medium text-med-muted">Pending</p><p class="mt-2 text-2xl font-bold text-orange-700">{{ number_format($summary['pending']) }}</p></x-ui.card>
                    <x-ui.card><p class="text-sm font-medium text-med-muted">Completed</p><p class="mt-2 text-2xl font-bold text-med-primary">{{ number_format($summary['completed']) }}</p></x-ui.card>
                    <x-ui.card><p class="text-sm font-medium text-med-muted">Active Visits</p><p class="mt-2 text-2xl font-bold text-med-info">{{ number_format($summary['activeVisits']) }}</p></x-ui.card>
                </div>

                <x-ui.card title="Department Requests" subtitle="Auto-refreshes every 15 seconds while this page is visible.">
                    <div class="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <x-ui.select label="Request Status" name="requestStatus" wire:model.live="requestStatus">
                            <option value="">All Requests</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                        </x-ui.select>
                        <x-ui.select label="Visit Status" name="visitStatus" wire:model.live="visitStatus">
                            <option value="">All Visits</option>
                            <option value="Active">Active</option>
                            <option value="Closed">Closed</option>
                        </x-ui.select>
                        <x-ui.select label="Service" name="serviceId" wire:model.live="serviceId">
                            <option value="">All Services</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.select label="Rows" name="perPage" wire:model.live="perPage">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </x-ui.select>
                    </div>

                    @if($requests->count() > 0)
                        <x-ui.table>
                            <thead class="bg-med-canvas text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                                <tr>
                                    <th class="px-4 py-3">Patient</th>
                                    <th class="px-4 py-3">Service</th>
                                    <th class="px-4 py-3">Visit</th>
                                    <th class="px-4 py-3">Contact</th>
                                    <th class="px-4 py-3">Next Of Kin</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-med-line bg-white">
                                @foreach($requests as $request)
                                    @php
                                        $visit = $request->patientVisit;
                                        $patient = $visit?->patient;
                                        $demographic = $patient?->demographic;
                                        $nextOfKin = $patient?->nextOfKin;
                                        $currentRequestStatus = strtolower((string) $request->status);
                                        $currentVisitStatus = (string) ($visit?->status ?? 'N/A');
                                    @endphp
                                    <tr wire:key="clinical-request-{{ $profileRoute }}-{{ $request->id }}" class="hover:bg-med-canvas/60">
                                        <td class="px-4 py-3"><p class="font-semibold text-med-ink">{{ $demographic?->full_name ?? 'N/A' }}</p><p class="mt-1 text-xs text-med-muted">{{ $patient?->hospital_number ?? 'N/A' }}</p></td>
                                        <td class="px-4 py-3"><p class="text-med-ink">{{ $request->service?->name ?? 'N/A' }}</p><p class="mt-1 text-xs text-med-muted">{{ $request->requested_at ? $request->requested_at->format('M d, Y h:i A') : $request->created_at?->format('M d, Y h:i A') }}</p></td>
                                        <td class="px-4 py-3"><p class="text-med-ink">{{ $visit?->visit_type ?? 'N/A' }}</p><p class="mt-1 text-xs text-med-muted">{{ $visit?->visit_date?->format('M d, Y') ?? $visit?->created_at?->format('M d, Y') ?? 'N/A' }}</p></td>
                                        <td class="px-4 py-3"><p class="text-med-ink">{{ $demographic?->phone_number ?? 'N/A' }}</p><p class="mt-1 text-xs text-med-muted">{{ $demographic?->gender ?? 'N/A' }}@if($demographic?->date_of_birth), {{ $demographic->date_of_birth->age }} yrs @endif</p></td>
                                        <td class="px-4 py-3"><p class="text-med-ink">{{ $nextOfKin?->name ?? 'N/A' }}</p><p class="mt-1 text-xs text-med-muted">{{ $nextOfKin?->telephone ?? 'N/A' }}</p></td>
                                        <td class="px-4 py-3"><div class="space-y-1"><x-ui.badge variant="{{ $currentRequestStatus === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($request->status ?? 'pending') }}</x-ui.badge><br><x-ui.badge variant="{{ $currentVisitStatus === 'Active' ? 'info' : 'neutral' }}">{{ $currentVisitStatus }}</x-ui.badge></div></td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                @if($patient)
                                                    <a href="{{ route($profileRoute, $patient) }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">View</a>
                                                @endif
                                                @if($currentRequestStatus !== 'completed')
                                                    <button type="button" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-white px-3 py-2 text-sm font-semibold text-med-primary transition hover:bg-green-50" wire:click="completeRequest({{ $request->id }})" wire:confirm="Mark this service request as completed?">Complete</button>
                                                @endif
                                                @if($visit && $currentVisitStatus === 'Active')
                                                    <button type="button" class="mf-focus inline-flex items-center justify-center rounded-md border border-orange-200 bg-white px-3 py-2 text-sm font-semibold text-orange-700 transition hover:bg-orange-50" wire:click="closeVisit({{ $visit->id }})" wire:confirm="Close this patient visit?">Close</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>

                        <div class="mt-4">{{ $requests->links() }}</div>
                    @else
                        <x-ui.empty-state title="No Requests Found" :message="$emptyMessage" />
                    @endif
                </x-ui.card>
            @endif
        </div>
    </x-ui.page>
</div>

