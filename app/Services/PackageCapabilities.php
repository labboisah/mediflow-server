<?php

namespace App\Services;

class PackageCapabilities
{
    public function __construct(private LicenseService $license) {}

    public function available(string $capability): bool
    {
        $requirements = match ($capability) {
            'specialist' => ['specialist', 'patient_records', 'clinical_care', 'billing'],
            'dispensing' => ['pharmacy', 'patient_records', 'clinical_care', 'billing'],
            'laboratory' => ['laboratory', 'patient_records', 'billing'],
            'imaging' => ['radiology', 'patient_records', 'billing'],
            'inpatient' => ['clinical_care', 'wards_beds', 'patient_records'],
            'maternity' => ['maternity', 'patient_records'],
            default => null,
        };

        return $requirements !== null && collect($requirements)->every(fn ($module) => $this->license->moduleEnabled($module));
    }
}
