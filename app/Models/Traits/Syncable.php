<?php

namespace App\Models\Traits;

use App\Models\SyncOperation;
use Illuminate\Support\Str;

trait Syncable
{
    /**
     * Boot the trait
     */
    public static function bootSyncable()
    {
        static::observe(\App\Observers\SyncObserver::class);
    }

    /**
     * Get the sync UUID
     */
    public function getSyncUuid(): string
    {
        return $this->sync_uuid ?? '';
    }

    /**
     * Generate a sync UUID if not present
     */
    public function ensureSyncUuid(): void
    {
        if (!$this->sync_uuid) {
            $this->sync_uuid = (string) Str::uuid();
            $this->save();
        }
    }

    /**
     * Get sync operations for this model
     */
    public function syncOperations()
    {
        return $this->hasMany(SyncOperation::class, 'model_id')
            ->where('model_type', static::class);
    }

    /**
     * Get pending sync operations
     */
    public function pendingSyncOperations()
    {
        return $this->syncOperations()->where('status', 'pending');
    }

    /**
     * Create a sync operation for this model
     */
    public function createSyncOperation($operation = 'update', $payload = null): SyncOperation
    {
        if (app(\App\Services\SpecialistSyncBoundary::class)->localOnly($this)) {
            throw new \LogicException('Specialist clinical records require a complete exchange adapter and remain local to this installation.');
        }
        $this->ensureSyncUuid();

        return SyncOperation::create([
            'sync_uuid' => $this->sync_uuid,
            'model_type' => static::class,
            'model_id' => $this->getKey(),
            'operation' => $operation,
            'payload' => $payload ?? $this->getSyncPayload(),
            'status' => 'pending',
            'origin' => config('sync.environment'),
        ]);
    }

    /**
     * Define related models whose sync_uuid should be included in the payload.
     */
    public function syncRelations(): array
    {
        return [];
    }

    /**
     * Define dependency fields that must exist remotely before syncing this model.
     */
    public function syncDependencies(): array
    {
        return [];
    }

    /**
     * Get the payload to send during sync.
     */
    public function getSyncPayload(): array
    {
        $excluded = config('sync.excluded_fields', []);
        $payload = [];

        foreach ($this->getAttributes() as $key => $value) {
            if (!in_array($key, $excluded, true)) {
                $payload[$key] = $value;
            }
        }

        $payload['sync_uuid'] = $this->sync_uuid;

        foreach ($this->syncRelations() as $relation => $payloadKey) {
            if (!method_exists($this, $relation)) {
                continue;
            }

            $related = $this->{$relation};
            if ($related && isset($related->sync_uuid)) {
                $payload[$payloadKey] = $related->sync_uuid;
            }
        }

        return $payload;
    }

    /**
     * Apply sync data from remote
     */
    public function applySyncData(array $data): void
    {
        $excluded = config('sync.excluded_fields', []);
        $excluded[] = 'id';
        $excluded[] = 'sync_uuid';
        $excluded[] = 'created_at';

        foreach ($data as $key => $value) {
            if (!in_array($key, $excluded, true) && $this->isFillable($key)) {
                $this->{$key} = $value;
            }
        }

        $this->sync_origin = config('sync.environment');
        $this->sync_status = 'synced';
        $this->save();
    }

    /**
     * Check if attribute is fillable
     */
    public function isFillable($key): bool
    {
        if ($this->fillable) {
            return in_array($key, $this->fillable, true);
        }

        return !in_array($key, $this->guarded, true);
    }

    /**
     * Get sync status
     */
    public function getSyncStatus(): string
    {
        return $this->sync_status ?? 'unknown';
    }

    /**
     * Mark as synced
     */
    public function markAsSynced($remoteId = null): void
    {
        $this->update([
            'sync_status' => 'synced',
            'remote_id' => $remoteId,
            'sync_updated_at' => now(),
        ]);
    }
}
