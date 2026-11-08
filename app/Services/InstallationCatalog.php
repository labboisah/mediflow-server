<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class InstallationCatalog
{
    public function __construct(private LicenseService $license) {}

    public function restricted(): bool
    {
        return (bool) config('central_licensing.enabled');
    }

    private function enabled(?string $module): bool
    {
        if (! $this->restricted()) {
            return true;
        }

        if (app(ProductEntitlementService::class)->strictEnforcementEnabled()) {
            return $module !== null && app(ProductEntitlementService::class)->moduleEnabled($module);
        }

        return $module !== null && $this->license->moduleEnabled($module);
    }

    public function roleAllowed(string $name): bool
    {
        $module = match (strtolower($name)) {
            'administrator' => 'access_control', 'accountant' => 'billing', 'finance_officer' => 'finance',
            'doctor' => 'doctor','nurse' => 'nursing','midwife' => 'maternity','record' => 'patient_records',
            'pharmacist','pharmacy_technician' => 'pharmacy','lab_scientist','lab_technician' => 'laboratory',
            'radiologist','radiographer' => 'radiology','medical_director' => 'medical_director','head_of_department' => 'department_management',
            default => str_starts_with($name, 'specialist') ? 'specialist' : (str_starts_with($name, 'network_') ? 'partner_network' : 'access_control'),
        };

        return $this->enabled($module);
    }

    public function departmentAllowed(string $name): bool
    {
        $name = strtolower(trim($name));
        $module = match (true) {
            str_contains($name, 'pharma') => 'pharmacy',str_contains($name, 'laborator') => 'laboratory',str_contains($name, 'radiolog') || str_contains($name, 'imaging') => 'radiology',
            str_contains($name, 'record') => 'patient_records',str_contains($name, 'nurs') => 'nursing',
            str_contains($name, 'obstetric') || str_contains($name, 'gynecolog') || str_contains($name, 'matern') || str_contains($name, 'midwi') => 'maternity',
            str_contains($name, 'emergency') || str_contains($name, 'outpatient') || str_contains($name, 'medical services') || str_contains($name, 'pediatric') || str_contains($name, 'family medicine') => 'clinical_care',
            default => 'department_management',
        };

        return $this->enabled($module);
    }

    public function categoryAllowed(?string $category): bool
    {
        $module = match (strtolower(trim($category ?? ''))) {
            'consultation','consultations','pediatric','family','vaccination' => 'clinical_care',
            'laboratory' => 'laboratory','imaging','radiology' => 'radiology','medication','pharmacy' => 'pharmacy',
            'file' => 'patient_records','admission' => 'wards_beds','labour','anc','maternity' => 'maternity','general' => 'billing',default => null,
        };

        return $this->enabled($module);
    }

    public function permissionAllowed(?string $module, string $name = ''): bool
    {
        $entitlements = app(ProductEntitlementService::class);
        if ($entitlements->strictEnforcementEnabled()) {
            $mappedModule = $entitlements->permissionModuleForAttributes($module, $name);

            return $mappedModule !== null && $entitlements->moduleEnabled($mappedModule);
        }

        if (! $module && $name !== '') {
            $prefix = explode('.', $name)[0];
            $module = ['patient' => 'patient_records', 'investigation' => 'laboratory', 'lab' => 'laboratory', 'radiology' => 'radiology', 'pharmacy' => 'pharmacy', 'ward' => 'wards_beds', 'bed' => 'wards_beds', 'department' => 'department_management', 'service' => 'billing', 'bill' => 'billing', 'payment' => 'billing', 'report' => 'reports', 'sync' => 'synchronization', 'specialist' => 'specialist', 'network' => 'partner_network'][$prefix] ?? 'access_control';
        }
        $module = strtolower(str_replace([' ', '-'], '_', trim($module ?? '')));
        $module = match ($module) {
            'administration','general','' => 'access_control','patient' => 'patient_records','clinical' => 'clinical_care',
            'midwifery' => 'maternity','inventory' => 'department_management',default => $module,
        };

        return $this->enabled($module);
    }

    public function permissions(): Builder
    {
        $query = Permission::query();
        if (! $this->restricted()) {
            return $query;
        }
        $ids = Permission::query()->get(['id', 'module', 'name', 'module_id'])->filter(function ($p) {
            if (app(ProductEntitlementService::class)->strictEnforcementEnabled()) {
                return app(ProductEntitlementService::class)->permissionAllowed($p);
            }
            if (! $this->permissionAllowed($p->getRawOriginal('module'), $p->name)) {
                return false;
            }
            if ($p->module_id) {
                $module = Module::find($p->module_id);

                return $module && (! $module->license_module || $this->enabled($module->license_module));
            }

            return true;
        })->pluck('id');

        return $query->whereIn('id', $ids);
    }

    public function roles(): Builder
    {
        $query = Role::query();
        if (! $this->restricted()) {
            return $query;
        }

        return $query->whereIn('id', Role::query()->get(['id', 'name'])->filter(fn ($r) => $this->roleAllowed($r->name))->pluck('id'));
    }

    public function departments(): Builder
    {
        $query = Department::query();
        if (! $this->restricted()) {
            return $query;
        }

        return $query->whereIn('id', Department::query()->get(['id', 'name'])->filter(fn ($d) => $this->departmentAllowed($d->name))->pluck('id'));
    }

    public function services(): Builder
    {
        $query = Service::query();
        if (! $this->restricted()) {
            return $query;
        }
        $categories = Service::withTrashed()->distinct()->pluck('category')->filter(fn ($c) => $this->categoryAllowed($c));

        return $query->whereIn('category', $categories)->where(fn ($q) => $q->whereNull('department_id')->orWhereIn('department_id', $this->departments()->select('id')));
    }

    public function ids(string $kind, array $ids, string $field): void
    {
        if (! $this->restricted()) {
            return;
        }
        $ids = array_unique($ids);
        if ($this->$kind()->whereIn('id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages([$field => 'Select only records included in the activated package.']);
        }
    }

    public function roleInput(string $name, array $permissions): void
    {
        if (! $this->roleAllowed(strtolower(str_replace(' ', '_', $name)))) {
            throw ValidationException::withMessages(['name' => 'This role requires a feature outside the activated package.']);
        }
        $this->ids('permissions', $permissions, 'permissions');
    }

    public function retainedUnavailablePermissionIds(Role $role): array
    {
        if (! app(ProductEntitlementService::class)->strictEnforcementEnabled()) {
            return [];
        }

        return $role->permissions()
            ->whereNotIn('permissions.id', $this->permissions()->select('permissions.id'))
            ->pluck('permissions.id')
            ->all();
    }

    public function serviceInput(array $data): void
    {
        if (! $this->categoryAllowed($data['category'] ?? null)) {
            throw ValidationException::withMessages(['category' => 'This service category is not included in the activated package.']);
        }
        if (! empty($data['department_id'])) {
            $this->ids('departments', [$data['department_id']], 'department_id');
        }
    }
}
