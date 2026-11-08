<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class MedicineBatch extends Model
{
    protected $guarded = [];

    public function medicine() {
        return $this->belongsTo(Medicine::class);
    }

    public function expiryBadgeVariant(): string
    {
        $expiryDate = Carbon::parse($this->expiry_date);

        if ($expiryDate->lt(today()->addMonths(5))) {
            return 'danger';
        }

        if ($expiryDate->lt(today()->addYear())) {
            return 'warning';
        }

        if ($expiryDate->lte(today()->addYears(2))) {
            return 'success';
        }

        return 'primary';
    }
}
