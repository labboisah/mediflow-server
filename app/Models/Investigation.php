<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Investigation extends Model
{
    protected $fillable = [
        'investigation_type_id',
        'name',
        'price',
        'code',
    ];

    public function investigationType()
    {
        return $this->belongsTo(InvestigationType::class);
    }

    public function parameters()
    {
        return $this->hasMany(Parameter::class);
    } 
    
    public function investigationRequests()
    {
        return $this->hasMany(InvestigationRequest::class);
    }
}
