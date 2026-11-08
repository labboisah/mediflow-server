<div>
    @php
        $selectedRoleIsProtected = $selectedRole && in_array($selectedRole->name, ['administrator'], true);
    @endphp

    <x-ui.page :title="$pageTitle ?? 'Workspace'" :subtitle="$pageSubtitle ?? config('app.name')">
        <x-slot:actions>
            <x-ui.button wire:click="createRole">
                <i class="bi bi-plus-circle"></i>
                New Role
            </x-ui.button>
        </x-slot:actions>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.card>
                <p class="text-base font-medium text-med-muted">Roles</p>
                <p class="mt-3 text-3xl font-semibold leading-tight text-med-ink">{{ number_format($summary['roles']) }}</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-base font-medium text-med-muted">Permissions</p>
                <p class="mt-3 text-3xl font-semibold leading-tight text-med-ink">{{ number_format($summary['permissions']) }}</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-base font-medium text-med-muted">Users</p>
                <p class="mt-3 text-3xl font-semibold leading-tight text-med-ink">{{ number_format($summary['users']) }}</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-base font-medium text-med-muted">Temp Permissions</p>
                <p class="mt-3 text-3xl font-semibold leading-tight text-med-ink">{{ number_format($summary['temporary_permissions']) }}</p>
            </x-ui.card>
        </div>

        <x-ui.card title="Module Access" subtitle="Attach users to licensed modules before role permissions are applied.">
            @if($licenseModules->isEmpty())
                <x-ui.empty-state title="No licensed modules are available" />
            @else
                <x-ui.table>
                    <thead class="bg-med-canvas">
                        <tr>
                            <th class="min-w-56 px-4 py-3 text-left text-sm font-semibold text-med-ink">User</th>
                            @foreach($licenseModules as $licenseModule)
                                <th class="whitespace-nowrap px-4 py-3 text-center text-sm font-semibold text-med-ink">
                                    {{ ucfirst(str_replace('_', ' ', $licenseModule)) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-med-line">
                        @foreach($users as $user)
                            <tr class="hover:bg-med-canvas">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-med-ink">{{ $user->name }}</p>
                                    <p class="mt-1 text-sm text-med-muted">{{ $user->email }}</p>
                                </td>
                                @foreach($licenseModules as $licenseModule)
                                    @php($hasAccess = $moduleAccessMap[$user->id . ':' . $licenseModule] ?? false)
                                    <td class="px-4 py-4 text-center">
                                        <button type="button"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-md border text-sm transition {{ $hasAccess ? 'border-med-primary bg-green-50 text-med-primary' : 'border-med-line bg-white text-med-muted hover:bg-med-canvas' }}"
                                                wire:click="toggleUserModuleAccess({{ $user->id }}, '{{ $licenseModule }}')"
                                                title="{{ $hasAccess ? 'Module enabled for user' : 'Module disabled for user' }}">
                                            <i class="bi {{ $hasAccess ? 'bi-check2' : 'bi-dash' }}"></i>
                                        </button>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @endif
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-3" title="Roles">
                <x-ui.input type="search"
                            placeholder="Search roles"
                            wire:model.live.debounce.300ms="roleSearch" />

                <div class="mt-4 max-h-[620px] space-y-2 overflow-y-auto pr-1">
                    @forelse($roles as $role)
                        <button type="button"
                                wire:click="selectRole({{ $role->id }})"
                                class="w-full rounded-md border px-4 py-3 text-left transition {{ $selectedRole?->id === $role->id ? 'border-med-primary bg-green-50 text-med-primary shadow-sm' : 'border-med-line bg-white text-med-ink hover:bg-med-canvas' }}">
                            <span class="flex items-start justify-between gap-3">
                                <span class="min-w-0">
                                    <span class="block font-semibold">{{ $role->display_name ?: ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                                    <span class="mt-1 block truncate text-sm text-med-muted">{{ $role->name }}</span>
                                </span>
                                <x-ui.badge>{{ $role->permissions_count }}</x-ui.badge>
                            </span>
                        </button>
                    @empty
                        <x-ui.empty-state title="No roles found" />
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card class="xl:col-span-6" title="Role Builder">
                <x-slot:actions>
                    @if($selectedRoleIsProtected)
                        <x-ui.badge variant="warning">Protected</x-ui.badge>
                    @endif
                </x-slot:actions>

                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Role Name"
                                placeholder="billing_supervisor"
                                wire:model.defer="roleName"
                                :disabled="$selectedRoleIsProtected" />

                    <x-ui.input label="Display Name"
                                placeholder="Billing Supervisor"
                                wire:model.defer="roleDisplayName"
                                :disabled="$selectedRoleIsProtected" />

                    <div class="md:col-span-2">
                        <x-ui.textarea label="Description"
                                       rows="3"
                                       wire:model.defer="roleDescription"
                                       :disabled="$selectedRoleIsProtected" />
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 border-t border-med-line pt-5 lg:flex-row lg:items-center lg:justify-between">
                    <h3 class="text-lg font-semibold text-med-ink">Permissions</h3>
                    <div class="grid gap-3 sm:grid-cols-2 lg:w-[26rem]">
                        <x-ui.input type="search"
                                    placeholder="Search permissions"
                                    wire:model.live.debounce.300ms="permissionSearch" />
                        <x-ui.select wire:model.live="moduleFilter">
                            <option value="">All Modules</option>
                            @foreach($modules as $module)
                                <option value="{{ $module }}">{{ ucfirst(str_replace('_', ' ', $module)) }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                </div>

                <div class="mt-4 max-h-[520px] space-y-4 overflow-y-auto pr-1">
                    @forelse($permissionGroups as $module => $permissions)
                        <section class="rounded-md border border-med-line bg-white">
                            <div class="flex flex-col gap-3 border-b border-med-line bg-med-canvas px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <strong class="text-base text-med-ink">{{ ucfirst(str_replace(['_', '-'], ' ', $module)) }}</strong>
                                @if(! $selectedRoleIsProtected)
                                    <div class="flex gap-2">
                                        <x-ui.button variant="secondary" wire:click="selectModulePermissions('{{ $module }}')">Select</x-ui.button>
                                        <x-ui.button variant="ghost" wire:click="clearModulePermissions('{{ $module }}')">Clear</x-ui.button>
                                    </div>
                                @endif
                            </div>

                            <div class="grid gap-3 p-4 md:grid-cols-2">
                                @foreach($permissions as $permission)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-md border border-med-line bg-white p-3 transition hover:bg-med-canvas">
                                        <input class="mt-1 h-4 w-4 rounded border-med-line text-med-primary"
                                               type="checkbox"
                                               value="{{ $permission->id }}"
                                               wire:model.live="selectedPermissionIds"
                                               @disabled($selectedRoleIsProtected)>
                                        <span>
                                            <span class="block font-semibold text-med-ink">{{ $permission->display_name ?: $permission->name }}</span>
                                            <span class="mt-1 block text-sm text-med-muted">{{ $permission->name }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <x-ui.empty-state title="No permissions found" />
                    @endforelse
                </div>

                <div class="mt-5 flex flex-wrap gap-3 border-t border-med-line pt-5">
                    <x-ui.button wire:click="saveRole" :disabled="$selectedRoleIsProtected">
                        <i class="bi bi-check-circle"></i>
                        Save Role
                    </x-ui.button>

                    @if($selectedRole && ! $selectedRoleIsProtected)
                        <x-ui.button variant="danger" wire:click="deleteRole" wire:confirm="Delete this role?">
                            <i class="bi bi-trash"></i>
                            Delete Role
                        </x-ui.button>
                    @endif
                </div>
            </x-ui.card>

            <div class="space-y-6 xl:col-span-3">
                <x-ui.card title="Create Permission">
                    <div class="space-y-4">
                        <x-ui.input label="Permission Name"
                                    placeholder="billing.approve"
                                    wire:model.defer="permissionName" />

                        <x-ui.input label="Display Name"
                                    placeholder="Approve Billing"
                                    wire:model.defer="permissionDisplayName" />

                        <x-ui.input label="Module"
                                    placeholder="billing"
                                    wire:model.defer="permissionModule" />

                        <x-ui.textarea label="Description"
                                       rows="3"
                                       wire:model.defer="permissionDescription" />

                        <x-ui.button variant="secondary" class="w-full" wire:click="savePermission">
                            <i class="bi bi-plus-circle"></i>
                            Add Permission
                        </x-ui.button>
                    </div>
                </x-ui.card>

                <x-ui.card title="Users In Role">
                    @if($selectedRole)
                        <div class="max-h-[360px] space-y-3 overflow-y-auto pr-1">
                            @foreach($users as $user)
                                <label class="flex cursor-pointer items-start gap-3 rounded-md border border-med-line bg-white p-3 transition hover:bg-med-canvas">
                                    <input class="mt-1 h-4 w-4 rounded border-med-line text-med-primary"
                                           type="checkbox"
                                           value="{{ $user->id }}"
                                           wire:model.live="selectedUserIds"
                                           @disabled($selectedRoleIsProtected)>
                                    <span class="min-w-0">
                                        <span class="block font-semibold text-med-ink">{{ $user->name }}</span>
                                        <span class="mt-1 block truncate text-sm text-med-muted">{{ $user->email }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <x-ui.button variant="secondary"
                                     class="mt-4 w-full"
                                     wire:click="syncRoleUsers"
                                     :disabled="$selectedRoleIsProtected">
                            <i class="bi bi-people"></i>
                            Update Users
                        </x-ui.button>
                    @else
                        <x-ui.empty-state title="Select a role first" message="Save or select a role before assigning users." />
                    @endif
                </x-ui.card>
            </div>
        </div>
    </x-ui.page>
</div>

