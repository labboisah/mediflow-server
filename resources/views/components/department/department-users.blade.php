<div>
    <x-ui.page title="Department Users" subtitle="Manage staff accounts and operational roles for {{ auth()->user()->department?->name }}.">
        <x-slot:actions><x-ui.button type="button" wire:click="create">Add department user</x-ui.button></x-slot:actions>
        @if($feedback)<p role="status" class="rounded-md border border-med-primary bg-white p-4 text-sm text-med-primary">{{ $feedback }}</p>@endif
        @if($roles->isEmpty())
            <x-ui.card title="No assignable roles"><p class="text-sm text-med-muted">No enabled staff roles match this department. Ask your administrator to review the package and department role configuration.</p></x-ui.card>
        @endif
        @if($showEditor)
            <x-ui.card :title="$editingUserId ? 'Edit department user' : 'New department user'" subtitle="Roles are limited to this department and enabled installation modules.">
                <form wire:submit="save" class="space-y-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input label="Name" name="name" wire:model="name" required />
                        <x-ui.input type="email" label="Email" name="email" wire:model="email" required />
                        <x-ui.input type="password" :label="$editingUserId ? 'New password (optional)' : 'Password'" name="password" wire:model="password" autocomplete="new-password" />
                        <x-ui.input type="password" label="Confirm password" name="passwordConfirmation" wire:model="passwordConfirmation" autocomplete="new-password" />
                    </div>
                    <fieldset>
                        <legend class="mb-3 text-sm font-semibold text-med-ink">Staff roles</legend>
                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach($roles as $role)
                                <label class="flex items-center gap-3 rounded-md border border-med-line p-3 text-sm text-med-ink"><input type="checkbox" wire:model="selectedRoleIds" value="{{ $role->id }}">{{ $role->display_name ?: str($role->name)->headline() }}</label>
                            @endforeach
                        </div>
                        @error('selectedRoleIds')<p class="mt-2 text-sm text-med-danger">{{ $message }}</p>@enderror
                        @error('selectedRoleIds.*')<p class="mt-2 text-sm text-med-danger">{{ $message }}</p>@enderror
                    </fieldset>
                    <p class="text-xs text-med-muted">At least 8 characters are required for new passwords. Share new account credentials with the staff member through your approved process.</p>
                    <div class="flex gap-3"><x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Save user</x-ui.button><x-ui.button type="button" variant="secondary" wire:click="cancelEdit">Cancel</x-ui.button></div>
                </form>
            </x-ui.card>
        @endif
        <x-ui.card title="Department staff" subtitle="Your own account, department heads, administrators, and accounts with roles outside this department are managed by an administrator.">
            <div class="mb-5 grid gap-4 md:grid-cols-2">
                <x-ui.input type="search" label="Search" wire:model.live.debounce.400ms="search" placeholder="Name or email" />
                <x-ui.select label="Rows" wire:model.live="perPage"><option value="15">15</option><option value="25">25</option><option value="50">50</option></x-ui.select>
            </div>
            <x-ui.table>
                <thead><tr class="bg-med-canvas text-left text-sm text-med-muted"><th class="px-4 py-3">Name</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Roles</th><th class="px-4 py-3">Action</th></tr></thead>
                <tbody class="divide-y divide-med-line">
                    @forelse($users as $user)
                        <tr wire:key="department-user-{{ $user->id }}"><td class="px-4 py-3 font-semibold text-med-ink">{{ $user->name }}</td><td class="px-4 py-3 text-med-muted">{{ $user->email }}</td><td class="px-4 py-3 text-sm text-med-muted">{{ $user->roles->pluck('display_name')->implode(', ') ?: 'No role' }}</td><td class="px-4 py-3"><button type="button" wire:click="edit({{ $user->id }})" class="text-sm font-semibold text-med-primary">Edit user &amp; roles</button></td></tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-sm text-med-muted">No manageable staff accounts in this department.</td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
            <div class="mt-4">{{ $users->links() }}</div>
        </x-ui.card>
    </x-ui.page>
</div>
