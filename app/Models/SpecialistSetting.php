<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialistSetting extends Model
{
    protected $guarded = [];

    protected $casts = ['allow_walkins' => 'boolean'];
}
