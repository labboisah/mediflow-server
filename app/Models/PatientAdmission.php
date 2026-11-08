<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientAdmission extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'patient_id',
        'admission_date',
        'discharge_date',
        'reason_for_admission',
        'department',
        'bed_number',
        'status',
        'notes',
        'admitted_by',
        'discharged_by',
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
    ];

    /**
     * Get the patient this admission belongs to
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the user who admitted this patient
     */
    public function admittedBy()
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    /**
     * Get the user who discharged this patient
     */
    public function dischargedBy()
    {
        return $this->belongsTo(User::class, 'discharged_by');
    }
}
