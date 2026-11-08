<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'specialist_token',
        'bill_id',
        'patient_id',
        'payment_id',
        'amount',
        'payment_method_id',
        'insurance_provider',
        'reference_number',
        'status',
        'notes',
        'paid_by',
        'payment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    /**
     * Get the bill associated with the payment
     */
    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Get the payment method associated with the payment
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
    /**
     * Get the patient associated with the payment
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function syncRelations(): array
    {
        return [
            'bill' => 'bill_sync_uuid',
            'patient' => 'patient_sync_uuid',
            'recordedBy' => 'user_sync_uuid',
        ];
    }

    public function syncDependencies(): array
    {
        return [
            'bill_sync_uuid' => Bill::class,
            'patient_sync_uuid' => Patient::class,
            'user_sync_uuid' => User::class,
        ];
    }

    /**
     * Get the user who recorded the payment
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Generate unique Payment ID
     */
    public static function generatePaymentID()
    {
        $year = substr(date('Y'),2,2);
        $month = date('m');
        $lastPayment = self::where('payment_id', 'like', "PY{$year}{$month}%")
            ->orderBy('payment_id', 'desc')
            ->first();
        
        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_id, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return "PY{$year}{$month}{$newNumber}";
    }

    /**
     * Scope to get completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get insurance payments
     */
    public function scopeInsurance($query)
    {
        return $query->whereIn('payment_method', ['NHIS', 'Private Insurance']);
    }

    /**
     * Scope to get cash payments
     */
    public function scopeCash($query)
    {
        return $query->where('payment_method', 'Cash');
    }
}
