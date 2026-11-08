<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use KernelBridge\LicensingClient\Models\LicenseState;
use KernelBridge\LicensingClient\Services\LicenseCacheService;

final class ProductEntitlementService
{
    public function __construct(private readonly LicenseCacheService $cache) {}

    /**
     * @return array{allowed: bool, status: string, module: string, grant_key: string|null, source: string|null, entitlement_revision: int|null, catalog_revision: int|null, source_revision: int|null, snapshot_age_seconds: int|null}
     */
    public function moduleDecision(string $module): array
    {
        $grantKey = config("entitlement_framework.module_capabilities.{$module}");
        if (! is_string($grantKey)) {
            return $this->decision($module, null, [
                'status' => 'absent',
                'source' => null,
            ], null);
        }

        if ($this->strictEnforcementEnabled()) {
            $decision = $this->cache->entitlementDecision($grantKey);
            if (($decision['source'] ?? null) !== 'framework'
                && ! in_array($decision['status'] ?? null, ['license_unavailable', 'malformed', 'unsupported'], true)) {
                $decision = [
                    'status' => 'framework_required',
                    'source' => $decision['source'] ?? null,
                ];
            }
            if (($decision['source'] ?? null) === 'framework'
                && ($decision['status'] ?? null) === 'granted'
                && (($decision['grant']['type'] ?? null) !== 'capability'
                    || ($decision['grant']['enabled'] ?? null) !== true)) {
                $decision = ['status' => 'invalid_grant', 'source' => 'framework'];
            }

            return $this->decision($module, $grantKey, $decision, $this->cache->state());
        }

        if (! $this->cache->hasUsableLicense()) {
            $decision = $this->cache->entitlementDecision($grantKey);

            return $this->decision($module, $grantKey, $decision, $this->cache->state());
        }

        $state = $this->cache->state();
        $framework = $this->signedFramework($state);
        $decisionKey = $framework === null ? $module : $grantKey;
        $decision = $this->cache->entitlementDecision($decisionKey);

        return $this->decision($module, $decisionKey, $decision, $state);
    }

    public function moduleEnabled(string $module): bool
    {
        return $this->moduleDecision($module)['allowed'];
    }

    public function strictEnforcementEnabled(): bool
    {
        return (bool) config('central_licensing.enabled')
            && (bool) config('entitlement_framework.strict_enforcement', false);
    }

    /**
     * Resolve a permission only through its persisted module and stable permission namespace.
     */
    public function permissionModuleForAttributes(?string $module, string $name): ?string
    {
        $category = strtolower(trim($module ?? ''));
        $prefix = strtolower(explode('.', $name, 2)[0]);
        $mappings = (array) config('entitlement_framework.permission_module_mappings', []);
        $mappedModule = $mappings[$category][$prefix] ?? null;
        $capabilities = (array) config('entitlement_framework.module_capabilities', []);

        return is_string($mappedModule)
            && isset($capabilities[$mappedModule])
                ? $mappedModule
                : null;
    }

    public function permissionModule(Permission $permission): ?string
    {
        $mappedModule = $this->permissionModuleForAttributes(
            $permission->getRawOriginal('module'),
            $permission->name,
        );

        if ($permission->module_id === null) {
            return $mappedModule;
        }

        $module = $permission->relationLoaded('module')
            ? $permission->module
            : Module::query()->find($permission->module_id);
        $licenseModule = $module?->license_module;
        $capabilities = (array) config('entitlement_framework.module_capabilities', []);
        if (! is_string($licenseModule)
            || ! isset($capabilities[$licenseModule])
            || ($mappedModule !== null && $mappedModule !== $licenseModule)) {
            return null;
        }

        return $licenseModule;
    }

    /**
     * @return array{allowed: bool, status: string, module: string|null, grant_key: string|null, source: string|null}
     */
    public function permissionDecision(Permission $permission): array
    {
        $module = $this->permissionModule($permission);
        if ($module === null) {
            Log::debug('mediflow.entitlement.permission_denied', [
                'permission' => $permission->name,
                'reason' => 'unmapped_permission',
            ]);

            return [
                'allowed' => false,
                'status' => 'unmapped_permission',
                'module' => null,
                'grant_key' => null,
                'source' => null,
            ];
        }

        $decision = $this->moduleDecision($module);

        return [
            'allowed' => $decision['allowed'],
            'status' => $decision['status'],
            'module' => $module,
            'grant_key' => $decision['grant_key'],
            'source' => $decision['source'],
        ];
    }

    public function permissionAllowed(Permission $permission): bool
    {
        return $this->permissionDecision($permission)['allowed'];
    }

    /**
     * Return a typed limit decision. A missing grant is not treated as unlimited.
     *
     * @return array<string, mixed>
     */
    public function limit(string $grantKey): array
    {
        return $this->cache->limitGrant($grantKey);
    }

