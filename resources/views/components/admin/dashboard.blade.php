@php
    $maxVisitCount = max($visitStatusRows->max('count') ?: 1, 1);
    $maxBillCount = max($billStatusRows->max('count') ?: 1, 1);
    $dashboardTitle = $pageTitle ?? 'Dashboard';
    $dashboardSubtitle = $pageSubtitle ?? config('app.name');

    $metricCards = [
        [
            'label' => 'Patients',
            'value' => number_format($hospitalMetrics['patients']),
            'meta' => number_format($hospitalMetrics['walkin_patients']) . ' walk-in',
            'icon' => 'bi-people-fill',
            'tone' => 'text-med-primary',
        ],
        [
            'label' => 'Active Visits',
            'value' => number_format($hospitalMetrics['active_visits']),
            'meta' => number_format($hospitalMetrics['today_visits']) . ' today',
            'icon' => 'bi-clipboard-pulse',
            'tone' => 'text-med-info',
        ],
    ];

    if ($canViewTechnicalRecords) {
        $metricCards[] = [
            'label' => 'Collected Today',
            'value' => number_format($financeMetrics['collected_today'], 2),
            'meta' => number_format($financeMetrics['payments_today']) . ' payments',
            'icon' => 'bi-cash-coin',
            'tone' => 'text-med-primary',
        ];
        $metricCards[] = [
            'label' => 'Sync Queue',
            'value' => number_format($syncMetrics['pending']),
            'meta' => number_format($syncMetrics['failed']) . ' failed',
            'icon' => 'bi-cloud-arrow-up',
            'tone' => $syncMetrics['failed'] > 0 ? 'text-med-danger' : 'text-med-accent',
        ];
    }
@endphp

