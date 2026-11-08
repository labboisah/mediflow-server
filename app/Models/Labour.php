<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Labour extends Model
{
    use SoftDeletes, Syncable;

    protected $table = 'labours';

    protected $guarded = [];

    protected $casts = [
        'labour_onset_time' => 'datetime',
        'first_stage_started_at' => 'datetime',
        'second_stage_started_at' => 'datetime',
        'third_stage_started_at' => 'datetime',
    ];

    /**
     * Get the patient in labour
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    /**
     * Get the admission for this labour
     */
    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * Get the midwife/staff who recorded this
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get all progress records for this labour
     */
    public function progressRecords()
    {
        return $this->hasMany(LabourProgress::class);
    }

    /**
     * Get the delivery associated with this labour
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Calculate duration of labour stage
     */
    public function calculateStageDuration($stage)
    {
        $startTime = match($stage) {
            'first' => $this->first_stage_started_at,
            'second' => $this->second_stage_started_at,
            'third' => $this->third_stage_started_at,
            default => null
        };

        if (!$startTime) {
            return null;
        }

        $endTime = match($stage) {
            'first' => $this->second_stage_started_at ?? now(),
            'second' => $this->third_stage_started_at ?? now(),
            'third' => now(),
            default => now()
        };

        return $startTime->diffInMinutes($endTime);
    }

    /**
     * Scope to get active labours
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ongoing');
    }

    /**
     * Scope to get complicated labours
     */
    public function scopeComplicated($query)
    {
        return $query->where('status', 'complicated');
    }
}