    /**
     * Export the product-owned grant contract in the shared catalog wire shape.
     *
     * @return array{schema_version: int, product_code: string, catalog_revision: int, packages: list<array{key: string, modules: list<array{key: string, capabilities: list<array{key: string, type: string, description: string}>}>}>, plans: list<array{key: string, grants: array<string, array{type: string, enabled: bool}>}>}
     */
    public function catalog(): array
    {
        $definitions = (array) config('entitlement_framework.module_capabilities', []);
        $labels = (array) config('mediflow_modules.features', []);
        $descriptions = (array) config('entitlement_framework.module_descriptions', []);
        $groups = (array) config('entitlement_framework.module_groups', []);
        $groupedModules = collect($groups)->flatten()->all();
        $configuredModules = array_keys($definitions);
        sort($groupedModules);
        sort($configuredModules);
        if ($groupedModules !== $configuredModules) {
            throw new \LogicException('MediFlow entitlement module groups must contain every catalog module exactly once.');
        }

        $packages = [];
        foreach ($groups as $packageKey => $modules) {
            $packages[] = [
                'key' => $packageKey,
                'modules' => array_map(function (string $module) use ($definitions, $labels, $descriptions): array {
                    if (! isset($definitions[$module], $labels[$module], $descriptions[$module])) {
                        throw new \LogicException("MediFlow entitlement catalog is missing the '$module' module definition.");
                    }

                    return [
                        'key' => $module,
                        'capabilities' => [[
                            'key' => $definitions[$module],
                            'type' => 'capability',
                            'description' => $descriptions[$module],
                        ]],
                    ];
                }, $modules),
            ];
        }

        $plans = [];
        foreach ((array) config('mediflow_modules.plans', []) as $planKey => $plan) {
            $modules = (array) ($plan['modules'] ?? []);
            if (in_array('*', $modules, true)) {
                $modules = $configuredModules;
            }
            $grants = [];
            foreach ($modules as $module) {
                if (isset($definitions[$module])) {
                    $grants[$definitions[$module]] = ['type' => 'capability', 'enabled' => true];
                }
            }
            $plans[] = ['key' => $planKey, 'grants' => $grants];
        }

        return [
            'schema_version' => (int) config('entitlement_framework.schema_version', 1),
            'product_code' => (string) config('entitlement_framework.product_code', 'MEDIFLOW'),
            'catalog_revision' => (int) config('entitlement_framework.catalog_revision', 1),
            'packages' => $packages,
            'plans' => $plans,
        ];
    }

    /**
     * Safe metadata for installation administrators. Signed claims and credentials are never returned.
     *
     * @return array<string, mixed>
     */
    public function diagnostics(): array
    {
        $state = $this->cache->state();
        $usable = $this->cache->hasUsableLicense();
        $claims = $this->cache->signatureIsValid($state) ? $this->signedClaims($state) : null;
        $framework = is_array($claims['entitlement_framework'] ?? null)
            ? $claims['entitlement_framework']
            : null;
        $issuedAt = is_string($claims['issued_at'] ?? null) && strtotime($claims['issued_at']) !== false
            ? Carbon::parse($claims['issued_at'])
            : null;

        return [
            'license_usable' => $usable,
            'license_status' => $state->status,
            'license_reason' => $usable ? 'active' : $this->cache->activationRequirementReason(),
            'source' => $framework['source'] ?? ($state->credential_kind === 'installation_bearer' ? 'legacy' : null),
            'catalog_revision' => $framework['catalog_revision'] ?? null,
            'source_revision' => $framework['source_revision'] ?? null,
            'entitlement_revision' => $state->entitlement_revision,
            'snapshot_age_seconds' => $issuedAt ? max(0, $issuedAt->diffInSeconds(now(), false)) : null,
            'last_successful_verification_at' => $state->last_successful_verification_at?->toIso8601String(),
            'offline_grace_expires_at' => $state->offline_grace_expires_at?->toIso8601String(),
            'last_error_code' => $state->last_error_code,
        ];
    }

    /**
     * @param  array{status?: string, source?: string|null}  $decision
     * @return array{allowed: bool, status: string, module: string, grant_key: string|null, source: string|null, entitlement_revision: int|null, catalog_revision: int|null, source_revision: int|null, snapshot_age_seconds: int|null}
     */
    private function decision(string $module, ?string $grantKey, array $decision, ?LicenseState $state): array
    {
        $claims = $state ? $this->signedClaims($state) : null;
        $framework = is_array($claims['entitlement_framework'] ?? null)
            ? $claims['entitlement_framework']
            : null;
        $issuedAt = is_string($claims['issued_at'] ?? null) && strtotime($claims['issued_at']) !== false
            ? Carbon::parse($claims['issued_at'])
            : null;
        $status = (string) ($decision['status'] ?? 'malformed');
        $result = [
            'allowed' => $status === 'granted',
            'status' => $status,
            'module' => $module,
            'grant_key' => $grantKey,
            'source' => $framework['source'] ?? ($decision['source'] ?? null),
            'entitlement_revision' => $state?->entitlement_revision,
            'catalog_revision' => $framework['catalog_revision'] ?? null,
            'source_revision' => $framework['source_revision'] ?? null,
            'snapshot_age_seconds' => $issuedAt ? max(0, $issuedAt->diffInSeconds(now(), false)) : null,
        ];

        if (! $result['allowed']) {
            Log::debug('mediflow.entitlement.denied', [
                'grant_key' => $grantKey,
                'module' => $module,
                'reason' => $status,
                'source' => $result['source'],
                'entitlement_revision' => $result['entitlement_revision'],
                'catalog_revision' => $result['catalog_revision'],
                'source_revision' => $result['source_revision'],
                'snapshot_age_seconds' => $result['snapshot_age_seconds'],
            ]);
        }

        return $result;
    }

    private function signedFramework(LicenseState $state): ?array
    {
        $claims = $this->signedClaims($state);

        return is_array($claims['entitlement_framework'] ?? null)
            ? $claims['entitlement_framework']
            : null;
    }

    private function signedClaims(LicenseState $state): ?array
    {
        $payloadJson = $state->server_signed_entitlements['payload_json'] ?? null;
        if (! is_string($payloadJson)) {
            return null;
        }

        try {
            $claims = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);

            return is_array($claims) ? $claims : null;
        } catch (\JsonException) {
            return null;
        }
    }
}
