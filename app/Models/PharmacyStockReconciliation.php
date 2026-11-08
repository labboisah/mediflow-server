<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyStockReconciliation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'checked_date' => 'date',
        'total_variance_value' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PharmacyStockReconciliationItem::class);
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function stockTransaction()
    {
        return $this->belongsTo(StockTransaction::class);
    }
}
