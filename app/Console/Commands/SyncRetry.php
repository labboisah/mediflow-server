<?php

namespace App\Console\Commands;

use App\Jobs\SyncRecordJob;
use App\Models\SyncOperation;
use Illuminate\Console\Command;

class SyncRetry extends Command
{
    protected $signature = 'sync:retry {uuid? : UUID of sync operation to retry} {--all : Retry all failed operations}';
    protected $description = 'Retry failed sync operations';

    public function handle()
    {
        if ($this->option('all')) {
            $this->retryAll();
        } elseif ($uuid = $this->argument('uuid')) {
            $this->retrySingle($uuid);
        } else {
            $this->error('Provide UUID or use --all flag');
            return 1;
        }

        return 0;
    }

    private function retrySingle(string $uuid)
    {
        $sync = SyncOperation::where('sync_uuid', $uuid)->first();

        if (!$sync) {
            $this->error("Sync operation not found: {$uuid}");
            return;
        }

        if ($sync->status !== 'failed') {
            $this->warn("Sync operation is not in failed state (current: {$sync->status})");
            return;
        }

        // Reset attempts and dispatch job
        $sync->update([
            'status' => 'pending',
            'attempts' => 0,
            'error_message' => null,
        ]);

        SyncRecordJob::dispatch($sync)
            ->onQueue(config('sync.queue.name', 'sync'));

        $this->info("Retrying sync operation: {$uuid}");
    }

    private function retryAll()
    {
        $failed = SyncOperation::where('status', 'failed')->get();

        if ($failed->isEmpty()) {
            $this->info('No failed sync operations to retry');
            return;
        }

        $count = 0;
        foreach ($failed as $sync) {
            if ($sync->attempts < config('sync.queue.max_attempts', 5)) {
                $sync->update([
                    'status' => 'pending',
                    'attempts' => 0,
                    'error_message' => null,
                ]);

                SyncRecordJob::dispatch($sync)
                    ->onQueue(config('sync.queue.name', 'sync'));

                $count++;
            }
        }

        $this->info("Queued {$count} sync operations for retry");
    }
}
