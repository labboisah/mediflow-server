<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'name',
        'label',
        'route',
        'icon',
        'group',
        'license_module',
        'sidebar_group',
        'sidebar_patterns',
        'sort_order',
        'is_active',
        'is_sidebar_visible',
    ];

    protected $casts = [
        'sidebar_patterns' => 'array',
        'is_active' => 'boolean',
        'is_sidebar_visible' => 'boolean',
    ];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'module_role');
    }
}
