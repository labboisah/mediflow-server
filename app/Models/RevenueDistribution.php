<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueDistribution extends Model
{
    protected $guarded = [];

    public function billItem() {
        return $this->belongsTo(BillItem::class);
    }
}