<div wire:poll.10s>
    <x-ui.page :title="$dashboardTitle" :subtitle="$dashboardSubtitle">
        <x-slot:actions>
            <div class="rounded-md border border-med-line bg-white px-3 py-2 text-right text-xs text-med-muted shadow-sm">
                <div wire:ignore>
                    <i class="bi bi-calendar-event mr-1 text-med-primary"></i>
                    <span id="admin-dashboard-local-date">{{ $lastUpdated->format('F j, Y') }}</span>
                </div>
                <div wire:ignore>
                    <i class="bi bi-clock mr-1 text-med-primary"></i>
                    <span id="admin-dashboard-local-time">{{ $lastUpdated->format('h:i:s A') }}</span>
                </div>
                <div>
                    <i class="bi bi-arrow-repeat mr-1 text-med-primary"></i>
                    Refreshed {{ $lastUpdated->format('h:i:s A') }}
                </div>
            </div>
        </x-slot:actions>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($metricCards as $card)
                <div class="rounded-md border border-med-line bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-base font-medium text-med-muted">{{ $card['label'] }}</p>
                            <p class="mt-3 text-4xl font-semibold leading-tight text-med-ink">{{ $card['value'] }}</p>
                            <p class="mt-2 text-base text-med-muted">{{ $card['meta'] }}</p>
                        </div>
                        <span class="mf-icon-box {{ $card['tone'] }}">
                            <i class="bi {{ $card['icon'] }} text-xl"></i>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            @if($canViewTechnicalRecords)
                <x-ui.card>
                    <p class="text-base font-medium text-med-muted">Bills</p>
                    <p class="mt-3 text-3xl font-semibold leading-tight text-med-ink">{{ number_format($financeMetrics['bills']) }}</p>
                    <p class="mt-2 text-base text-med-muted">{{ number_format($financeMetrics['open_bills']) }} open, {{ number_format($financeMetrics['today_bills']) }} today</p>
                </x-ui.card>

                <x-ui.card>
                    <p class="text-base font-medium text-med-muted">Today Billed</p>
                    <p class="mt-3 text-3xl font-semibold leading-tight text-med-ink">{{ number_format($financeMetrics['total_billed_today'], 2) }}</p>
                    <p class="mt-2 text-base text-med-muted">{{ number_format($financeMetrics['payments']) }} total payments</p>
                </x-ui.card>

                <x-ui.card>
                    <p class="text-base font-medium text-med-muted">Access Control</p>
                    <p class="mt-3 text-3xl font-semibold leading-tight text-med-ink">{{ number_format($accessMetrics['users']) }} users</p>
                    <p class="mt-2 text-base text-med-muted">{{ number_format($accessMetrics['roles']) }} roles, {{ number_format($accessMetrics['permissions']) }} permissions</p>
                </x-ui.card>
            @endif

            <x-ui.card>
                <p class="text-base font-medium text-med-muted">Bed Occupancy</p>
                <p class="mt-3 text-3xl font-semibold leading-tight text-med-ink">{{ number_format($setupMetrics['occupied_beds']) }} / {{ number_format($setupMetrics['beds']) }}</p>
                <p class="mt-2 text-base text-med-muted">{{ number_format($setupMetrics['wards']) }} wards</p>
            </x-ui.card>
        </div>

        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-7" title="Live Patient Flow">
                <x-slot:actions>
                    <x-ui.badge variant="success">Polling every 10s</x-ui.badge>
                </x-slot:actions>

                <div class="space-y-4">
                    @forelse($visitStatusRows as $row)
                        @php $width = max(5, ($row->count / $maxVisitCount) * 100); @endphp
                        <div>
                            <div class="mb-2 flex justify-between text-base">
                                <span class="font-medium text-med-ink">{{ $row->label }}</span>
                                <strong class="text-med-muted">{{ number_format($row->count) }}</strong>
                            </div>
                            <div class="h-4 overflow-hidden rounded-full bg-med-canvas">
                                <div class="h-full rounded-full bg-med-primary" style="width: {{ $width }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state title="No visit data yet" />
                    @endforelse
                </div>
            </x-ui.card>

            @if($canViewTechnicalRecords)
                <x-ui.card class="xl:col-span-5" title="Bill Status">
                    <div class="space-y-4">
                        @forelse($billStatusRows as $row)
                            @php $width = max(5, ($row->count / $maxBillCount) * 100); @endphp
                            <div>
                                <div class="mb-2 flex justify-between text-base">
                                    <span class="font-medium text-med-ink">{{ ucfirst($row->label) }}</span>
                                    <strong class="text-med-muted">{{ number_format($row->count) }}</strong>
                                </div>
                                <div class="h-4 overflow-hidden rounded-full bg-med-canvas">
                                    <div class="h-full rounded-full bg-med-info" style="width: {{ $width }}%;"></div>
                                </div>
                                <p class="mt-2 text-right text-sm text-med-muted">{{ number_format($row->amount ?? 0, 2) }}</p>
                            </div>
                        @empty
                            <x-ui.empty-state title="No bill data yet" />
                        @endforelse
                    </div>
                </x-ui.card>
            @endif
        </div>

        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-4" title="Management Snapshot">
                <div class="divide-y divide-med-line text-base">
                    <div class="flex justify-between gap-4 py-4"><span class="text-med-muted">Departments</span><strong>{{ number_format($setupMetrics['departments']) }}</strong></div>
                    <div class="flex justify-between gap-4 py-4"><span class="text-med-muted">Services</span><strong>{{ number_format($setupMetrics['services']) }}</strong></div>
                    <div class="flex justify-between gap-4 py-4"><span class="text-med-muted">Investigations</span><strong>{{ number_format($setupMetrics['investigations']) }}</strong></div>
                    @if($canViewTechnicalRecords)
                        <div class="flex justify-between gap-4 py-4"><span class="text-med-muted">Administrators</span><strong>{{ number_format($accessMetrics['administrators']) }}</strong></div>
                        <div class="flex justify-between gap-4 py-4"><span class="text-med-muted">Temporary Permissions</span><strong>{{ number_format($accessMetrics['temporary_permissions']) }}</strong></div>
                    @endif
                </div>
            </x-ui.card>

            @if($canViewTechnicalRecords)
                <x-ui.card class="xl:col-span-8" title="Recent System Activity">
                    <x-slot:actions>
                        @if($syncMetrics['latest'])
                            <span class="text-xs text-med-muted">Latest sync: {{ $syncMetrics['latest']->updated_at?->diffForHumans() }}</span>
                        @endif
                    </x-slot:actions>

                    <div class="max-h-[430px] divide-y divide-med-line overflow-y-auto">
                        @forelse($recentActivities as $activity)
                            @php
                                $actionLabel = ucwords(str_replace(['.', '_'], [' ', ' '], $activity->action));
                                $modelLabel = $activity->model_type ? class_basename($activity->model_type) : null;
                            @endphp
                            <div class="flex flex-col gap-2 py-5 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-base font-semibold text-med-ink">{{ $activity->actor?->name ?? 'System' }}</p>
                                    <p class="mt-1 text-base leading-6 text-med-muted">
                                        {{ $actionLabel }}
                                        @if($modelLabel)
                                            on {{ $modelLabel }}
                                            @if($activity->model_id)
                                                #{{ $activity->model_id }}
                                            @endif
                                        @endif
                                    </p>
                                </div>
                                <div class="shrink-0 text-left text-sm leading-6 text-med-muted sm:text-right">
                                    <p>{{ $activity->created_at?->format('M j, h:i A') }}</p>
                                    <p>{{ $activity->created_at?->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty-state title="No recent activity yet" />
                        @endforelse
                    </div>
                </x-ui.card>
            @endif
        </div>
    </x-ui.page>
</div>

@push('scripts')
    <script>
        function updateAdminDashboardLocalClock() {
            const dateElement = document.getElementById('admin-dashboard-local-date');
            const timeElement = document.getElementById('admin-dashboard-local-time');

            if (!dateElement || !timeElement) {
                return;
            }

            const now = new Date();

            dateElement.textContent = now.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });

            timeElement.textContent = now.toLocaleTimeString(undefined, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        }

        updateAdminDashboardLocalClock();
        setInterval(updateAdminDashboardLocalClock, 1000);
    </script>
@endpush

