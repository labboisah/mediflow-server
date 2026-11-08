<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AntenatalCare extends Model
{
    use SoftDeletes, Syncable;

    protected $table = 'antenatal_cares';

    protected $fillable = [
        'patient_id',
        'patient_visit_id',
        'recorded_by',
        'last_menstrual_period',
        'expected_delivery_date',
        'gestational_weeks',
        'number_of_fetuses',
        'pregnancy_type',
        'blood_pressure',
        'weight',
        'height',
        'abdominal_examination',
        'fundal_height',
        'fetal_heart_rate',
        'fetal_movement',
        'vaginal_examination',
        'urine_analysis',
        'blood_tests',
        'ultrasound_findings',
        'risk_factors',
        'complications',
        'management_plan',
        'counseling_topics',
        'took_supplements',
        'clinical_notes',
        'status',
    ];

    protected $casts = [
        'last_menstrual_period' => 'date',
        'expected_delivery_date' => 'date',
        'took_supplements' => 'boolean',
    ];

    /**
     * Get the patient for this antenatal care
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the patient visit for this antenatal care
     */
    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    /**
     * Get the user who recorded this
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope to get only current pregnancy records
     */
    public function scopeCurrentPregnancy($query)
    {
        return $query->where('status', '!=', 'delivered');
    }

    /**
     * Scope to get high-risk pregnancies
     */
    public function scopeHighRisk($query)
    {
        return $query->where('status', 'high_risk');
    }

    /**
     * Calculate BMI
     */
    public function getBmi()
    {
        if ($this->weight && $this->height) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 2);
        }
        return null;
    }

    /**
     * Check if pregnancy is overdue
     */
    public function isOverdue()
    {
        if ($this->expected_delivery_date) {
            return now()->isAfter($this->expected_delivery_date);
        }
        return false;
    }
}
