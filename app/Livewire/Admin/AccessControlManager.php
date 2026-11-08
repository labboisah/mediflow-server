<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Module;
use App\Models\ModuleUserAccess;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TemporaryPermission;
use App\Models\User;
use App\Services\InstallationCatalog;
use App\Services\LicenseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]
class AccessControlManager extends Component
{
    private const PROTECTED_ROLES = ['administrator'];

    public string $roleSearch = '';

    public ?int $selectedRoleId = null;

    public string $roleName = '';

    public string $roleDisplayName = '';

    public string $roleDescription = '';

    public array $selectedPermissionIds = [];

    public array $selectedUserIds = [];

    public string $permissionName = '';

    public string $permissionDisplayName = '';

    public string $permissionModule = '';

    public string $permissionDescription = '';

    public string $permissionSearch = '';

    public string $moduleFilter = '';

    public function mount(): void
    {
        $this->selectFirstRole();
    }

    public function render()
    {
        return view('components.admin.access-control-manager', [
            'pageTitle' => 'Access Control',
            'pageSubtitle' => 'Manage role permissions, licensed module access, and user assignments.',
            'roles' => $this->roles(),
            'selectedRole' => $this->selectedRole(),
            'permissionGroups' => $this->permissionGroups(),
            'modules' => $this->modules(),
            'users' => User::with('roles')->orderBy('name')->get(['id', 'name', 'email']),
            'licenseModules' => $this->licenseModules(),
            'moduleAccessMap' => $this->moduleAccessMap(),
            'summary' => $this->summary(),
        ]);
    }

    public function createRole(): void
    {
        $this->resetRoleForm();
        $this->selectedRoleId = null;
    }

    public function selectRole(int $roleId): void
    {
        $role = app(InstallationCatalog::class)->roles()->with(['permissions', 'users'])->findOrFail($roleId);

        $this->selectedRoleId = $role->id;
        $this->roleName = $role->name;
        $this->roleDisplayName = $role->display_name ?? '';
        $this->roleDescription = $role->description ?? '';
        $this->selectedPermissionIds = app(InstallationCatalog::class)->permissions()->whereHas('roles', fn ($q) => $q->where('roles.id', $role->id))->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->selectedUserIds = $role->users->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function saveRole(): void
    {
        $validated = $this->validate([
            'roleName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($this->selectedRoleId),
            ],
            'roleDisplayName' => ['nullable', 'string', 'max:255'],
            'roleDescription' => ['nullable', 'string'],
            'selectedPermissionIds' => ['array'],
            'selectedPermissionIds.*' => ['exists:permissions,id'],
        ]);

        if ($this->isProtectedRole($this->selectedRole())) {
            $this->dispatch('toast', message: 'This system role is protected and cannot be edited here.', type: 'warning');

            return;
        }

        $catalog = app(InstallationCatalog::class);
        $catalog->roleInput($this->roleName, $validated['selectedPermissionIds']);
        if ($this->selectedRoleId) {
            $catalog->ids('roles', [$this->selectedRoleId], 'selectedRoleId');
        }
        DB::transaction(function () use ($validated, $catalog) {
            $isExisting = $this->selectedRoleId !== null;
            $role = Role::updateOrCreate(
                ['id' => $this->selectedRoleId],
                [
                    'name' => str($validated['roleName'])->lower()->replace(' ', '_')->toString(),
                    'display_name' => $validated['roleDisplayName'] ?: str($validated['roleName'])->headline()->toString(),
                    'description' => $validated['roleDescription'] ?: null,
                ]
            );

            $permissionIds = $validated['selectedPermissionIds'];
            if ($isExisting) {
                $permissionIds = array_unique(array_merge(
                    $permissionIds,
                    $catalog->retainedUnavailablePermissionIds($role),
                ));
            }
            $role->permissions()->sync($permissionIds);

            $this->selectedRoleId = $role->id;
            AuditLog::record(auth()->user(), 'role.save', $role, null, $role->fresh('permissions')->toArray());
        });

        $this->selectRole($this->selectedRoleId);
        $this->dispatch('toast', message: 'Role saved successfully.', type: 'success');
    }

    public function syncRoleUsers(): void
    {
        $role = $this->selectedRole();

        if (! $role) {
            $this->dispatch('toast', message: 'Select a role first.', type: 'warning');

            return;
        }

        if ($this->isProtectedRole($role)) {
            $this->dispatch('toast', message: 'Protected role users are managed from the user edit screen.', type: 'warning');

            return;
        }

        $this->validate([
            'selectedUserIds' => ['array'],
            'selectedUserIds.*' => ['exists:users,id'],
        ]);

        $role->users()->sync($this->selectedUserIds);
        foreach ($role->users()->get() as $user) {
            app(\App\Services\RoleModuleAccess::class)->grantMissing($user);
        }
        AuditLog::record(auth()->user(), 'role.users.sync', $role, null, ['users' => $this->selectedUserIds]);

        $this->selectRole($role->id);
        $this->dispatch('toast', message: 'Role users updated successfully.', type: 'success');
    }

    public function deleteRole(): void
    {
        $role = $this->selectedRole();

        if (! $role) {
            return;
        }

        if ($this->isProtectedRole($role)) {
            $this->dispatch('toast', message: 'Protected system roles cannot be deleted.', type: 'warning');

            return;
        }

        $before = $role->toArray();
        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        AuditLog::record(auth()->user(), 'role.delete', null, $before, null);
        $this->resetRoleForm();
        $this->selectFirstRole();
        $this->dispatch('toast', message: 'Role deleted successfully.', type: 'success');
    }

