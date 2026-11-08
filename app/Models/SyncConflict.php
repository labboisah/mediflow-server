<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncConflict extends Model
{
    protected $fillable = [
        'sync_uuid',
        'model_type',
        'model_id',
        'conflict_type',
        'local_data',
        'remote_data',
        'resolution',
        'notes',
        'resolved_at',
    ];

    protected $casts = [
        'local_data' => 'array',
        'remote_data' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * Resolve conflict by keeping local version
     */
    public function resolveLocal($notes = null): void
    {
        $this->update([
            'resolution' => 'keep_local',
            'notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Resolve conflict by accepting remote version
     */
    public function resolveRemote($notes = null): void
    {
        $this->update([
            'resolution' => 'accept_remote',
            'notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark for manual review
     */
    public function markForReview($notes = null): void
    {
        $this->update([
            'resolution' => 'manual_review',
            'notes' => $notes,
        ]);
    }

    /**
     * Check if conflict is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolution !== 'pending';
    }
}
