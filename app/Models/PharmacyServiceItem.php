<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyServiceItem extends Model
{
    protected $guarded = [];
    protected $casts = ['price' => 'decimal:2', 'subtotal' => 'decimal:2'];
}
