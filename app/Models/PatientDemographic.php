<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientDemographic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'age',
        'lga',
        'occupation',
        'marital_status',
        'address',
        'phone_number',
        'email',
        'lga_id'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Get the patient this demographic belongs to
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function lga() {
        return $this->belongsTo(Lga::class);
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Calculate age from date of birth
     */
    public function calculateAge()
    {
        if ($this->date_of_birth) {
            $this->age = $this->date_of_birth->diffInYears(now());
            return $this->age;
        }
        return null;
    }
}
