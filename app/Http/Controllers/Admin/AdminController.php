<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\TemporaryPermission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function index()
    {
        $rolesCount = Role::count();
        $permissionsCount = Permission::count();
        $usersCount = User::count();
        $adminUsersCount = User::whereHas('roles', function ($query) {
            $query->where('name', 'administrator');
        })->count();
        $activeTempPermissionsCount = TemporaryPermission::active()->count();

        return view('admin.index', compact('rolesCount', 'permissionsCount', 'usersCount', 'adminUsersCount', 'activeTempPermissionsCount'));
    }
}
