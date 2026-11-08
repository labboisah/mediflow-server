<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lga extends Model
{
    protected $guarded = [];

    public function state() {
        return $this->belongsTo(State::class);
    }

    /**
     * Get all patient demographics for this LGA
     */
    public function patientDemographics() {
        return $this->hasMany(PatientDemographic::class);
    }
}
