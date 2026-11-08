<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class SyncOperation extends Model
{
    protected $fillable = [
        'sync_uuid',
        'model_type',
        'model_id',
        'remote_id',
        'operation',
        'payload',
        'status',
        'error_message',
        'attempts',
        'last_attempted_at',
        'synced_at',
        'origin',
        'remote_version',
    ];

    protected $casts = [
        'payload' => 'array',
        'last_attempted_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the syncable model instance
     */
    public function syncable()
    {
        return $this->morphTo('model');
    }

    /**
     * Check if operation is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if operation failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if operation was synced
     */
    public function isSynced(): bool
    {
        return $this->status === 'synced';
    }

    /**
     * Mark as synced
     */
    public function markSynced($remoteId = null): void
    {
        $this->update([
            'status' => 'synced',
            'synced_at' => now(),
            'remote_id' => $remoteId,
            'error_message' => null,
        ]);

        $this->updateSourceModel([
            'sync_status' => 'synced',
            'remote_id' => $remoteId,
            'sync_updated_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markFailed($errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'last_attempted_at' => now(),
        ]);

        $this->updateSourceModel([
            'sync_status' => 'failed',
            'sync_updated_at' => now(),
        ]);
    }

    /**
     * Increment attempts
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts', 1);
        $this->update(['last_attempted_at' => now()]);
    }

    /**
     * Check if should retry
     */
    public function shouldRetry(): bool
    {
        $maxAttempts = config('sync.queue.max_attempts', 5);
        return $this->attempts < $maxAttempts;
    }

    private function updateSourceModel(array $attributes): void
    {
        if (!class_exists($this->model_type)) {
            return;
        }

        $model = $this->model_type::find($this->model_id);

        if (!$model) {
            return;
        }

        $this->model_type::withoutEvents(function () use ($model, $attributes) {
            $model->forceFill($attributes);
            $model->save();
        });
    }
}
