<?php

use App\Models\Bill;
use App\Models\BillInvestigation;
use App\Models\InvestigationRequest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schedule;

Schedule::command('visits:close-expired')->hourly();



Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('investigation:create-missing-requests {--dry-run}', function () {
    $this->info('Scanning bill investigations for missing investigation requests...');

    $processed = 0;
    $created = 0;
    $skipped = 0;
    $missingBill = 0;

    BillInvestigation::with('bill')
        ->chunkById(200, function ($investigations) use (&$processed, &$created, &$skipped, &$missingBill) {
            foreach ($investigations as $billInvestigation) {
                $processed++;

                $bill = $billInvestigation->bill;
                if (!$bill) {
                    $missingBill++;
                    $this->warn("Skipping BillInvestigation {$billInvestigation->id}: linked bill missing.");
                    continue;
                }

                $exists = InvestigationRequest::where('bill_id', $bill->id)
                    ->where('investigation_id', $billInvestigation->investigation_id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $created++;
                    continue;
                }

                $investigationRequest = InvestigationRequest::create([
                    'investigation_id' => $billInvestigation->investigation_id,
                    'patient_visit_id' => $bill->patient_visit_id,
                    'walkin_id' => $bill->walkin_id,
                    'bill_id' => $bill->id,
                    'requested_by' => $bill->issued_by,
                    'requested_at' => Carbon::now(),
                    'status' => 'pending',
                    'payment_status' => $bill->status,
                    'clinical_diagnoses' => 'Auto-created from bill investigation',
                ]);

                $bill->refreshRequestPaymentStatuses();

                if (!$bill->investigation_request_id) {
                    $bill->investigation_request_id = $investigationRequest->id;
                    $bill->save();
                }

                $created++;
            }
        });

    $this->info('Scan complete.');
    $this->line("Processed: {$processed}");
    $this->line("Created: {$created}");
    $this->line("Skipped existing: {$skipped}");
    $this->line("Missing bill references: {$missingBill}");
    if ($this->option('dry-run')) {
        $this->info('Dry-run mode: no requests were persisted.');
    }
})->purpose('Create missing investigation requests from existing bill investigations');

Artisan::command('bill:copy-amount-to-due-amount {--dry-run}', function () {
    $this->info('Copying bill amount to due_amount...');

    $processed = 0;
    $updated = 0;
    $skipped = 0;

    Bill::chunkById(200, function ($bills) use (&$processed, &$updated, &$skipped) {
        foreach ($bills as $bill) {
            $processed++;
            if ($bill->due_amount == $bill->amount) {
                $skipped++;
                continue;
            }

            if (!$this->option('dry-run')) {
                $bill->due_amount = $bill->amount;
                $bill->save();
            }
            $updated++;
        }
    });

    $this->info('Copy complete.');
    $this->line("Processed: {$processed}");
    $this->line("Updated: {$updated}");
    $this->line("Skipped (already equal): {$skipped}");
    if ($this->option('dry-run')) {
        $this->info('Dry-run mode: no records were modified.');
    }
})->purpose('Copy each bill amount into due_amount for existing bills');

Artisan::command('installation:admin {email} {--revoke}', function () {
    $user = \App\Models\User::where('email', $this->argument('email'))->first();
    if (! $user) {
        $this->error('Account not found. Create the user before granting installation access.');
        return 1;
    }
    $user->is_installation_admin = ! $this->option('revoke');
    $user->saveQuietly();
    \App\Models\AuditLog::create([
        'actor_id' => $user->id, 'action' => 'installation.admin_changed',
        'model_type' => \App\Models\User::class, 'model_id' => $user->id,
        'after' => ['is_installation_admin' => $user->is_installation_admin],
        'meta' => ['source' => 'console'],
    ]);
    $this->info($user->is_installation_admin ? 'Installation access granted.' : 'Installation access revoked.');
})->purpose('Grant or revoke installation configuration access for an existing account');

\Illuminate\Support\Facades\Schedule::command('partners:deliver')->everyMinute()->withoutOverlapping()
    ->when(fn()=>\Illuminate\Support\Facades\Schema::hasTable('partner_outbox') && app(\App\Services\LicenseService::class)->moduleEnabled('partner_network'));
