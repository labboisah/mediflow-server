<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $guarded = [];

    public function prescriptionItems() {
        return $this->hasMany(PrescriptionItem::class);
    }
}
