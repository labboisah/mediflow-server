<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Delivery extends Model
{
    use SoftDeletes, Syncable;

    protected $table = 'deliveries';

    protected $fillable = [
        'labour_id',
        'patient_id',
        'patient_visit_id',
        'delivered_by',
        'assisted_by',
        'delivery_date_time',
        'delivery_type',
        'reason_for_delivery_type',
        'assisted_with',
        'indication_for_assistance',
        'caesarean_type',
        'indication_for_caesarean',
        'perineal_trauma',
        'episiotomy',
        'perineal_repair',
        'placenta_delivery_method',
        'placenta_delivered_at',
        'placental_examination',
        'estimated_blood_loss',
        'blood_loss_assessment',
        'uterine_tone',
        'per_vaginal_bleeding',
        'blood_pressure',
        'pulse_rate',
        'general_condition',
        'complications',
        'management_of_complications',
        'number_of_babies',
        'delivery_summary',
        'delivery_status',
    ];

    protected $casts = [
        'delivery_date_time' => 'datetime',
        'placenta_delivered_at' => 'datetime',
    ];

    /**
     * Get the labour that ended in this delivery
     */
    public function labour()
    {
        return $this->belongsTo(Labour::class);
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    /**
     * Get the patient who delivered
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the midwife/doctor who delivered the baby
     */
    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    /**
     * Get the assistant during delivery
     */
    public function assistedBy()
    {
        return $this->belongsTo(User::class, 'assisted_by');
    }

    /**
     * Get all newborns from this delivery
     */
    public function newborns()
    {
        return $this->hasMany(Newborn::class);
    }

    /**
     * Get the postnatal examination
     */
    public function postnatalExaminations()
    {
        return $this->hasMany(PostnatalExamination::class);
    }

    /**
     * Scope to get successful deliveries
     */
    public function scopeSuccessful($query)
    {
        return $query->where('delivery_status', 'successful');
    }

    /**
     * Scope to get complicated deliveries
     */
    public function scopeComplicated($query)
    {
        return $query->where('delivery_status', 'complicated');
    }

    /**
     * Scope to get vaginal deliveries
     */
    public function scopeVaginal($query)
    {
        return $query->where('delivery_type', 'vaginal');
    }

    /**
     * Scope to get caesarean deliveries
     */
    public function scopeCaesarean($query)
    {
        return $query->where('delivery_type', 'caesarean');
    }

    /**
     * Calculate third stage duration
     */
    public function getThirdStageDuration()
    {
        if ($this->labour?->third_stage_started_at && $this->placenta_delivered_at) {
            return $this->labour->third_stage_started_at->diffInMinutes($this->placenta_delivered_at);
        }
        return null;
    }
}
