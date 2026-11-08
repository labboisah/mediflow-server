<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticBillContact extends Model
{
    protected $guarded = [];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }
}
