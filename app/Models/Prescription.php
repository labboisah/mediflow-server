<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use Syncable;
    
    protected $guarded = [];

    public function prescriptionItems() {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function patientVisit() {
        return $this->belongsTo(PatientVisit::class);
    }

    public function prescribedBy() {
        return $this->belongsTo(User::class, 'prescribe_by');
    }
}
