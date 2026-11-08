<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VitalSign extends Model
{
    use SoftDeletes, Syncable;


    protected $fillable = [
        'patient_visit_id',
        'body_temperature',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'heart_rate',
        'respiratory_rate',
        'oxygen_saturation',
        'blood_glucose',
        'weight',
        'height',
        'notes',
        'recorded_by',
        'recorded_date',
    ];

    protected $casts = [
        'body_temperature' => 'decimal:1',
        'blood_pressure_systolic' => 'integer',
        'blood_pressure_diastolic' => 'integer',
        'heart_rate' => 'integer',
        'respiratory_rate' => 'integer',
        'oxygen_saturation' => 'decimal:1',
        'blood_glucose' => 'decimal:2',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'recorded_date' => 'datetime',
    ];

    /**
     * Get the patient for this vital sign record
     */
    public function patientVisit()
    {
        return $this->belongsTo(PatientVisit::class);
    }

    /**
     * Get the user who recorded these vital signs
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
