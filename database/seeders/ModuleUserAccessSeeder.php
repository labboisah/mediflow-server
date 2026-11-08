<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleUserAccess;
use App\Models\User;
use Illuminate\Database\Seeder;

class ModuleUserAccessSeeder extends Seeder
{
    public function run(): void
    {
        $modulesByRole = Module::with('roles')->get()
            ->flatMap(function (Module $module) {
                return $module->roles->map(fn ($role) => [
                    'role' => $role->name,
                    'license_module' => $module->license_module,
                ]);
            })
            ->filter(fn (array $item) => filled($item['license_module']) && $item['license_module'] !== 'platform')
            ->groupBy('role')
            ->map(fn ($items) => $items->pluck('license_module')->unique()->values());

        $allModules = Module::query()
            ->whereNotNull('license_module')
            ->where('license_module', '!=', 'platform')
            ->pluck('license_module')
            ->unique()
            ->values();

        User::with('roles')->get()->each(function (User $user) use ($modulesByRole, $allModules) {
            $modules = collect();

            if ($user->hasRole('administrator')) {
                $modules = $allModules;
            } else {
                foreach ($user->roles as $role) {
                    $modules = $modules->merge($modulesByRole->get($role->name, collect()));
                }
            }

            $modules->filter()->unique()->each(function (string $licenseModule) use ($user) {
                ModuleUserAccess::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'license_module' => $licenseModule,
                    ],
                    [
                        'granted_by' => null,
                        'starts_at' => null,
                        'expires_at' => null,
                        'is_active' => true,
                    ]
                );
            });
        });
    }
}
