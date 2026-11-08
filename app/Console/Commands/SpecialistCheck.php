<?php

namespace App\Console\Commands;

use App\Models\FileType;
use App\Models\PaymentMethod;
use App\Models\SpecialistProfile;
use App\Models\SpecialistService;
use App\Models\Specialty;
use App\Services\LicenseService;
use App\Services\PackageCapabilities;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SpecialistCheck extends Command
{
    protected $signature = 'specialist:check';

    protected $description = 'Check installation prerequisites for Specialist operations without changing data';

    public function handle(): int
    {
        foreach (['specialist_profiles', 'specialist_consultations', 'specialist_services', 'specialist_patient_access', 'specialist_documents'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error('Apply Specialist migrations first: '.$table.' is missing.');

                return self::FAILURE;
            }
        }
        $checks = [
            'Specialist and required modules enabled' => app(PackageCapabilities::class)->available('specialist'),
            'Active specialty configured' => Specialty::where('is_active', true)->exists(),
            'Active specialist with clinical privilege and module access' => SpecialistProfile::with('user')->where('is_active', true)->get()->contains(fn ($p) => $p->user
                && $p->user->hasPermission('specialist_consultation.update') && app(LicenseService::class)->userHasModuleAccess($p->user, 'specialist')),
            'Active service configured' => SpecialistService::whereHas('service', fn ($q) => $q->where('is_active', true))->whereHas('specialty', fn ($q) => $q->where('is_active', true))->exists(),
            'Patient file type configured' => FileType::exists(),
            'Active payment method configured' => PaymentMethod::where('is_active', true)->exists(),
        ];
        $this->table(['Prerequisite', 'Result'], collect($checks)->map(fn ($ok, $label) => [$label, $ok ? 'OK' : 'NEEDS SETUP'])->values()->all());
        $this->line('This checks configuration only. Clinical privileges, operating procedures and deployment acceptance still require local verification.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
