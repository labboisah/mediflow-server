<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialistService extends Model
{
    protected $guarded = [];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }
}
