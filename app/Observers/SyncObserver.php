<?php

namespace App\Observers;

use App\Jobs\SyncRecordJob;
use Illuminate\Database\Eloquent\Model;

class SyncObserver
{
    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        if (!config('sync.behavior.auto_sync_enabled') || app(\App\Services\SpecialistSyncBoundary::class)->localOnly($model)) {
            return;
        }

        $this->dispatchSyncJob($model, 'create');
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        if (!config('sync.behavior.auto_sync_enabled') || app(\App\Services\SpecialistSyncBoundary::class)->localOnly($model)) {
            return;
        }

        if ($model->wasRecentlyCreated) {
            return;
        }

        $syncFields = $this->syncFields();
        $changedFields = array_keys($model->getChanges());
        $realChanges = array_filter($changedFields, fn($field) => !in_array($field, $syncFields, true));

        if (count($realChanges) > 0) {
            $this->dispatchSyncJob($model, 'update');
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        if (!config('sync.behavior.auto_sync_enabled') || app(\App\Services\SpecialistSyncBoundary::class)->localOnly($model)) {
            return;
        }

        $this->dispatchSyncJob($model, 'delete');
    }

    /**
     * Dispatch sync job for the model.
     */
    private function dispatchSyncJob(Model $model, string $operation): void
    {
        if (method_exists($model, 'ensureSyncUuid')) {
            $model->ensureSyncUuid();
        }

        $model::withoutEvents(function () use ($model) {
            $model->forceFill([
                'sync_status' => 'pending',
                'sync_updated_at' => now(),
            ]);

            $model->save();
        });

        $syncOperation = $model->createSyncOperation($operation);

        $delay = config('sync.observer.dispatch_delay', 3);

        SyncRecordJob::dispatch($syncOperation)
            ->delay($delay)
            ->onQueue(config('sync.queue.name', 'sync'))
            ->onConnection(config('sync.queue.connection', 'database'));
    }

    private function syncFields(): array
    {
        return ['sync_uuid', 'sync_status', 'sync_origin', 'sync_updated_at', 'remote_id'];
    }
}
