<?php

namespace App\Services;

use KernelBridge\LicensingClient\Services\LicenseCacheService;

class CentralLicenseService extends LicenseService
{
    public function __construct(private readonly LicenseCacheService $cache) {}

    public function currentPlan(): ?string
    {
        return $this->payload()['subscription']['primary_package'] ?? null;
    }

    public function selectedPackages(): array
    {
        return $this->payload()['subscription']['selected_packages'] ?? [];
    }

    public function configuredModules(): array
    {
        if (! $this->cache->hasUsableLicense()) {
            return config('mediflow_modules.required', ['core', 'access_control']);
        }

        return array_values(array_filter(
            array_keys(config('mediflow_modules.features')),
            fn (string $module): bool => $this->moduleEnabled($module),
        ));
    }

    public function enabledModules(): array
    {
        return $this->configuredModules();
    }

    public function moduleEnabled(?string $module): bool
    {
        if ($module === null || $module === '') {
            return true;
        }

        return app(ProductEntitlementService::class)->moduleEnabled($module);
    }

    /**
     * Safe installation-admin diagnostics; no signed payload or credentials are returned.
     *
     * @return array<string, mixed>
     */
    public function entitlementDiagnostics(): array
    {
        return app(ProductEntitlementService::class)->diagnostics();
    }

    private function payload(): array
    {
        try {
            return $this->cache->state()->entitlement_payload ?? [];
        } catch (\Throwable) {
            return [];
        }
    }
}
