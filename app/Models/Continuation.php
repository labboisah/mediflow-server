<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Continuation extends Model
{
    use Syncable;
    
    protected $guarded = [];

    public function patientVisit(){

        return $this->belongsTo(PatientVisit::class);
    }

    public function writtenBy() {
        return $this->belongsTo(User::class, 'written_by');
    }

    public function sendTo() {
        return $this->belongsTo(Department::class, 'send_to');
    }
}
