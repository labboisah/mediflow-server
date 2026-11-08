<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Newborn extends Model
{
    use SoftDeletes;

    protected $table = 'newborns';

    protected $fillable = [
        'delivery_id',
        'patient_id',
        'patient_visit_id',
        'recorded_by',
        'sex',
        'birth_order',
        'newborn_registration_number',
        'birth_date_time',
        'birth_weight',
        'birth_length',
        'head_circumference',
        'presentation',
        'delivery_notes',
        'apgar_score_1_minute',
        'apgar_score_5_minutes',
        'apgar_score_10_minutes',
        'apgar_appearance_1min',
        'apgar_pulse_1min',
        'apgar_grimace_1min',
        'apgar_activity_1min',
        'apgar_respiration_1min',
        'general_condition',
        'physical_examination',
        'birth_defects_noted',
        'meconium_aspiration',
        'breastfeeding_initiated',
        'first_breastfeed_time',
        'feeding_problems',
        'vitamin_k_given',
        'eye_prophylaxis_given',
        'immunizations_given',
        'immunizations_details',
        'screening_test_done',
        'screening_test_results',
        'special_care_needed',
        'referred_to',
        'status',
        'neonatal_observations',
    ];

    protected $casts = [
        'birth_date_time' => 'datetime',
        'first_breastfeed_time' => 'datetime',
        'breastfeeding_initiated' => 'boolean',
        'vitamin_k_given' => 'boolean',
        'eye_prophylaxis_given' => 'boolean',
        'immunizations_given' => 'boolean',
        'screening_test_done' => 'boolean',
    ];

    /**
     * Get the delivery this newborn came from
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
    public function mother()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the user who recorded newborn information
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get all examinations for this newborn
     */
    public function examinations()
    {
        return $this->hasMany(NewbornExamination::class);
    }

    public function generateRegistrationNumber() {
        return 'NB' . now()->format('YmdHis') . rand(1000, 9999);
    }

    /**
     * Get all follow-up visits for this baby
     */
    public function followUps()
    {
        return $this->hasMany(ChildFollowUp::class);
    }

    /**
     * Get the latest examination
     */
    public function latestExamination()
    {
        return $this->examinations()->latest('examination_date_time')->first();
    }

    /**
     * Check if APGAR score indicates distress
     */
    public function hasApgarDistress()
    {
        return ($this->apgar_score_1_minute && $this->apgar_score_1_minute < 7) ||
               ($this->apgar_score_5_minutes && $this->apgar_score_5_minutes < 7);
    }

    /**
     * Scope to get live newborns
     */
    public function scopeAlive($query)
    {
        return $query->where('status', 'alive');
    }

    /**
     * Scope to get stillborn
     */
    public function scopeStillborn($query)
    {
        return $query->where('status', 'stillborn');
    }

    /**
     * Scope to get males
     */
    public function scopeMale($query)
    {
        return $query->where('sex', 'male');
    }

    /**
     * Scope to get females
     */
    public function scopeFemale($query)
    {
        return $query->where('sex', 'female');
    }
}
