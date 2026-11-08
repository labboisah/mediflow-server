<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransactionItem extends Model
{
    protected $guarded = [];

    public function transaction() {
        return $this->belongsTo(StockTransaction::class, 'transaction_id');
    }

    public function medicineBatch() {
        return $this->belongsTo(MedicineBatch::class);
    }

    public function prescriptionItem() {
        return $this->belongsTo(PrescriptionItem::class);
    }
}
