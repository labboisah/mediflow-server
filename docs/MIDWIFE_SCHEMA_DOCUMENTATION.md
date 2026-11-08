# Midwife Database Schema & Models Implementation

## Overview
Complete database schema and Eloquent models have been implemented for midwife-related activities including antenatal care, labour, delivery, newborn care, and postnatal follow-up.

## Database Migrations Created

### 1. **Antenatal Cares Table** (`2026_04_03_130000_create_antenatal_cares_table.php`)
Tracks pregnancy monitoring and antenatal care visits.

**Key Fields:**
- Pregnancy details (LMP, EDD, gestational weeks, fetus count)
- Vital signs (BP, weight, height)
- Clinical findings (fundal height, FHR, fetal movement)
- Investigations (urinalysis, blood tests, ultrasound)
- Risk factors and complications
- Management and counseling records

**Relationships:**
- `patient_id` → Patient
- `patient_visit_id` → PatientVisit
- `recorded_by` → User (midwife)

---

### 2. **Labour Table** (`2026_04_03_130100_create_labours_table.php`)
Records onset and progression of labour.

**Key Fields:**
- Labour onset time and mode (spontaneous/induced)
- Gestational weeks and labour type
- Pre-labour assessment (cervical state, show, ROM, liquor)
- Maternal vitals at admission
- Labour stage tracking (first, second, third stage timing)
- Fetal monitoring details
- Complications during labour

**Relationships:**
- `patient_id` → Patient
- `admission_id` → Admission
- `recorded_by` → User (midwife)
- `hasMany` → LabourProgress

---

### 3. **Labour Progress Table** (`2026_04_03_130200_create_labour_progress_table.php`)
Detailed tracking of labour progression at intervals.

**Key Fields:**
- Contraction assessment (frequency, duration, intensity)
- Cervical findings (dilation 0-10cm, effacement, consistency, position)
- Fetal station and position
- Maternal and fetal vital parameters
- Pain relief and coping mechanisms
- Interventions and medications

**Relationships:**
- `labour_id` → Labour
- `recorded_by` → User (midwife)

---

### 4. **Delivery Table** (`2026_04_03_130300_create_deliveries_table.php`)
Records actual delivery event and outcomes.

**Key Fields:**
- Delivery date/time and type (vaginal, assisted, caesarean)
- Assistance details (vacuum/forceps, indication)
- Caesarean details (type, indication)
- Perineal trauma assessment and repair
- Third stage details (placenta delivery method, examination)
- Maternal condition post-delivery
- Complications and management
- Number of babies delivered

**Relationships:**
- `labour_id` → Labour
- `patient_id` → Patient
- `delivered_by` → User (midwife/doctor)
- `assisted_by` → User (assistant)
- `hasMany` → Newborn

---

### 5. **Newborn Table** (`2026_04_03_130400_create_newborns_table.php`)
Records newborn data and initial assessment.

**Key Fields:**
- Sex, birth order, registration number
- Birth measurements (weight, length, head circumference)
- Presentation and delivery notes
- APGAR scores (1 min, 5 min, 10 min) with component scoring
- General condition and physical examination
- Birth defects and meconium aspiration
- Breastfeeding initiation and problems
- Early newborn care (Vitamin K, eye prophylaxis, immunizations)
- Screening tests
- Status (alive, stillborn, early neonatal death)

**Relationships:**
- `delivery_id` → Delivery
- `patient_id` → Patient (mother)
- `recorded_by` → User (midwife)
- `hasMany` → NewbornExamination
- `hasMany` → ChildFollowUp

---

### 6. **Newborn Examinations Table** (`2026_04_03_130500_create_newborn_examinations_table.php`)
Detailed physical examinations of newborn at various time points.

**Key Fields:**
- Examination timing (hours after birth)
- Vital signs and anthropometry
- Systematic examination (general, cardiovascular, respiratory, abdominal, urogenital, neurological, musculoskeletal)
- Jaundice assessment and management
- Feeding assessment and tolerance
- Abnormal findings and congenital anomalies
- Follow-up scheduling

**Exam Status:** normal, abnormal, needs_follow_up, referral_needed

**Relationships:**
- `newborn_id` → Newborn
- `recorded_by` → User (midwife/pediatrician)

---

### 7. **Postnatal Examinations Table** (`2026_04_03_130600_create_postnatal_examinations_table.php`)
Comprehensive maternal assessment after delivery.

**Key Fields:**
- Examination timing (0-2h, 6-12h, 24h, 48h, day 4-6, week 1, 2, 6)
- Vital signs and general condition
- Uterine assessment (size, consistency, tenderness, fundal height)
- Lochia assessment (type, amount, odour, clots)
- Perineal and vaginal assessment
- Breast assessment and breastfeeding success
- Abdominal and wound assessment (if C-section)
- Lower limbs assessment (oedema, DVT signs)
- Psychological status (mood, bonding, depression screening)
- Maternal recovery tracking (sleep, pain, activity, ability to care)
- Contraception counseling and hygiene teaching
- Danger signs explanation

