@php
    $sidebarUser = Auth::user();
    $sidebarService = app(\App\Services\SidebarService::class);
    $sidebarGroups = $sidebarService->groupsFor($sidebarUser);
    $dashboardRoute = $sidebarUser?->hasRole('medical_director') && app(\App\Services\LicenseService::class)->moduleEnabled('medical_director')
        ? route('medical-director.index')
        : ($sidebarUser?->hasRole('administrator') ? route('admin.index') : route('dashboard'));
    $dashboardLabel = 'Dashboard';

    $isRouteActive = fn (array $patterns) => collect($patterns)->contains(fn ($pattern) => request()->routeIs($pattern));
    $activitiesOpen = request()->routeIs('reports.activities.*');
    $canShowActivities = $sidebarUser && $sidebarService->canShowActivities($sidebarUser);
@endphp

<aside class="fixed inset-y-0 left-0 z-40 flex w-80 -translate-x-full flex-col border-r border-med-line bg-med-canvas px-4 py-4 transition-all duration-200 lg:translate-x-0"
       :class="{
            'translate-x-0': sidebarOpen,
            '-translate-x-full': ! sidebarOpen,
            'lg:w-24 lg:px-3': sidebarCollapsed,
            'lg:w-80 lg:px-4': ! sidebarCollapsed
       }">
    <div class="mb-5 flex shrink-0 items-center justify-between px-2">
        <a href="{{ $dashboardRoute }}" class="flex min-w-0 items-center gap-3">
            <img src="{{ app(\App\Services\SystemBranding::class)->logoUrl() }}" alt="{{ config('app.name') }}" class="h-10 w-10 rounded-md object-contain">
            <span class="min-w-0" x-show="! sidebarCollapsed">
                <span class="block text-lg font-bold text-med-ink">{{ config('app.name') }}</span>
                <span class="block text-sm font-medium text-med-muted">Healthcare operations</span>
            </span>
        </a>

        <button type="button"
                class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-md bg-white text-med-primary shadow-sm transition hover:bg-green-50 lg:inline-flex"
                @click="toggleSidebarCollapsed()"
                :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                aria-label="Toggle sidebar">
            <i class="bi text-lg" :class="sidebarCollapsed ? 'bi-layout-sidebar-inset' : 'bi-layout-sidebar-inset-reverse'"></i>
        </button>

        <button type="button"
                class="mf-focus inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-muted lg:hidden"
                @click="sidebarOpen = false"
                aria-label="Close sidebar">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <nav class="mf-sidebar-scrollbar min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-contain pb-8 pr-1">
        <a class="mf-sidebar-link {{ request()->routeIs('dashboard') || request()->routeIs('admin.index') || request()->routeIs('medical-director.index') ? 'mf-sidebar-link-active' : '' }}"
           href="{{ $dashboardRoute }}"
           title="{{ $dashboardLabel }}"
           :class="{ 'justify-center px-2': sidebarCollapsed }">
            <span class="mf-icon-box"><i class="bi bi-speedometer2"></i></span>
            <span x-show="! sidebarCollapsed">{{ $dashboardLabel }}</span>
        </a>

        @if($sidebarUser?->is_installation_admin)
            <a href="{{ route('admin.installation') }}" class="mf-sidebar-link {{ request()->routeIs('admin.installation') ? 'mf-sidebar-link-active' : '' }}" title="Installation Setup">
                <span class="mf-icon-box"><i class="bi bi-sliders"></i></span>
                <span x-show="! sidebarCollapsed">Installation Setup</span>
            </a>
        @endif

        @foreach($sidebarGroups as $group)
            @continue($group['key'] === 'dashboard')

            @php
                $groupOpen = $group['items']->contains(fn ($item) => $isRouteActive($sidebarService->patternsFor($item)))
                    || ($group['key'] === 'reports' && $activitiesOpen);
            @endphp

            <div x-data="{ open: {{ $groupOpen ? 'true' : 'false' }} }" class="pt-1">
                <button type="button"
                        class="mf-sidebar-toggle"
                        :class="{ 'mf-sidebar-toggle-active': open, 'justify-center px-2': sidebarCollapsed }"
                        @click="sidebarCollapsed ? (expandSidebar(), open = true) : open = ! open"
                        title="{{ $group['label'] }}">
                    <span class="flex items-center gap-3">
                        <span class="mf-icon-box"><i class="bi {{ $group['icon'] }}"></i></span>
                        <span x-show="! sidebarCollapsed">{{ $group['label'] }}</span>
                    </span>
                    <i x-show="! sidebarCollapsed" class="bi bi-chevron-down text-xs text-med-muted transition" :class="{ 'rotate-180': open }"></i>
                </button>

                <div x-show="open && ! sidebarCollapsed" class="ml-6 mt-1 space-y-1 border-l border-med-line pl-4">
                    @foreach($group['items'] as $item)
                        @php($patterns = $sidebarService->patternsFor($item))
                        <a class="mf-sidebar-link {{ $isRouteActive($patterns) ? 'mf-sidebar-link-active' : '' }}"
                           href="{{ route($item->route) }}"
                           title="{{ $item->label }}"
                           @click="sidebarOpen = false">
                            <i class="bi {{ $item->icon }} w-5 text-center text-med-primary"></i>
                            <span>{{ $item->label }}</span>
                        </a>
                    @endforeach

                    @if($group['key'] === 'reports' && $canShowActivities)
                        <div x-data="{ activitiesOpen: {{ $activitiesOpen ? 'true' : 'false' }} }">
                            <button type="button"
                                    class="mf-sidebar-toggle"
                                    :class="{ 'mf-sidebar-toggle-active': activitiesOpen }"
                                    @click="activitiesOpen = ! activitiesOpen">
                                <span class="flex items-center gap-3">
                                    <i class="bi bi-activity w-5 text-center text-med-primary"></i>
                                    <span>Activities</span>
                                </span>
                                <i class="bi bi-chevron-down text-xs text-med-muted transition" :class="{ 'rotate-180': activitiesOpen }"></i>
                            </button>

                            <div x-show="activitiesOpen" class="ml-4 max-h-72 space-y-1 overflow-y-auto border-l border-med-line pl-4">
                                @foreach(\App\Models\Department::orderBy('name')->get(['id', 'name']) as $activityDepartment)
                                    <a class="mf-sidebar-link {{ request()->routeIs('reports.activities.*') && request()->route('department')?->id === $activityDepartment->id ? 'mf-sidebar-link-active' : '' }}"
                                       href="{{ route('reports.activities.show', $activityDepartment) }}"
                                       @click="sidebarOpen = false">
                                        <i class="bi bi-building w-5 text-center text-med-primary"></i>
                                        <span>{{ $activityDepartment->name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </nav>
</aside>
