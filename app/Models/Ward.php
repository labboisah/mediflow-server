<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    protected $fillable = ['name', 'capacity','price'];

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }

    public function getAvailableBed()
    {
        return $this->beds()->where('status', 'vacant')->first();
    }
}
