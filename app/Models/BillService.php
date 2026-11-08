<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillService extends Model
{
    protected $fillable = [
        'bill_id',
        'service_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the bill that owns this service
     */
    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Get the service associated with this bill service
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
