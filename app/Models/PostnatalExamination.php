<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostnatalExamination extends Model
{
    use SoftDeletes;

    protected $table = 'postnatal_examinations';

    protected $fillable = [
        'delivery_id',
        'patient_id',
        'patient_visit_id',
        'recorded_by',
        'examination_date_time',
        'hours_post_delivery',
        'examination_time',
        'blood_pressure',
        'pulse_rate',
        'temperature',
        'respiration_rate',
        'general_appearance',
        'consciousness_level',
        'skin_colour',
        'uterine_size',
        'uterine_consistency',
        'uterine_tenderness',
        'fundal_height',
        'lochia_type',
        'lochia_amount',
        'lochia_odour',
        'clot_presence',
        'perineal_assessment',
        'perineal_wound_status',
        'perineal_pain',
        'vaginal_examination',
        'breast_examination',
        'nipple_condition',
        'breast_engorgement',
        'breast_milk_expression',
        'breastfeeding_successful',
        'breastfeeding_problems',
        'abdominal_examination',
        'wound_assessment',
        'drain_status',
        'lower_limbs_examination',
        'oedema_assessment',
        'calf_tenderness',
        'signs_of_dvt',
        'maternal_mood',
        'emotional_state',
        'signs_of_depression',
        'bonding_with_baby',
        'complications_identified',
        'infection_signs',
        'bleeding_assessment',
        'hypertension_assessment',
        'sleep_patterns',
        'pain_level',
        'activity_tolerance',
        'perineal_care_ability',
        'counseling_topics',
        'contraception_discussed',
        'contraception_method_chosen',
        'hygiene_taught',
        'danger_signs_explained',
        'clinical_summary',
        'recovery_status',
        'management_plan',
        'medications_prescribed',
        'follow_up_plan',
        'next_follow_up_date',
    ];

    protected $casts = [
        'examination_date_time' => 'datetime',
        'next_follow_up_date' => 'datetime',
        'breastfeeding_successful' => 'boolean',
        'signs_of_depression' => 'boolean',
        'contraception_discussed' => 'boolean',
        'hygiene_taught' => 'boolean',
        'danger_signs_explained' => 'boolean',
    ];

    /**
     * Get the delivery associated with this postnatal examination
     */
    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    /**
     * Get the mother (patient)
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the user who recorded this examination
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope to get examinations with complications
     */
    public function scopeComplicated($query)
    {
        return $query->where('recovery_status', '!=', 'normal');
    }

    /**
     * Scope to get examinations needing referral
     */
    public function scopeNeedsReferral($query)
    {
        return $query->where('recovery_status', 'needs_referral');
    }

    /**
     * Check if mother has signs of postpartum depression
     */
    public function hasPPDRisks()
    {
        return $this->signs_of_depression ||
               (stripos($this->maternal_mood, 'depressed') !== false) ||
               (stripos($this->emotional_state, 'sad') !== false);
    }

    /**
     * Check for infection signs
     */
    public function hasInfectionRisks()
    {
        return (stripos($this->lochia_odour, 'foul') !== false) ||
               (stripos($this->perineal_wound_status, 'infected') !== false) ||
               (stripos($this->infection_signs, 'present') !== false);
    }
}
