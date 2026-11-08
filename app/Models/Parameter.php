<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    protected $fillable = [
        'investigation_id',
        'name',
        'unit',
        'reference_range',
    ];

    public function investigation()
    {
        return $this->belongsTo(Investigation::class);
    }

    public function investigationResults() {
        return $this->hasMany(InvestigationResult::class);
    }
}
