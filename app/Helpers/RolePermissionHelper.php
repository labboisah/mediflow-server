<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class RolePermissionHelper
{
    /**
     * Check if the current user has a specific role.
     */
    public static function hasRole(string|array $role): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasRole($role);
    }

    /**
     * Check if the current user has any of the given roles.
     */
    public static function hasAnyRole(array $roles): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole($roles);
    }

    /**
     * Check if the current user has all the given roles.
     */
    public static function hasAllRoles(array $roles): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasAllRoles($roles);
    }

    /**
     * Check if the current user has a specific permission.
     */
    public static function hasPermission(string|array $permission): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasPermission($permission);
    }

    /**
     * Check if the current user has any of the given permissions.
     */
    public static function hasAnyPermission(array $permissions): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyPermission($permissions);
    }

    /**
     * Check if the current user is an administrator.
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('administrator');
    }

    /**
     * Get all roles for the current user.
     */
    public static function getUserRoles(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        return $user->roles()->pluck('name')->toArray();
    }

    /**
     * Get all permissions for the current user.
     */
    public static function getUserPermissions(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        return $user->getAllPermissions()->pluck('name')->toArray();
    }
}
