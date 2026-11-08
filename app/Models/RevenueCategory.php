<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueCategory extends Model
{
    protected $guarded = [];

    public function revenues()
    {
        return $this->hasMany(Revenue::class);
    }
}