**Recovery Status:** normal, complicated, needs_referral

**Relationships:**
- `delivery_id` → Delivery
- `patient_id` → Patient (mother)
- `recorded_by` → User (midwife)

---

### 8. **Child Follow-ups Table** (`2026_04_03_130700_create_child_follow_ups_table.php`)
Comprehensive baby follow-up tracking from birth onwards.

**Key Fields:**
- Follow-up timing (day 3, 7, 10, 14, 6 weeks, 3/6 months, 1 year)
- Location (hospital, clinic, home, other)
- Vital signs and growth parameters (weight, length, HC)
- Weight change assessment and gain evaluation
- Physical examination (general appearance, skin, activity)
- Cord care assessment
- Jaundice screening and management
- Feeding assessment (breast exam, latching, milk transfer, bottle feeding)
- Elimination (urine/stool output and characteristics)
- Neurological and developmental assessment
- Immunization status and planned immunizations
- Newborn and hearing screening results
- Developmental milestones and concerns
- Mother's health and emotional wellbeing
- Counseling provided (infant care, feeding, cord care, hygiene, danger signs)
- Clinical summary and health status
- Referral information if needed

**Health Status:** normal, at_risk, needs_referral, referred

**Follow-up Periods:** day_3, day_7, day_10, day_14, 6weeks, 3months, 6months, year1

**Relationships:**
- `newborn_id` → Newborn
- `patient_id` → Patient (mother)
- `recorded_by` → User (midwife)

---

## Eloquent Models Created

### 1. **AntenatalCare** Model
- **Fillable:** All key fields from schema
- **Relationships:** patient(), visit(), recordedBy()
- **Methods:**
  - `currentPregnancy()` - Scope for active pregnancies
  - `highRisk()` - Scope for high-risk pregnancies
  - `getBmi()` - Calculate BMI
  - `isOverdue()` - Check if delivery date passed

### 2. **Labour** Model
- **Fillable:** All key fields from schema
- **Relationships:** patient(), admission(), recordedBy(), progressRecords(), delivery()
- **Methods:**
  - `calculateStageDuration($stage)` - Duration of labour stage
  - `active()` - Scope for ongoing labours
  - `complicated()` - Scope for complicated labours

### 3. **LabourProgress** Model
- **Fillable:** All key fields from schema
- **Relationships:** labour(), recordedBy()
- **Methods:**
  - `isProgressingNormally()` - Validate labour progression
  - `recent($minutes)` - Scope for recent records

### 4. **Delivery** Model
- **Fillable:** All key fields from schema
- **Relationships:** labour(), patient(), deliveredBy(), assistedBy(), newborns(), postnatalExamination()
- **Methods:**
  - `successful()` - Scope for successful deliveries
  - `complicated()` - Scope for complicated deliveries
  - `vaginal()` - Scope for vaginal deliveries
  - `caesarean()` - Scope for caesarean deliveries
  - `getThirdStageDuration()` - Duration of third stage

### 5. **Newborn** Model
- **Fillable:** All key fields from schema
- **Relationships:** delivery(), mother(), recordedBy(), examinations(), followUps()
- **Methods:**
  - `latestExamination()` - Get most recent exam
  - `hasApgarDistress()` - Check for APGAR distress
  - `alive()` - Scope for live newborns
  - `stillborn()` - Scope for stillborn
  - `male()`, `female()` - Gender scopes

### 6. **NewbornExamination** Model
- **Fillable:** All key fields from schema
- **Relationships:** newborn(), recordedBy()
- **Methods:**
  - `abnormal()` - Scope for abnormal findings
  - `needsReferral()` - Scope for referral cases
  - `getWeightPercentile()` - Growth chart assessment

### 7. **PostnatalExamination** Model
- **Fillable:** All key fields from schema
- **Relationships:** delivery(), patient(), recordedBy()
- **Methods:**
  - `complicated()` - Scope for complicated recoveries
  - `needsReferral()` - Scope for referral cases
  - `hasPPDRisks()` - Detect postpartum depression risks
  - `hasInfectionRisks()` - Detect infection signs

### 8. **ChildFollowUp** Model
- **Fillable:** All key fields from schema
- **Relationships:** newborn(), mother(), recordedBy()
- **Methods:**
  - `withConcerns()` - Scope for concerning cases
  - `needsReferral()` - Scope for referral cases
  - `normal()` - Scope for normal follow-ups
  - `isWeightGainAdequate()` - Weight gain assessment
  - `needsPhototherapy()` - Jaundice severity check
  - `getNextFollowUpPeriod()` - Suggest next follow-up timing

---

## Updated Existing Models

### Patient Model
Added relationships:
- `antenatalCares()` - Get all antenatal records
- `labours()` - Get all labour records
- `deliveries()` - Get all deliveries
- `newborns()` - Get all babies (as mother)
- `postnatalExaminations()` - Get postnatal exams
- `childFollowUps()` - Get child follow-ups
- `latestAntenatalCare()` - Most recent antenatal
- `latestLabour()` - Most recent labour
- `latestDelivery()` - Most recent delivery

