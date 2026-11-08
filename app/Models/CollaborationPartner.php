<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollaborationPartner extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'is_local'=>'boolean','details'=>'array'];
}
