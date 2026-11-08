<section class="space-y-7">
    <div class="flex flex-col gap-5 border-b border-med-line pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-3xl font-semibold leading-tight text-med-ink">{{ $pageTitle ?? 'Patient Management' }}</h1>
            <p class="mt-2 max-w-3xl text-base leading-6 text-med-muted">{{ $pageSubtitle ?? 'Search, filter, and open patient records quickly.' }}</p>
        </div>

        @if($canManageRecords)
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <a href="{{ route('record.patients.register.form') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
                    <i class="bi bi-person-plus"></i>
                    Register Patient
                </a>
            </div>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.card>
            <p class="text-sm font-medium text-med-muted">Total Patients</p>
            <p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($totalPatients) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm font-medium text-med-muted">Filtered Result</p>
            <p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($filteredCount) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm font-medium text-med-muted">Registered Today</p>
            <p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($registeredToday) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm font-medium text-med-muted">Walk-in Patients</p>
            <p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($walkInCount) }}</p>
        </x-ui.card>
    </div>

    @if($feedbackMessage)
        <div class="rounded-md border px-4 py-3 text-sm {{ $feedbackMessage['type'] === 'danger' ? 'border-red-200 bg-red-50 text-red-700' : 'border-orange-200 bg-orange-50 text-orange-800' }}">
            <i class="bi bi-info-circle"></i>
            {{ $feedbackMessage['message'] }}
        </div>
    @endif

    <x-ui.card>
        <div class="grid gap-4 xl:grid-cols-12 xl:items-end">
            <div class="xl:col-span-4">
                <x-ui.input label="Search Patient" type="search" wire:model.live.debounce.400ms="search" placeholder="Hospital no, name, phone, email" />
            </div>
            <div class="xl:col-span-2">
                <x-ui.select label="Gender" wire:model.live="gender">
                    <option value="">All</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </x-ui.select>
            </div>
            <div class="xl:col-span-2">
                <x-ui.select label="Type" wire:model.live="patientType">
                    <option value="">All</option>
                    <option value="registered">Registered</option>
                    <option value="walk_in">Walk-in</option>
                </x-ui.select>
            </div>
            <div class="xl:col-span-1">
                <x-ui.input label="From" type="date" wire:model.live="dateFrom" />
            </div>
            <div class="xl:col-span-1">
                <x-ui.input label="To" type="date" wire:model.live="dateTo" />
            </div>
            <div class="xl:col-span-1">
                <x-ui.select label="Rows" wire:model.live="perPage">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </x-ui.select>
            </div>
            <div class="xl:col-span-1">
                <x-ui.button type="button" variant="secondary" class="w-full" wire:click="clearFilters" :disabled="! $hasActiveFilters" title="Clear filters">
                    <i class="bi bi-x-circle"></i>
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>

    <x-ui.table>
        <thead class="bg-med-canvas">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">
                    <button type="button" class="inline-flex items-center gap-1 font-semibold" wire:click="sortBy('hospital_number')">
                        Hospital No
                        @if($sortField === 'hospital_number')
                            <i class="bi bi-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Patient</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Gender</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Age</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Phone</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">
                    <button type="button" class="inline-flex items-center gap-1 font-semibold" wire:click="sortBy('registration_date')">
                        Registered
                        @if($sortField === 'registration_date')
                            <i class="bi bi-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Status</th>
                <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-med-line">
            @forelse($patients as $patient)
                <tr class="hover:bg-med-canvas" wire:key="patient-{{ $patient->id }}">
                    <td class="px-4 py-4"><x-ui.badge variant="info">{{ $patient->hospital_number }}</x-ui.badge></td>
                    <td class="px-4 py-4">
                        <p class="font-semibold text-med-ink">{{ $patient->demographic->full_name ?? 'N/A' }}</p>
                        <p class="mt-1 text-sm text-med-muted">{{ $patient->fileType->name ?? 'General file' }}</p>
                    </td>
                    <td class="px-4 py-4 text-sm text-med-muted">{{ $patient->demographic->gender ?? 'N/A' }}</td>
                    <td class="px-4 py-4 text-sm text-med-muted">{{ $patient->demographic->age ?? 'N/A' }}</td>
                    <td class="px-4 py-4 text-sm text-med-muted">{{ $patient->demographic->phone_number ?? 'N/A' }}</td>
                    <td class="px-4 py-4 text-sm text-med-muted">{{ optional($patient->registration_date)->format('M d, Y') ?? 'N/A' }}</td>
                    <td class="px-4 py-4">
                        @if($patient->is_walkIn)
                            <x-ui.badge variant="warning">Walk-in</x-ui.badge>
                        @else
                            <x-ui.badge variant="success">Registered</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex justify-end gap-2">
                            @if($canManageRecords)
                                <a href="{{ route('record.patients.show', $patient) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-muted hover:bg-med-canvas" title="View patient">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('record.patients.edit.form', $patient) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-info hover:bg-med-canvas" title="Edit patient">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @else
                                <a href="{{ route('patient.show', $patient) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 text-sm font-semibold text-med-primary hover:bg-med-canvas">
                                    <i class="bi bi-eye"></i>
                                    View
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-8">
                        <x-ui.empty-state title="No patients match the current filters">
                            @if($hasActiveFilters)
                                <x-ui.button type="button" variant="secondary" wire:click="clearFilters">Clear filters</x-ui.button>
                            @elseif($canManageRecords)
                                <a href="{{ route('record.patients.register.form') }}">
                                    <x-ui.button type="button">Register First Patient</x-ui.button>
                                </a>
                            @endif
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    @if($patients->hasPages())
        <div>
            {{ $patients->links() }}
        </div>
    @endif
</section>



