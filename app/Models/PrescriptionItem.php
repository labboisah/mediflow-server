<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'medication_status_changed_at' => 'datetime',
    ];

    public const STATUS_STARTED = 'started';
    public const STATUS_STOPPED = 'stopped';

    public function prescription() {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine() {
        return $this->belongsTo(Medicine::class);
    }

    public function drugCharts() {
        return $this->hasMany(DrugChart::class);
    }

    public function prescribedBy() {
        return $this->belongsTo(User::class, 'prescribe_by');
    }

    public function medicationStatusChangedBy() {
        return $this->belongsTo(User::class, 'medication_status_changed_by');
    }

    public function route() {
        return $this->belongsTo(Route::class);
    }

    public function isStarted(): bool
    {
        return ($this->medication_status ?? self::STATUS_STARTED) === self::STATUS_STARTED;
    }

    public function startMedication(?int $userId = null): void
    {
        $this->update([
            'medication_status' => self::STATUS_STARTED,
            'medication_status_changed_at' => now(),
            'medication_status_changed_by' => $userId,
        ]);
    }

    public function stopMedication(?int $userId = null): void
    {
        $this->update([
            'medication_status' => self::STATUS_STOPPED,
            'medication_status_changed_at' => now(),
            'medication_status_changed_by' => $userId,
        ]);
    }
}
