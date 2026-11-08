<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientReferral extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'specialist_consultation_id', 'specialist_token', 'destination_type', 'destination_user_id', 'collaboration_partner_id', 'urgency', 'outcome', 'outcome_admission_id',
        'patient_id',
        'referral_date',
        'referred_to_department',
        'reason_for_referral',
        'status',
        'notes',
        'referred_by',
        'accepted_date',
        'completed_date',
    ];

    protected $casts = [
        'referral_date' => 'datetime',
        'accepted_date' => 'datetime',
        'completed_date' => 'datetime',
    ];

    /**
     * Get the patient this referral belongs to
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the user who made this referral
     */
    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }
}
