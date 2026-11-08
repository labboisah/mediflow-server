<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialistDocument extends Model
{
    protected $guarded = [];

    public function consultation()
    {
        return $this->belongsTo(SpecialistConsultation::class, 'specialist_consultation_id');
    }
}
