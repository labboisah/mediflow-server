<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueSharingRule extends Model
{
    protected $fillable = [
        'category_id',
        'radiologist_percent',
        'radiographer_percent',
        'staff_percent',
        'annex_percent'
    ];
}
