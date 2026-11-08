<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TemporaryPermission;
use App\Models\User;
use App\Services\InstallationCatalog;
use Illuminate\Http\Request;

class TemporaryPermissionController extends Controller
{
    public function index()
    {
        $tempPermissions = TemporaryPermission::with(['user', 'permission', 'grantedBy'])
            ->latest()
            ->paginate(15);

        return view('admin.temporary-permissions.index', compact('tempPermissions'));
    }

    public function create()
    {
        $users = User::where('id', '!=', auth()->id())->orderBy('name')->get();
        $permissions = app(InstallationCatalog::class)->permissions()->orderBy('module')->orderBy('name')->get();

        return view('admin.temporary-permissions.create', compact('users', 'permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|not_in:'.auth()->id(),
            'permission_id' => 'required|exists:permissions,id',
            'duration_hours' => 'required|integer|min:1|max:168', // Max 7 days
            'reason' => 'nullable|string|max:500',
        ]);

        app(InstallationCatalog::class)->ids('permissions', [$validated['permission_id']], 'permission_id');
        // Check if already exists
        $existing = TemporaryPermission::where('user_id', $validated['user_id'])
            ->where('permission_id', $validated['permission_id'])
            ->active()
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'This user already has this temporary permission active.');
        }

        $expiresAt = now()->addHours($validated['duration_hours']);

        $temp = TemporaryPermission::create([
            'user_id' => $validated['user_id'],
            'permission_id' => $validated['permission_id'],
            'granted_by' => auth()->id(),
            'reason' => $validated['reason'],
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        AuditLog::record(auth()->user(), 'temporary_permission.grant', $temp, null, $temp->toArray(), ['reason' => $validated['reason'] ?? null]);

        return redirect()->route('admin.temporary-permissions.index')
            ->with('success', 'Temporary permission granted successfully.');
    }

    public function revoke(TemporaryPermission $temporaryPermission)
    {
        $before = $temporaryPermission->toArray();
        $temporaryPermission->revoke();
        $after = $temporaryPermission->refresh()->toArray();

        AuditLog::record(auth()->user(), 'temporary_permission.revoke', $temporaryPermission, $before, $after);

        return redirect()->route('admin.temporary-permissions.index')
            ->with('success', 'Temporary permission revoked.');
    }

    public function destroy(TemporaryPermission $temporaryPermission)
    {
        $before = $temporaryPermission->toArray();
        $temporaryPermission->delete();

        AuditLog::record(auth()->user(), 'temporary_permission.delete', null, $before, null);

        return redirect()->route('admin.temporary-permissions.index')
            ->with('success', 'Temporary permission record deleted.');
    }
}
