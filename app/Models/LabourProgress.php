<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabourProgress extends Model
{
    use SoftDeletes, Syncable;

    protected $table = 'labour_progress';

   
    protected $fillable = [
        'labour_id',
        'recorded_by',
        'recorded_at',
        'contraction_frequency',
        'contraction_duration',
        'contraction_intensity',
        'cervical_dilation',
        'cervical_effacement',
        'cervical_consistency',
        'cervical_position',
        'fetal_station',
        'fetal_position',
        'uterine_tone',
        'uterine_tenderness',
        'vaginal_examination_findings',
        'fetal_heart_rate',
        'fetal_heart_variability',
        'fetal_movements',
        'meconium_stained_liquor',
        'blood_pressure',
        'pulse_rate',
        'temperature',
        'maternal_pain_relief',
        'coping_mechanisms',
        'interventions',
        'medications_given',
        'observations_and_notes',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    /**
     * Get the labour record this progress belongs to
     */
    public function labour()
    {
        return $this->belongsTo(Labour::class);
    }

    /**
     * Get the user who recorded this progress
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Check if labour is progressing normally
     */
    public function isProgressingNormally()
    {
        // Expected dilation approximately 1cm per hour in active labour
        // This is a simplified check
        if ($this->cervical_dilation && $this->labour) {
            $labourDuration = $this->recorded_at->diffInHours($this->labour->labour_onset_time);
            // Expected dilation: labour_duration (approximately)
            return $this->cervical_dilation >= ($labourDuration * 0.8); // 80% of expected
        }
        return true;
    }

    /**
     * Scope to get recent progress records
     */
    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes))
                    ->orderBy('recorded_at', 'desc');
    }
}
