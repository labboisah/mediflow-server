<?php

namespace App\Services;

use App\Models\ClientLicense;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class LicenseService
{
    public function currentLicense(): ?ClientLicense
    {
        if (! Schema::hasTable('client_licenses')) {
            return null;
        }

        return ClientLicense::query()->latest('id')->first();
    }

    public function currentPlan(): ?string
    {
        $plan = $this->currentLicense()?->plan ?? config('mediflow_modules.default_plan');

        return config("mediflow_modules.legacy_plan_aliases.{$plan}", $plan);
    }

    public function planModules(string $plan): array
    {
        $modules = config("mediflow_modules.plans.{$plan}.modules", []);

        return in_array('*', $modules, true) ? $this->allKnownModules($modules) : $modules;
    }

    public function selectedPackages(): array
    {
        return $this->currentLicense()?->selected_packages ?: [$this->currentPlan()];
    }

    public function packageModules(array $packages): array
    {
        return collect($packages)->flatMap(fn ($package) => $this->planModules($package))->unique()->values()->all();
    }

    public function configuredModules(): array
    {
        $license = $this->currentLicense();
        if ($license && Schema::hasTable('client_enabled_modules')) {
            $saved = $license->enabledModules()->get();
            // An explicit all-disabled selection must never restore the package defaults.
            if ($saved->isNotEmpty()) {
                return $saved->where('is_enabled', true)->pluck('module_name')->unique()->values()->all();
            }
        }

        return $this->packageModules($this->selectedPackages());
    }

    public function enabledModules(): array
    {
        $license = $this->currentLicense();
        if ($license && (! $license->is_active
            || ($license->starts_at && $license->starts_at->isFuture())
            || ($license->expires_at && $license->expires_at->isPast()))) {
            return config('mediflow_modules.required', ['core']);
        }

        return $this->configuredModules();
    }

    public function routeEnabled(?string $name, ?string $path = null): bool
    {
        if ($path && str_starts_with(ltrim($path, '/'), 'api/v1/sync')) {
            return $this->moduleEnabled('synchronization');
        }
        foreach (config('installation_routes', []) as $pattern => $requirements) {
            if (! $name || ! \Illuminate\Support\Str::is($pattern, $name)) {
                continue;
            }
            foreach ($requirements as $requirement) {
                $allowed = false;
                foreach (explode('|', $requirement) as $module) {
                    $allowed = $allowed || $this->moduleEnabled($module);
                }
                if (! $allowed) {
                    return false;
                }
            }
        }

        return true;
    }

    public function reportTableEnabled(string $table): bool
    {
        $modules = match ($table) {
            'patient_visits', 'visit_activities' => ['patient_records'],
            'service_requests', 'vital_signs', 'observations', 'fluid_balances',
            'continuations', 'prescriptions', 'admissions', 'patient_admissions' => ['clinical_care'],
            'investigation_requests' => ['laboratory', 'radiology'],
            'antenatal_cares', 'labours', 'labour_progress', 'deliveries', 'newborns',
            'newborn_examinations', 'postnatal_examinations', 'child_follow_ups' => ['maternity'],
            default => [],
        };

        foreach ($modules as $module) {
            if ($this->moduleEnabled($module)) {
                return true;
            }
        }

        return false;
    }

    public function moduleEnabled(?string $module): bool
    {
        if ($module === null || $module === '') {
            return true;
        }

        $enabledModules = $this->enabledModules();

        return in_array($module, $enabledModules, true);
    }

    private function allKnownModules(array $extraModules = []): array
    {
        $configuredModules = collect(config('mediflow_modules.features', []))->keys();

        if (Schema::hasTable('modules')) {
            $configuredModules = $configuredModules
                ->merge(\App\Models\Module::query()
                    ->whereNotNull('license_module')
                    ->distinct()
                    ->pluck('license_module'));
        }

        return $configuredModules
            ->merge(collect($extraModules)->reject(fn (string $module) => $module === '*'))
            ->reject(fn (string $module) => $module === 'platform')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function userHasModuleAccess(User $user, ?string $module): bool
    {
        if ($module === null || $module === '') {
            return true;
        }

        if (! $this->moduleEnabled($module)) {
            return false;
        }

        if ($user->hasRole('administrator')) {
            return true;
        }

        if (! Schema::hasTable('module_user_access')) {
            return true;
        }

        return $user->moduleAccess()
            ->where('license_module', $module)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->exists();
    }

}
