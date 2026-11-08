<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticRevenueDistribution extends Model
{
    protected $guarded = [];
    protected $casts = ['booked_at' => 'datetime'];

    public function billInvestigation()
    {
        return $this->belongsTo(BillInvestigation::class);
    }
}