    public function savePermission(): void
    {
        $validated = $this->validate([
            'permissionName' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'permissionDisplayName' => ['nullable', 'string', 'max:255'],
            'permissionModule' => ['nullable', 'string', 'max:255'],
            'permissionDescription' => ['nullable', 'string'],
        ]);

        abort_unless(app(InstallationCatalog::class)->permissionAllowed($this->permissionModule, $this->permissionName), 403);
        $permission = Permission::create([
            'name' => str($validated['permissionName'])->lower()->replace(' ', '.')->toString(),
            'display_name' => $validated['permissionDisplayName'] ?: str($validated['permissionName'])->headline()->toString(),
            'module' => $validated['permissionModule'] ?: 'general',
            'description' => $validated['permissionDescription'] ?: null,
        ]);

        AuditLog::record(auth()->user(), 'permission.create', $permission, null, $permission->toArray());
        $this->resetPermissionForm();
        $this->dispatch('toast', message: 'Permission created successfully.', type: 'success');
    }

    public function selectModulePermissions(string $module): void
    {
        $permissionIds = $this->modulePermissionQuery($module)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->selectedPermissionIds = collect($this->selectedPermissionIds)->merge($permissionIds)->unique()->values()->all();
    }

    public function clearModulePermissions(string $module): void
    {
        $permissionIds = $this->modulePermissionQuery($module)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->selectedPermissionIds = collect($this->selectedPermissionIds)->reject(fn ($id) => in_array($id, $permissionIds, true))->values()->all();
    }

    public function toggleUserModuleAccess(int $userId, string $licenseModule): void
    {
        $user = User::with('roles')->findOrFail($userId);

        if (! $this->licenseModules()->contains($licenseModule)) {
            $this->dispatch('toast', message: 'This module is not enabled by the current license.', type: 'warning');

            return;
        }

        $access = ModuleUserAccess::firstOrNew([
            'user_id' => $user->id,
            'license_module' => $licenseModule,
        ]);

        $access->fill([
            'granted_by' => auth()->id(),
            'is_active' => ! (bool) $access->is_active,
        ]);

        if (! $access->exists) {
            $access->is_active = true;
        }

        $access->save();

        AuditLog::record(auth()->user(), 'module_access.toggle', $user, null, [
            'user_id' => $user->id,
            'license_module' => $licenseModule,
            'is_active' => $access->is_active,
        ]);

        $this->dispatch('toast', message: 'Module access updated successfully.', type: 'success');
    }

    private function roles(): Collection
    {
        return app(InstallationCatalog::class)->roles()->withCount(['users', 'permissions'])
            ->when($this->roleSearch !== '', function ($query) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$this->roleSearch}%")->orWhere('display_name', 'like', "%{$this->roleSearch}%"));
            })
            ->orderBy('name')
            ->get();
    }

    private function selectedRole(): ?Role
    {
        return $this->selectedRoleId ? app(InstallationCatalog::class)->roles()->with(['permissions', 'users'])->find($this->selectedRoleId) : null;
    }

    private function permissionGroups(): Collection
    {
        return app(InstallationCatalog::class)->permissions()
            ->when($this->permissionSearch !== '', function ($query) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$this->permissionSearch}%")
                    ->orWhere('display_name', 'like', "%{$this->permissionSearch}%")
                    ->orWhere('description', 'like', "%{$this->permissionSearch}%"));
            })
            ->when($this->moduleFilter !== '', function ($query) {
                $this->moduleFilter === 'general'
                    ? $query->where(fn ($builder) => $builder->whereNull('module')->orWhere('module', 'general'))
                    : $query->where('module', $this->moduleFilter);
            })
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($permission) => $permission->module ?: 'general');
    }

    private function modules(): Collection
    {
        return app(InstallationCatalog::class)->permissions()
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->map(fn ($module) => $module ?: 'general')
            ->unique()
            ->values();
    }

    private function summary(): array
    {
        return [
            'roles' => app(InstallationCatalog::class)->roles()->count(),
            'permissions' => app(InstallationCatalog::class)->permissions()->count(),
            'users' => User::count(),
            'temporary_permissions' => TemporaryPermission::active()->count(),
        ];
    }

    private function licenseModules(): Collection
    {
        $enabledModules = app(LicenseService::class)->enabledModules();

        return Module::query()
            ->whereNotNull('license_module')
            ->where('license_module', '!=', 'platform')
            ->when(
                ! in_array('*', $enabledModules, true),
                fn ($query) => $query->whereIn('license_module', $enabledModules)
            )
            ->distinct()
            ->orderBy('license_module')
            ->pluck('license_module')
            ->filter()
            ->values();
    }

    private function moduleAccessMap(): array
    {
        return ModuleUserAccess::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->get(['user_id', 'license_module'])
            ->mapWithKeys(fn (ModuleUserAccess $access) => [$access->user_id.':'.$access->license_module => true])
            ->all();
    }

    private function selectFirstRole(): void
    {
        $role = app(InstallationCatalog::class)->roles()->orderBy('name')->first();

        if ($role) {
            $this->selectRole($role->id);
        }
    }

    private function resetRoleForm(): void
    {
        $this->reset(['roleName', 'roleDisplayName', 'roleDescription', 'selectedPermissionIds', 'selectedUserIds']);
    }

    private function resetPermissionForm(): void
    {
        $this->reset(['permissionName', 'permissionDisplayName', 'permissionModule', 'permissionDescription']);
    }

    private function modulePermissionQuery(string $module)
    {
        return app(InstallationCatalog::class)->permissions()
            ->when(
                $module === 'general',
                fn ($query) => $query->where(fn ($builder) => $builder->whereNull('module')->orWhere('module', 'general')),
                fn ($query) => $query->where('module', $module)
            );
    }

    public function isProtectedRole(?Role $role): bool
    {
        return $role && in_array($role->name, self::PROTECTED_ROLES, true);
    }
}
