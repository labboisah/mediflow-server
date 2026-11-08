<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'category',
        'is_active',
        'department_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

        /**
        * Get the department that offers this service
        */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function serviceRequests() {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * Get bills that include this service
     */
    public function bills()
    {
        return $this->belongsToMany(Bill::class, 'bill_services', 'service_id', 'bill_id')
            ->withPivot('quantity', 'unit_price', 'subtotal')
            ->withTimestamps();
    }

    /**
     * Get all active services
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get services by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category)->active();
    }
}
