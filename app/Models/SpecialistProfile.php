<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialistProfile extends Model
{
    protected $guarded = [];

    protected $casts = ['availability' => 'array', 'online' => 'boolean', 'physical' => 'boolean', 'is_active' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'specialist_profile_specialty');
    }
}
