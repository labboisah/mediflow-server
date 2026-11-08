<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialistConsultation extends Model
{
    protected $guarded = [];

    protected $casts = ['clinical' => 'array', 'charge' => 'decimal:2', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'followup_due' => 'date'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function profile()
    {
        return $this->belongsTo(SpecialistProfile::class, 'specialist_profile_id');
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function followup()
    {
        return $this->belongsTo(self::class, 'followup_consultation_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function investigations()
    {
        return $this->hasMany(InvestigationRequest::class);
    }

    public function referrals()
    {
        return $this->hasMany(PatientReferral::class);
    }

    public function amendments()
    {
        return $this->hasMany(SpecialistAmendment::class);
    }

    public function carePlans()
    {
        return $this->hasMany(SpecialistCarePlan::class)->orderByDesc('version');
    }

    public function documents()
    {
        return $this->hasMany(SpecialistDocument::class);
    }
}
