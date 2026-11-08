<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\InstallationCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    private const PROTECTED_ROLES = ['administrator'];

    public function index()
    {
        $roles = app(InstallationCatalog::class)->roles()->with(['permissions' => fn ($q) => $q->whereIn('permissions.id', app(InstallationCatalog::class)->permissions()->select('id'))])->paginate(10);

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = app(InstallationCatalog::class)->permissions()->get();

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:roles|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissionIds = (array) ($validated['permissions'] ?? []);
        app(InstallationCatalog::class)->roleInput($validated['name'], $permissionIds);
        $role = DB::transaction(function () use ($request, $validated, $permissionIds) {
            $role = Role::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            if ($request->has('permissions')) {
                $role->permissions()->sync($permissionIds);
            }

            return $role;
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        abort_unless(app(InstallationCatalog::class)->roles()->whereKey($role->id)->exists(), 403);
        $permissions = app(InstallationCatalog::class)->permissions()->get();
        $rolePermissions = app(InstallationCatalog::class)->permissions()->whereHas('roles', fn ($q) => $q->where('roles.id', $role->id))->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        abort_unless(app(InstallationCatalog::class)->roles()->whereKey($role->id)->exists(), 403);
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return redirect()->route('admin.roles.index')->with('error', 'Cannot edit protected system roles.');
        }

        $validated = $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id.'|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $catalog = app(InstallationCatalog::class);
        $permissionIds = (array) ($validated['permissions'] ?? []);
        $catalog->roleInput($validated['name'], $permissionIds);
        DB::transaction(function () use ($request, $role, $validated, $catalog, $permissionIds): void {
            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            if ($request->has('permissions')) {
                $permissionIds = array_unique(array_merge(
                    $permissionIds,
                    $catalog->retainedUnavailablePermissionIds($role),
                ));
                $role->permissions()->sync($permissionIds);
            }
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        abort_unless(app(InstallationCatalog::class)->roles()->whereKey($role->id)->exists(), 403);
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return redirect()->route('admin.roles.index')->with('error', 'Cannot delete protected system roles.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
