<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChildFollowUp extends Model
{
    use SoftDeletes, Syncable;

    protected $table = 'child_follow_ups';

    protected $fillable = [
        'newborn_id',
        'patient_id',
        'patient_visit_id',
        'recorded_by',
        'follow_up_date_time',
        'days_of_life',
        'follow_up_period',
        'location',
        'location_details',
        'feeding_type',
        'how_baby_is_feeding',
        'mother_observations',
        'temperature',
        'heart_rate',
        'respiratory_rate',
        'weight',
        'length',
        'head_circumference',
        'weight_percentile',
        'weight_change_since_birth',
        'weight_gain_rate',
        'weight_assessment',
        'general_appearance',
        'activity_level',
        'alertness',
        'skin_examination',
        'umbilical_cord_status',
        'umbilical_discharge',
        'signs_of_infection',
        'jaundice_present',
        'jaundice_level',
        'jaundice_management',
        'breast_examination',
        'latching_quality',
        'suckling_pattern',
        'milk_transfer',
        'bottle_feeding_if_applicable',
        'feeding_frequency',
        'feeding_duration',
        'feeding_problems',
        'mother_nipple_problems',
        'urinary_output',
        'stool_output',
        'stool_characteristics',
        'elimination_problems',
        'responsiveness',
        'cry_quality',
        'reflex_assessment',
        'muscle_tone',
        'immunizations_up_to_date',
        'immunizations_given',
        'immunizations_planned',
        'newborn_screening_done',
        'newborn_screening_results',
        'hearing_screening_done',
        'hearing_screening_results',
        'developmental_milestones',
        'developmental_concerns',
        'mother_recovery_status',
        'mother_emotional_wellbeing',
        'mother_breastfeeding_support',
        'baby_concerns',
        'mother_concerns',
        'complications_identified',
        'counseling_topics',
        'infant_care_advice_given',
        'feeding_guidance_given',
        'cord_care_advice_given',
        'hygiene_safety_advice_given',
        'danger_signs_explained',
        'clinical_summary',
        'health_status',
        'referral_reason',
        'referral_destination',
        'management_plan',
        'next_follow_up_date',
        'next_follow_up_reason',
    ];

    protected $casts = [
        'follow_up_date_time' => 'datetime',
        'next_follow_up_date' => 'datetime',
        'immunizations_up_to_date' => 'boolean',
        'newborn_screening_done' => 'boolean',
        'hearing_screening_done' => 'boolean',
        'infant_care_advice_given' => 'boolean',
        'feeding_guidance_given' => 'boolean',
        'cord_care_advice_given' => 'boolean',
        'hygiene_safety_advice_given' => 'boolean',
        'danger_signs_explained' => 'boolean',
    ];

    /**
     * Get the newborn being followed up
     */
    public function newborn()
    {
        return $this->belongsTo(Newborn::class);
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    /**
     * Get the mother (patient)
     */
    public function mother()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the user who recorded this follow-up
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope to get follow-ups with health concerns
     */
    public function scopeWithConcerns($query)
    {
        return $query->whereIn('health_status', ['at_risk', 'needs_referral', 'referred']);
    }

    /**
     * Scope to get follow-ups requiring referral
     */
    public function scopeNeedsReferral($query)
    {
        return $query->whereIn('health_status', ['needs_referral', 'referred']);
    }

    /**
     * Scope to get normal follow-ups
     */
    public function scopeNormal($query)
    {
        return $query->where('health_status', 'normal');
    }

    /**
     * Check if weight gain is adequate
     */
    public function isWeightGainAdequate()
    {
        // Typical weight gain: 20-30g/day for first week, 30-60g/day after
        if ($this->weight_gain_rate) {
            return stripos($this->weight_gain_rate, 'adequate') !== false ||
                   stripos($this->weight_assessment, 'improving') !== false;
        }
        return true;
    }

    /**
     * Check for jaundice requiring treatment
     */
    public function needsPhototherapy()
    {
        return $this->jaundice_present && 
               ($this->jaundice_level === 'high' || $this->jaundice_level === 'severe');
    }

    /**
     * Get next follow-up period
     */
    public function getNextFollowUpPeriod()
    {
        $periods = [
            'day_3' => 'day_7',
            'day_7' => 'day_10',
            'day_10' => 'day_14',
            'day_14' => '6weeks',
            '6weeks' => '3months',
            '3months' => '6months',
            '6months' => 'year1',
        ];

        return $periods[$this->follow_up_period] ?? null;
    }
}
