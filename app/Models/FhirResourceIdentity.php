<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FhirResourceIdentity extends Model
{
    protected $fillable = [
        'resource_type',
        'local_key',
        'logical_id',
    ];
}
