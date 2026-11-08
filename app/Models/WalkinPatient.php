<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalkinPatient extends Model
{
    protected $guarded = [];

    public function bills() {
        return $this->hasMany(Bill::class);
    }

    public function departmentServiceRequests() {
        return $this->hasMany(DepartmentServiceRequest::class);
    }
}