### Admission Model
Added relationship:
- `labour()` - Link to labour if admission is for delivery

### PatientVisit Model
Added relationship:
- `antenatalCare()` - Link to antenatal care if applicable

---

## Data Flow & Relationships

```
Patient (Mother)
├── AntenatalCare (via PatientVisit)
│   └── PatientVisit
├── Admission
│   └── Labour
│       ├── LabourProgress (multiple entries)
│       └── Delivery
│           ├── Newborn (1 or more)
│           │   ├── NewbornExamination (multiple)
│           │   └── ChildFollowUp (multiple, ongoing)
│           └── PostnatalExamination
└── Deliveries (alt path)
```

---

## Running the Migrations

Execute in order:

```bash
# Run all migrations
php artisan migrate

# Or run specific seeders if you created them
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PermissionSeeder
```

---

## Usage Examples

### Creating Antenatal Care Record
```php
$visit = $patient->patientVisits()->create([...]);

$antenatal = AntenatalCare::create([
    'patient_id' => $patient->id,
    'patient_visit_id' => $visit->id,
    'recorded_by' => auth()->user()->id,
    'gestational_weeks' => 28,
    'blood_pressure' => '120/80',
    'status' => 'normal'
]);
```

### Recording Labour
```php
$labour = Labour::create([
    'patient_id' => $patient->id,
    'admission_id' => $admission->id,
    'recorded_by' => auth()->user()->id,
    'labour_onset_time' => now(),
    'mode_of_onset' => 'spontaneous'
]);
```

### Tracking Labour Progress
```php
$progress = $labour->progressRecords()->create([
    'recorded_by' => auth()->user()->id,
    'recorded_at' => now(),
    'cervical_dilation' => 5,
    'fetal_heart_rate' => '140'
]);
```

### Recording Delivery
```php
$delivery = Delivery::create([
    'labour_id' => $labour->id,
    'patient_id' => $patient->id,
    'delivered_by' => auth()->user()->id,
    'delivery_date_time' => now(),
    'delivery_type' => 'vaginal',
    'delivery_status' => 'successful'
]);
```

### Creating Newborn Record
```php
$newborn = Newborn::create([
    'delivery_id' => $delivery->id,
    'patient_id' => $patient->id,
    'recorded_by' => auth()->user()->id,
    'sex' => 'male',
    'birth_weight' => 3500,
    'apgar_score_1_minute' => 8,
    'apgar_score_5_minutes' => 9
]);
```

### Newborn Examination
```php
$exam = $newborn->examinations()->create([
    'recorded_by' => auth()->user()->id,
    'examination_date_time' => now(),
    'hours_after_birth' => 2,
    'exam_status' => 'normal'
]);
```

### Postnatal Examination
```php
$postnatal = PostnatalExamination::create([
    'delivery_id' => $delivery->id,
    'patient_id' => $patient->id,
    'recorded_by' => auth()->user()->id,
    'examination_date_time' => now(),
    'hours_post_delivery' => 6,
    'examination_time' => '6-12h'
]);
```

### Child Follow-up
```php
$followUp = $newborn->followUps()->create([
    'patient_id' => $patient->id,
    'recorded_by' => auth()->user()->id,
    'follow_up_date_time' => now(),
    'days_of_life' => 3,
    'follow_up_period' => 'day_3',
    'health_status' => 'normal'
]);
```

---

## Midwife Role Permissions
The midwife role has been configured with permissions for:
- Patient viewing
- Visit creation and viewing
- Patient history viewing
- Prescription management
- Drug chart management
- Admission/discharge management
- Observations and nursing notes

---

## Next Steps

1. **Create Controllers** for CRUD operations on each midwife activity
2. **Create Views/API Endpoints** for data entry and viewing
3. **Create Seeders** for test data if needed
4. **Add Validations** in Request classes
5. **Implement Observers** for audit logging on midwife activities
6. **Create Reports** for antenatal coverage, delivery outcomes, neonatal mortality, etc.
7. **Add Notifications** for high-risk pregnancies, complications, missed follow-ups

---

## Schema Files Location

All migration files are in: `database/migrations/`
- `2026_04_03_130000_create_antenatal_cares_table.php`
- `2026_04_03_130100_create_labours_table.php`
- `2026_04_03_130200_create_labour_progress_table.php`
- `2026_04_03_130300_create_deliveries_table.php`
- `2026_04_03_130400_create_newborns_table.php`
- `2026_04_03_130500_create_newborn_examinations_table.php`
- `2026_04_03_130600_create_postnatal_examinations_table.php`
- `2026_04_03_130700_create_child_follow_ups_table.php`

All model files are in: `app/Models/`
- `AntenatalCare.php`
- `Labour.php`
- `LabourProgress.php`
- `Delivery.php`
- `Newborn.php`
- `NewbornExamination.php`
- `PostnatalExamination.php`
- `ChildFollowUp.php`

