<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewbornExamination extends Model
{
    use SoftDeletes, Syncable;

    protected $table = 'newborn_examinations';

    protected $fillable = [
        'newborn_id',
        'recorded_by',
        'examination_date_time',
        'hours_after_birth',
        'temperature',
        'heart_rate',
        'respiratory_rate',
        'weight',
        'length',
        'head_circumference',
        'chest_circumference',
        'general_appearance',
        'skin_examination',
        'head_and_neck',
        'eyes_examination',
        'ear_examination',
        'mouth_and_throat',
        'heart_sounds',
        'pulses',
        'capillary_refill',
        'chest_expansion',
        'breath_sounds',
        'nasal_breathing',
        'abdomen_shape',
        'umbilical_cord_check',
        'hepatomegaly_splenomegaly',
        'bowel_sounds',
        'genitalia_examination',
        'urinary_output',
        'stool_output',
        'reflex_assessment',
        'muscle_tone',
        'developmental_screening',
        'extremities_examination',
        'hip_examination',
        'spine_examination',
        'abnormal_findings',
        'congenital_anomalies',
        'jaundice_present',
        'jaundice_level',
        'jaundice_management',
        'feeding_type',
        'feeding_tolerance',
        'feeding_challenges',
        'clinical_summary',
        'exam_status',
        'follow_up_plans',
        'next_follow_up_date',
    ];

    protected $casts = [
        'examination_date_time' => 'datetime',
        'next_follow_up_date' => 'datetime',
    ];

    /**
     * Get the newborn being examined
     */
    public function newborn()
    {
        return $this->belongsTo(Newborn::class);
    }

    /**
     * Get the user who recorded this examination
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope to get examinations with abnormal findings
     */
    public function scopeAbnormal($query)
    {
        return $query->where('exam_status', '!=', 'normal');
    }

    /**
     * Scope to get examinations needing referral
     */
    public function scopeNeedsReferral($query)
    {
        return $query->whereIn('exam_status', ['needs_follow_up', 'referral_needed']);
    }

    /**
     * Calculate weight for growth assessment
     */
    public function getWeightPercentile()
    {
        // This would typically use growth charts
        // Placeholder for actual implementation
        return null;
    }
}
