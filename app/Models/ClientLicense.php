<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientLicense extends Model
{
    protected $fillable = [
        'client_name',
        'plan',
        'selected_packages',
        'license_key',
        'branch_limit',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'selected_packages' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'branch_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    public function enabledModules(): HasMany
    {
        return $this->hasMany(ClientEnabledModule::class);
    }

}
