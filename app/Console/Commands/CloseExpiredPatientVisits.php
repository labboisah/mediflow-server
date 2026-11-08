<?php

namespace App\Console\Commands;

use App\Models\PatientVisit;
use Illuminate\Console\Command;

class CloseExpiredPatientVisits extends Command
{
    protected $signature = 'visits:close-expired';

    protected $description = 'Close patient visits older than 24 hours without active admission';

    public function handle()
    {
        $visits = PatientVisit::where('status', 'Active')
            ->where('visit_date', '<=', now()->subHours(24))
            ->whereDoesntHave('admissions', function ($query) {
                $query->whereIn('status', [
                    'registered',
                    'Registered',
                    'confirmed',
                    'Confirmed',
                ]);
            })
            ->get();

        foreach ($visits as $visit) {
            $visit->update([
                'status' => 'closed',
                'sync_status' => 'pending',
                'sync_updated_at' => now(),
            ]);
        }

        $this->info($visits->count() . ' expired visit(s) closed successfully.');

        return Command::SUCCESS;
    }
}