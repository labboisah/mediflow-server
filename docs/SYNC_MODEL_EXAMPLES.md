# Example: Adding Syncable Trait to Models

Below are example implementations showing how to add the `Syncable` trait to your models.

---

## Example 1: Patient Model

```php
<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use Syncable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'national_id',
        'blood_group',
        'marital_status',
        'occupation',
        'next_of_kin',
        'next_of_kin_phone',
        'emergency_contact',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // Relations
    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    public function antenatalCares(): HasMany
    {
        return $this->hasMany(AntenatalCare::class);
    }

    /**
     * Optional: Override sync payload to exclude sensitive data
     */
    public function getSyncPayload(): array
    {
        $payload = parent::getSyncPayload();
        
        // Remove sensitive fields if needed
        // unset($payload['some_sensitive_field']);
        
        return $payload;
    }
}
```

---

## Example 2: Admission Model

```php
<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admission extends Model
{
    use Syncable;

    protected $fillable = [
        'patient_id',
        'ward_id',
        'bed_id',
        'admission_date',
        'discharge_date',
        'reason_for_admission',
        'diagnosis',
        'status',
        'notes',
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    /**
     * When syncing, include patient sync_uuid instead of numeric patient_id
     */
    public function getSyncPayload(): array
    {
        $payload = parent::getSyncPayload();
        
        // Replace numeric IDs with sync_uuids for cross-system references
        if ($this->patient) {
            $payload['patient_sync_uuid'] = $this->patient->sync_uuid;
            unset($payload['patient_id']);
        }
        
        return $payload;
    }

    /**
     * When applying sync data, restore the reference
     */
    public function applySyncData(array $data): void
    {
        // If we receive patient_sync_uuid, resolve it to patient_id
        if (isset($data['patient_sync_uuid'])) {
            $patient = Patient::where('sync_uuid', $data['patient_sync_uuid'])->first();
            if ($patient) {
                $data['patient_id'] = $patient->id;
            }
            unset($data['patient_sync_uuid']);
        }
        
        parent::applySyncData($data);
    }
}
```

---

## Example 3: AntenatalCare Model

```php
<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntenatalCare extends Model
{
    use Syncable;

    protected $table = 'antenatal_cares';

    protected $fillable = [
        'patient_id',
        'visit_date',
        'gestational_age_weeks',
        'weight',
        'blood_pressure',
        'fetal_heart_rate',
        'fundal_height',
        'urine_results',
        'blood_results',
        'remarks',
        'midwife_id',
    ];

    protected $casts = [
        'visit_date' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function midwife(): BelongsTo
    {
        return $this->belongsTo(User::class, 'midwife_id');
    }

    public function getSyncPayload(): array
    {
        $payload = parent::getSyncPayload();
        
        // Include patient sync_uuid for reference
        if ($this->patient) {
            $payload['patient_sync_uuid'] = $this->patient->sync_uuid;
        }
        
        // Remove local IDs
        unset($payload['patient_id']);
        unset($payload['midwife_id']);
        
        return $payload;
    }

    public function applySyncData(array $data): void
    {
        // Resolve patient reference
        if (isset($data['patient_sync_uuid'])) {
            $patient = Patient::where('sync_uuid', $data['patient_sync_uuid'])->first();
            if ($patient) {
                $data['patient_id'] = $patient->id;
            }
            unset($data['patient_sync_uuid']);
        }
        
        parent::applySyncData($data);
    }
}
```

---

## Example 4: Labour Model

```php
<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Labour extends Model
{
    use Syncable;

    protected $fillable = [
        'patient_id',
        'onset_date',
        'admission_date',
        'completion_date',
        'duration_hours',
        'membrane_rupture_time',
        'labour_status',
        'type_of_delivery',
        'complications',
        'midwife_id',
    ];

    protected $casts = [
        'onset_date' => 'datetime',
        'admission_date' => 'datetime',
        'completion_date' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function midwife(): BelongsTo
    {
        return $this->belongsTo(User::class, 'midwife_id');
    }

    public function labourProgresses(): HasMany
    {
        return $this->hasMany(LabourProgress::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function getSyncPayload(): array
    {
        $payload = parent::getSyncPayload();
        
        if ($this->patient) {
            $payload['patient_sync_uuid'] = $this->patient->sync_uuid;
        }
        
        unset($payload['patient_id']);
        unset($payload['midwife_id']);
        
        return $payload;
    }

    public function applySyncData(array $data): void
    {
        if (isset($data['patient_sync_uuid'])) {
            $patient = Patient::where('sync_uuid', $data['patient_sync_uuid'])->first();
            if ($patient) {
                $data['patient_id'] = $patient->id;
            }
            unset($data['patient_sync_uuid']);
        }
        
        parent::applySyncData($data);
    }
}
```

---

## Implementation Pattern

Notice the pattern used in these examples:

### For Outbound Sync (local → remote)
```php
public function getSyncPayload(): array
{
    $payload = parent::getSyncPayload();
    
    // Convert local IDs to sync_uuids for references
    if ($this->relation) {
        $payload['relation_sync_uuid'] = $this->relation->sync_uuid;
        unset($payload['relation_id']);
    }
    
    return $payload;
}
```

### For Inbound Sync (remote → local)
```php
public function applySyncData(array $data): void
{
    // Resolve sync_uuids back to local IDs
    if (isset($data['relation_sync_uuid'])) {
        $relation = RelatedModel::where('sync_uuid', $data['relation_sync_uuid'])->first();
        if ($relation) {
            $data['relation_id'] = $relation->id;
        }
        unset($data['relation_sync_uuid']);
    }
    
    parent::applySyncData($data);
}
```

This ensures relationships work correctly on both servers even though the local numeric IDs are different.

---

## What to Do Now

1. Copy the `use Syncable;` trait into your models
2. Override `getSyncPayload()` if your model has relationships
3. Override `applySyncData()` if you need custom handling
4. Run migrations: `php artisan migrate`
5. Initialize UUIDs: `php artisan sync:initialize`
6. Test with: `php artisan sync:status`

---

## Quick Checklist

- [ ] Add `use Syncable;` to Patient model
- [ ] Add `use Syncable;` to Admission model
- [ ] Add `use Syncable;` to AntenatalCare model
- [ ] Add `use Syncable;` to Labour model
- [ ] Add `use Syncable;` to Delivery model
- [ ] Add `use Syncable;` to InvestigationRequest model
- [ ] Add `use Syncable;` to Prescription model
- [ ] Add custom getSyncPayload() for models with relationships
- [ ] Run `php artisan migrate`
- [ ] Run `php artisan sync:initialize`
- [ ] Configure .env on both servers
- [ ] Start queue workers
