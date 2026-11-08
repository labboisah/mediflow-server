<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyService extends Model
{
    protected $fillable = ['name', 'price', 'is_active'];
    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean'];

    public static function canManage(?User $user): bool
    {
        return $user && $user->isDepartmentHead()
            && str_contains(strtolower((string) $user->department?->name), 'pharmacy');
    }
}
