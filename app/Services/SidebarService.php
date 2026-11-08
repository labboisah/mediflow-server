<?php

namespace App\Services;

use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class SidebarService
{
    public function __construct(private readonly LicenseService $license)
    {
    }

    public function groupsFor(?User $user): array
    {
        if (! $user || ! Schema::hasTable('modules')) {
            return [];
        }

        $groupConfig = collect(config('sidebar.groups', []))->sortBy('sort');
        $items = Module::query()
            ->with(['permissions', 'roles'])
            ->where('is_active', true)
            ->where('is_sidebar_visible', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Module $module) => $this->canShowItem($user, $module))
            ->unique('route')
            ->groupBy(fn (Module $module) => $module->sidebar_group ?: $module->group ?: 'administration')
            ->map(fn (Collection $groupItems) => $groupItems
                ->unique(fn (Module $module) => strtolower(trim((string) ($module->label ?: $module->route))))
                ->values());

        return $groupConfig
            ->map(function (array $group, string $groupKey) use ($items) {
                return [
                    'key' => $groupKey,
                    'id' => 'sidebar-group-' . str_replace('_', '-', $groupKey),
                    'label' => $group['label'],
                    'icon' => $group['icon'],
                    'items' => $items->get($groupKey, collect())->values(),
                ];
            })
            ->filter(fn (array $group) => $group['items']->isNotEmpty())
            ->values()
            ->all();
    }

    public function canShowActivities(User $user): bool
    {
        return $this->license->userHasModuleAccess($user, 'reports')
            && ($user->hasRole('administrator') || $user->hasPermission('activity.read'));
    }

    public function canShowItem(User $user, Module $module): bool
    {
        $finance = app(PackageFinanceAccess::class);
        if ($finance->matches($module->route)) {
            return $finance->allows($user) && str_starts_with($module->route, 'admin.') && $this->routeCanBeGenerated($module->route);
        }

        if ($module->name === 'pharmacy_services') {
            return \App\Models\PharmacyService::canManage($user)
                && $this->license->moduleEnabled('pharmacy') && $this->routeCanBeGenerated($module->route);
        }

        if (in_array($module->name, ['pharmacy_medicines', 'pharmacy_stock', 'pharmacy_stock_reconciliation', 'pharmacy_batches', 'pharmacy_expiry_alerts', 'pharmacy_finance_report'], true)) {
            $isHead = $user->hasRole('head_of_department')
                && str_contains(strtolower((string) $user->department?->name), 'pharmacy');
            if (! $user->hasRole('pharmacist') && ! $isHead) {
                return false;
            }
            if ($module->name === 'pharmacy_stock_reconciliation' && ! $isHead) {
                return false;
            }
        }

        if ($module->name === 'department_users') {
            return $user->department_id && $user->hasRole('head_of_department')
                && $this->license->moduleEnabled('access_control') && $this->routeCanBeGenerated($module->route);
        }

        if (! $this->license->routeEnabled($module->route) || ! $this->license->userHasModuleAccess($user, $module->license_module)) {
            return false;
        }

        if (! $this->routeCanBeGenerated($module->route)) {
            return false;
        }

        if (! $this->passesDepartmentRules($user, $module)) {
            return false;
        }

        $permissionNames = $module->permissions->pluck('name')->filter()->all();

        if ($permissionNames !== [] && $user->hasAnyPermission($permissionNames)) {
            return true;
        }

        $roleNames = $module->roles->pluck('name')->filter()->all();

        return $roleNames !== [] && $user->hasAnyRole($roleNames);
    }

    public function patternsFor(Module $module): array
    {
        $patterns = $module->sidebar_patterns ?: [$module->route];

        return collect($patterns)->filter()->values()->all();
    }

    private function passesDepartmentRules(User $user, Module $module): bool
    {
        $rules = $this->departmentRules()[$module->name] ?? null;

        if (! $rules) {
            return true;
        }

        $departmentName = strtolower((string) $user->department?->name);

        if (! empty($rules['only'])) {
            $matches = collect($rules['only'])
                ->contains(fn (string $keyword) => str_contains($departmentName, strtolower($keyword)));

            if (! $matches) {
                return false;
            }
        }

        if (! empty($rules['except'])) {
            $matches = collect($rules['except'])
                ->contains(fn (string $keyword) => str_contains($departmentName, strtolower($keyword)));

            if ($matches) {
                return false;
            }
        }

        return true;
    }

    private function routeCanBeGenerated(?string $route): bool
    {
        if (! $route || ! Route::has($route)) {
            return false;
        }

        return Route::getRoutes()->getByName($route)?->parameterNames() === [];
    }

    private function departmentRules(): array
    {
        return [
            'department_investigations' => ['only' => ['lab', 'radio']],
            'department_consumables' => ['except' => ['pharmacy']],
            'department_stocks' => ['except' => ['pharmacy']],
            'department_stock_usage' => ['except' => ['pharmacy']],
            'pharmacy_finance_report' => ['only' => ['pharmacy']],
            'pharmacy_medicines' => ['only' => ['pharmacy']],
            'pharmacy_stock' => ['only' => ['pharmacy']],
            'pharmacy_stock_reconciliation' => ['only' => ['pharmacy']],
            'pharmacy_batches' => ['only' => ['pharmacy']],
            'pharmacy_expiry_alerts' => ['only' => ['pharmacy']],
        ];
    }
}
