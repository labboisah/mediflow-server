<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consumable extends Model
{
    protected $fillable = [
        'department_id',
        'name',
        'unit',
        'reorder_level',
        'current_quantity',
    ];

    protected $casts = [
        'current_quantity' => 'decimal:2',
        'reorder_level' => 'decimal:2',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function stocks()
    {
        return $this->hasMany(ConsumableStock::class);
    }

    public function usages()
    {
        return $this->hasMany(ConsumableUsage::class);
    }

    public function isBelowReorderLevel(): bool
    {
        return (float) $this->current_quantity <= (float) $this->reorder_level;
    }
}
