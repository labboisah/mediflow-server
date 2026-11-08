<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitActivity extends Model
{
    protected $guarded =[];
    
    
    public function recordedBy() {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function patientVisit() {
        return $this->belongsTo(PatientVisit::class);
    }
}
