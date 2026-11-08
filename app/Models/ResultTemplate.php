<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'clinical_note',
        'findings',
        'impression',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
