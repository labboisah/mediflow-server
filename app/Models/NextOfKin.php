<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NextOfKin extends Model
{
    use SoftDeletes;

    protected $table = 'next_of_kin';

    protected $fillable = [
        'patient_id',
        'name',
        'relationship',
        'contact_address',
        'telephone',
    ];

    /**
     * Get the patient this next of kin belongs to
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
