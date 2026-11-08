<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Observation extends Model
{
    use Syncable;
    
    protected $guarded = [];
    
    public function recordedBy() {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function patientVisit() {
        return $this->belongsTo(PatientVisit::class);
    }
}
