<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\RevenueCalculator;

class BillItem extends Model
{
    protected $fillable = [
        'bill_id',
        'service_id',
        'price',
        
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function revenueDistribution() {
        return $this->hasOne(RevenueDistribution::class);
    }

    public function result()
    {
        return $this->hasOne(BillResult::class);
    }

    public function shares() {
        return RevenueCalculator::calculate($this->service, $this->finalAmount());
    }

    function finalAmount() {
        return $this->price - $this->bill->discount_amount;
    }
}
