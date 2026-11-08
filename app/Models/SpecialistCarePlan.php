<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialistCarePlan extends Model
{
    protected $guarded = [];

    protected $casts = ['review_date' => 'date'];
}
