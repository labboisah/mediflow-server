<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    protected $guarded = [];

    function stockTransactionItems() {
        return $this->hasMany(StockTransactionItem::class, 'transaction_id');
    }

    public function serviceItems() {
        return $this->hasMany(PharmacyServiceItem::class, 'transaction_id');
    }

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bill() {
        return $this->belongsTo(Bill::class);
    }

    public function payment() {
        return $this->belongsTo(Payment::class);
    }
}
