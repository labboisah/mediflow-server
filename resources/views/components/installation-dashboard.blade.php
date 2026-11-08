<div>
    <x-ui.page title="Dashboard" :subtitle="$packageLabel . ' workspace'">
        @if(auth()->user()?->is_installation_admin)
            <x-slot:actions>
                <a href="{{ route('admin.installation') }}" class="mf-focus rounded-md bg-med-primary px-4 py-2 text-sm font-semibold text-white">Installation Setup</a>
            </x-slot:actions>
        @endif
        @forelse($groups as $group)
            @continue($group['key'] === 'dashboard')
            <x-ui.card :title="$group['label']">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($group['items'] as $item)
                        <a href="{{ route($item->route) }}" class="mf-focus flex items-center gap-3 rounded-md border border-med-line p-4 text-sm font-semibold text-med-ink hover:bg-med-canvas">
                            <i class="bi {{ $item->icon }} text-med-primary"></i>{{ $item->label }}
                        </a>
                    @endforeach
                </div>
            </x-ui.card>
        @empty
            <x-ui.card title="No workspaces available">
                <p class="text-sm text-med-muted">Contact your administrator to review your module access and the installation package.</p>
            </x-ui.card>
        @endforelse
    </x-ui.page>
</div>
