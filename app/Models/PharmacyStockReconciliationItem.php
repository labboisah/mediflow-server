<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyStockReconciliationItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'variance_value' => 'decimal:2',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(PharmacyStockReconciliation::class, 'pharmacy_stock_reconciliation_id');
    }

    public function medicineBatch()
    {
        return $this->belongsTo(MedicineBatch::class);
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }
}
