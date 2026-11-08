<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_visit_id',
        'service_id',
        'requested_by',
        'performed_by',
        'bill_id',
        'walkin_id',
        'clinical_diagnoses',
        'requested_at',
        'completed_at',
        'status',
        'payment_status',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function patientVisit()
    {
        return $this->belongsTo(PatientVisit::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function requesteredBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function walkin()
    {
        return $this->belongsTo(WalkinPatient::class);
    }
}
