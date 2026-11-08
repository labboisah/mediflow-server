<?php

namespace App\Console\Commands;

use App\Models\PartnerMembership;
use App\Models\Partnership;
use App\Models\PartnerType;
use App\Services\LicenseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PartnerNetworkCheck extends Command
{
    protected $signature = 'partners:check';

    protected $description = 'Read-only Care Network setup and operating-policy checks';

    public function handle(): int
    {
        if (! Schema::hasTable('partnerships')) {
            $this->error('Apply the Partner Network migration first.');

            return self::FAILURE;
        }
        $checks = ['Partner Network enabled' => app(LicenseService::class)->moduleEnabled('partner_network'), 'Partner types configured' => PartnerType::where('is_active', true)->exists(), 'Active relationships' => Partnership::where('status', 'active')->exists(), 'Active clinical intake membership' => PartnerMembership::where('role', 'intake')->where('is_active', true)->exists()];
        $this->table(['Prerequisite', 'Status'], collect($checks)->map(fn ($ok, $name) => [$name, $ok ? 'OK' : 'NEEDS SETUP'])->values()->all());
        $this->line('Document release: '.(config('partner_network.allow_manual_document_release') ? 'manual review policy enabled' : 'quarantined; manual release disabled'));
        $this->line('Email notices: '.(config('partner_network.notifications_enabled') ? 'enabled; verify mail and schedule partners:deliver' : 'disabled; authenticated portal remains the delivery surface'));
        $this->line('This checks setup, not identity credentials, clinical fitness or legal compliance.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
