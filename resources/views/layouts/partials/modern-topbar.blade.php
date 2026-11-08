<header class="sticky top-0 z-20 border-b border-med-line bg-white/90 backdrop-blur">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button"
                    class="mf-focus inline-flex h-10 w-10 items-center justify-center rounded-md border border-med-line bg-white text-med-muted lg:hidden"
                    @click="sidebarOpen = true"
                    aria-label="Open sidebar">
                <i class="bi bi-list text-xl"></i>
            </button>

            <div class="min-w-0">
                <p class="truncate text-base font-semibold text-med-ink">{{ $modernPageTitle ?? 'Workspace' }}</p>
                <p class="truncate text-sm text-med-muted">{{ $modernPageSubtitle ?? config('app.name') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if(app(\App\Services\LicenseService::class)->moduleEnabled('reports'))
            <a href="{{ route('reports.my-activities.index') }}" class="mf-focus hidden rounded-md border border-med-line bg-white px-3 py-2 text-sm font-medium text-med-muted hover:text-med-primary sm:inline-flex">
                My Activities
            </a>
            @endif

            <div x-data="{ open: false }" class="relative">
                <button type="button" class="mf-focus flex items-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink" @click="open = ! open">
                    <span class="hidden max-w-40 truncate sm:inline">{{ Auth::user()->name }}</span>
                    <i class="bi bi-person-circle text-lg text-med-primary"></i>
                </button>

                <div x-show="open"
                     x-transition
                     x-cloak
                     @click.outside="open = false"
                     class="absolute right-0 mt-2 w-56 rounded-md border border-med-line bg-white py-2 shadow-panel">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-med-muted hover:bg-med-canvas hover:text-med-primary">Profile</a>
                    @if(app(\App\Services\LicenseService::class)->moduleEnabled('reports'))
                    <a href="{{ route('reports.my-activities.index') }}" class="block px-4 py-2 text-sm text-med-muted hover:bg-med-canvas hover:text-med-primary">My Activities</a>
                    @endif
                    <div class="my-2 border-t border-med-line"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-med-danger hover:bg-med-canvas">Log Out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
