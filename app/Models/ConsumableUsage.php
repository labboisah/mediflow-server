<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumableUsage extends Model
{
    protected $fillable = [
        'department_id',
        'consumable_id',
        'assigned_to',
        'assigned_by',
        'quantity',
        'usage_date',
        'purpose',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'usage_date' => 'date',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
