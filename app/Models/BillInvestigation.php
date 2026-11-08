<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillInvestigation extends Model
{
    protected $guarded = [];

     protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];  
    public function bill() {
        return $this->belongsTo(Bill::class);
    }

    public function investigation() {
        return $this->belongsTo(Investigation::class);
    }
}
