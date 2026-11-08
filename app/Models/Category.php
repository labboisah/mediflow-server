<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = [];

    public function services() {
        return $this->hasMany(Service::class);
    }

    public function revenueRule()
    {
        return $this->hasOne(RevenueSharingRule::class);
    }

    public function revenueSharingRule() {
        return $this->hasOne(RevenueSharingRule::class);
    }
}
