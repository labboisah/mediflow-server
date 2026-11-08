<?php

namespace App\Console\Commands;

use App\Models\SyncOperation;
use Illuminate\Console\Command;

class SyncStatus extends Command
{
    protected $signature = 'sync:status';
    protected $description = 'Display sync operations status';

    public function handle()
    {
        $this->info('Sync Status Dashboard');
        $this->info('=' . str_repeat('=', 70));

        // Count operations by status
        $pending = SyncOperation::where('status', 'pending')->count();
        $synced = SyncOperation::where('status', 'synced')->count();
        $failed = SyncOperation::where('status', 'failed')->count();

        $this->table(
            ['Status', 'Count'],
            [
                ['Pending', $pending],
                ['Synced', $synced],
                ['Failed', $failed],
            ]
        );

        // Failed operations
        if ($failed > 0) {
            $this->warn("\nFailed Operations (last 10):");
            $failedOps = SyncOperation::where('status', 'failed')
                ->latest()
                ->limit(10)
                ->get(['sync_uuid', 'model_type', 'operation', 'attempts', 'error_message', 'created_at']);

            $this->table(
                ['UUID', 'Model', 'Op', 'Attempts', 'Error'],
                $failedOps->map(fn($op) => [
                    substr($op->sync_uuid, 0, 8),
                    class_basename($op->model_type),
                    $op->operation,
                    $op->attempts,
                    substr($op->error_message ?? 'N/A', 0, 30),
                ])->toArray()
            );
        }

        // Pending operations
        if ($pending > 0) {
            $this->info("\nPending Operations (first 10):");
            $pendingOps = SyncOperation::where('status', 'pending')
                ->oldest()
                ->limit(10)
                ->get(['sync_uuid', 'model_type', 'operation', 'attempts', 'created_at']);

            $this->table(
                ['UUID', 'Model', 'Op', 'Attempts', 'Created'],
                $pendingOps->map(fn($op) => [
                    substr($op->sync_uuid, 0, 8),
                    class_basename($op->model_type),
                    $op->operation,
                    $op->attempts,
                    $op->created_at->diffForHumans(),
                ])->toArray()
            );
        }

        $this->info("\nConfiguration:");
        $this->table(
            ['Setting', 'Value'],
            [
                ['Environment', config('sync.environment')],
                ['Auto Sync Enabled', config('sync.behavior.auto_sync_enabled') ? 'Yes' : 'No'],
                ['Conflict Resolution', config('sync.behavior.conflict_resolution')],
                ['Queue Connection', config('sync.queue.connection')],
                ['Max Attempts', config('sync.queue.max_attempts')],
            ]
        );
    }
}
