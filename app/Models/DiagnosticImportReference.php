<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticImportReference extends Model
{
    protected $guarded = [];
    protected $casts = ['metadata' => 'array'];

    public function target()
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id');
    }
}
