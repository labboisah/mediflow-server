<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\InstallationCatalog;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = app(InstallationCatalog::class)->permissions()->with('roles')->paginate(15);

        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:permissions|max:255',
            'description' => 'nullable|string',
        ]);

        abort_unless(app(InstallationCatalog::class)->permissionAllowed(null, $validated['name']), 403);
        Permission::create($validated);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission)
    {
        abort_unless(app(InstallationCatalog::class)->permissions()->whereKey($permission->id)->exists(), 403);
        $roles = app(InstallationCatalog::class)->roles()->get();
        $permissionRoles = $permission->roles->pluck('id')->toArray();

        return view('admin.permissions.edit', compact('permission', 'roles', 'permissionRoles'));
    }

    public function update(Request $request, Permission $permission)
    {
        abort_unless(app(InstallationCatalog::class)->permissions()->whereKey($permission->id)->exists(), 403);
        $validated = $request->validate([
            'name' => 'required|unique:permissions,name,'.$permission->id.'|max:255',
            'description' => 'nullable|string',
        ]);

        abort_unless(app(InstallationCatalog::class)->permissionAllowed($permission->getRawOriginal('module'), $validated['name']), 403);
        $permission->update($validated);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        abort_unless(app(InstallationCatalog::class)->permissions()->whereKey($permission->id)->exists(), 403);
        $permission->delete();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted successfully.');
    }
}
