<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['brand_name', 'address', 'welcome_heading', 'welcome_statement', 'welcome_template', 'logo_path', 'welcome_appearance'];

    protected $casts = ['welcome_appearance' => 'array'];
}
