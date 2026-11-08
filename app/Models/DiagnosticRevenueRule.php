<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticRevenueRule extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function investigationType()
    {
        return $this->belongsTo(InvestigationType::class);
    }
}
