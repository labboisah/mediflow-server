<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillResult extends Model
{
    protected $fillable = [
        'bill_id',
        'bill_item_id',
        'staff_id',
        'performed_by',
        'reported_by',
        'performed_at',
        'reported_at',
        'clinical_note',
        'findings',
        'impression',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'performed_at' => 'datetime',
        'reported_at' => 'datetime',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function billItem()
    {
        return $this->belongsTo(BillItem::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
