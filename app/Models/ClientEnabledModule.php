<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientEnabledModule extends Model
{
    protected $fillable = [
        'client_license_id',
        'module_name',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(ClientLicense::class, 'client_license_id');
    }
}
