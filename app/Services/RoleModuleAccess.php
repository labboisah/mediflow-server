<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleUserAccess;
use App\Models\User;

class RoleModuleAccess
{
    public function grantMissing(User $user): void
    {
        $roleIds = $user->roles()->pluck('roles.id');
        $modules = Module::query()->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $roleIds))
            ->whereNotNull('license_module')->pluck('license_module')->unique();

        foreach ($modules as $module) {
            if ($module === 'platform' || ! app(LicenseService::class)->moduleEnabled($module)) {
                continue;
            }

            // Existing revocations and time limits always take precedence.
            ModuleUserAccess::firstOrCreate(
                ['user_id' => $user->id, 'license_module' => $module],
                ['granted_by' => auth()->id(), 'is_active' => true],
            );
        }
    }
}
