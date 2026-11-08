<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\UserCreated;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\InstallationCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $query = User::with(['roles', 'department']);

        // search and filters
        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = request('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('id', $role);
            });
        }

        if (request()->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $users = $query->paginate(15)->withQueryString();
        $roles = app(InstallationCatalog::class)->roles()->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = app(InstallationCatalog::class)->roles()->orderBy('name')->get();
        $departments = app(InstallationCatalog::class)->departments()->orderBy('name')->get();

        return view('admin.users.create', compact('roles', 'departments'));
    }

    public function edit(User $user)
    {
        abort_if($user->is_installation_admin && ! auth()->user()?->is_installation_admin, 403);
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot edit your own roles here.');
        }

        $roles = app(InstallationCatalog::class)->roles()->orderBy('name')->get();
        $departments = app(InstallationCatalog::class)->departments()->orderBy('name')->get();
        $userRoles = $user->roles->pluck('id')->toArray();

        return view('admin.users.edit', compact('user', 'roles', 'departments', 'userRoles'));
    }

    public function show(User $user)
    {
        $user->load(['roles', 'department']);

        $roles = app(InstallationCatalog::class)->roles()->orderBy('name')->get();
        $userRoles = $user->roles->pluck('id')->toArray();

        return view('admin.users.show', compact('user', 'roles', 'userRoles'));
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->is_installation_admin && ! auth()->user()?->is_installation_admin, 403);
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot edit your own roles.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        app(InstallationCatalog::class)->ids('roles', $validated['roles'] ?? [], 'roles');
        app(InstallationCatalog::class)->ids('departments', [$validated['department_id']], 'department_id');
        $before = $user->toArray();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department_id' => $validated['department_id'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
            $user->save();
        }

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
            app(\App\Services\RoleModuleAccess::class)->grantMissing($user);
        } else {
            $user->removeAllRoles();
        }

        $after = $user->fresh()->toArray();

        AuditLog::record(auth()->user(), 'user.update', $user, $before, $after);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'department_id' => 'required|exists:departments,id',
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        app(InstallationCatalog::class)->ids('roles', $validated['roles'] ?? [], 'roles');
        app(InstallationCatalog::class)->ids('departments', [$validated['department_id']], 'department_id');
        $password = $validated['password'] ?? Str::random(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department_id' => $validated['department_id'],
            'password' => $password,
        ]);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
            app(\App\Services\RoleModuleAccess::class)->grantMissing($user);
        }

        // Send welcome email with temporary password
        try {
            Mail::to($user->email)->send(new UserCreated($user, $password));
        } catch (\Throwable $e) {
            // Log but don't fail the request
            logger()->error('Failed to send user created email: '.$e->getMessage());
        }

        AuditLog::record(auth()->user(), 'user.create', $user, null, $user->toArray());

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function destroy(User $user)
    {
        abort_if($user->is_installation_admin && ! auth()->user()?->is_installation_admin, 403);
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        $before = $user->toArray();
        $user->delete();

        AuditLog::record(auth()->user(), 'user.delete', null, $before, null);

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if (! $user->trashed()) {
            return redirect()->route('admin.users.index')->with('error', 'User is not deleted.');
        }

        $before = $user->toArray();
        $user->restore();
        $after = $user->fresh()->toArray();
        AuditLog::record(auth()->user(), 'user.restore', $user, $before, $after);

        return redirect()->route('admin.users.index')->with('success', 'User restored successfully.');
    }
}
