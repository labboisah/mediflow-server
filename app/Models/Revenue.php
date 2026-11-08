<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    protected $guarded = [];

     protected $casts = [
        'amount' => 'decimal:2',
        'revenue_date' => 'date',
    ];

    public function recordedBy() {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function category() {
        return $this->belongsTo(RevenueCategory::class, 'revenue_category_id');
    }

    public function department() {
        return $this->belongsTo(Department::class);
    }

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
