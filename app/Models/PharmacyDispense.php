<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyDispense extends Model
{
    protected $guarded = [];

    public function medicineBatch()
    {
        return $this->belongsTo(MedicineBatch::class);
    }

    public function prescriptionItem()
    {
        return $this->belongsTo(PrescriptionItem::class);
    }
}
