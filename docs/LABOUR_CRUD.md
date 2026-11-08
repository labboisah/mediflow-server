# Labour Management CRUD Implementation

## Overview
Complete CRUD (Create, Read, Update, Delete) implementation for labour management with role-based access control and gender/age-based filtering.

## Features Implemented

### ✅ Gender & Age Filtering
- **Female Only**: Labour management features only appear for female patients
- **Reproductive Age**: Only patients aged 13-55 years are eligible
- **Dynamic Filtering**: Automatically calculates and filters based on DOB stored in `patient_demographics`

### ✅ Role-Based Access Control
- **Midwife Role**: Full CRUD access to labour records
- **Administrator Role**: Full CRUD access for system management
- **Permission Checks**: All endpoints protected with role middleware and manual authorization checks
- **Audit Logging**: All operations logged for compliance and tracking

### ✅ Complete CRUD Operations

#### Create (Create View)
- Comprehensive form with labour-specific fields
- **Labour Tracking**: Records labour admission, type, and progression through stages
- **Patient Validation**: Checks female gender and reproductive age before allowing record creation
- **Validation Rules**: 
  - Cervical dilation: 0-10 cm
  - Cervical effacement: 0-100%
  - Fetal position: cephalic, breech, oblique, transverse
  - Vital signs ranges (BP, HR, temperature, RR)
- **Sections**:
  - Labour admission details
  - Cervical findings
  - Uterine contractions
  - Fetal status and monitoring
  - Maternal vital signs
  - Mode of delivery
  - Perineal and complications tracking
  - Management and treatment documentation
  - Clinical notes

#### Read (Show & Patient Records Views)
- **Individual Record View**: Full display of a single labour record with all details formatted
- **Patient Records View**: Timeline view of all labour records for a patient
  - Sortable history table with key metrics
  - Visual timeline showing progression
  - Quick access to edit/delete actions
  - Complications and complications alerts

#### Update (Edit View)
- Complete form with all labour fields pre-populated
- Same validation as create
- Activity logging on updates
- Edit mode indicator with record metadata

#### Delete
- Soft delete implementation
- Confirmation required before deletion
- Audit trail maintained

## File Structure

### Controller
- **Location**: `app/Http/Controllers/LabourController.php`
- **Methods**:
  - `index()` - List all female patients with labour record eligibility
  - `create()` - Show form to create labour record (with gender/age checks)
  - `store()` - Save new record to database
  - `show()` - Display single record
  - `edit()` - Show form to edit record
  - `update()` - Save changes to database
  - `destroy()` - Soft delete record
  - `patientRecords()` - Show all records for a patient

### Routes
- **Location**: `routes/midwife.php`
- **Route Group**: Protected by `middleware(['auth', 'verified', 'role:midwife,administrator'])`
- **Prefix**: `/midwife/labour`
- **Named Routes**:
  - `midwife.labour.index` - List patients
  - `midwife.labour.create` - Create form
  - `midwife.labour.store` - Store operation
  - `midwife.labour.show` - View record
  - `midwife.labour.edit` - Edit form
  - `midwife.labour.update` - Update operation
  - `midwife.labour.destroy` - Delete operation
  - `midwife.labour.patient-records` - Patient history
- **Route File Registration**: Already included in `routes/web.php`

### Views
All views inherit from `layouts.app` and use Bootstrap 5 styling

#### 1. **Index View** (`resources/views/midwife/labour/index.blade.php`)
- Lists all female patients aged 13-55
- Shows:
  - Hospital number, name, age, contact
  - Number of labour records per patient
  - Last record date and quick action buttons
- Action buttons:
  - Create new labour record
  - View all labour records

#### 2. **Create View** (`resources/views/midwife/labour/create.blade.php`)
- Comprehensive form with multiple sections
- **Features**:
  - Dynamic field validation feedback
  - Clear section organization with icons
  - Sticky right sidebar with quick reference guide
  - Bootstrap 5 form validation
- **Sections**:
  - Patient information (read-only)
  - Labour admission details
  - Cervical findings (dilation, effacement, consistency, position)
  - Uterine contractions (frequency, duration, intensity)
  - Fetal status (position, descent, FHR, meconium staining)
  - Maternal vital signs (BP, HR, temperature, RR)
  - Mode of delivery (vaginal, assisted, caesarean)
  - Perineal care (episiotomy, tear degree)
  - Complications tracking (maternal and fetal)
  - Management and treatment (analgesia, augmentation)
  - Clinical notes

#### 3. **Show View** (`resources/views/midwife/labour/show.blade.php`)
- Read-only display of complete labour record
- Organized by sections matching creation form
- **Features**:
  - Color-coded status badges
  - Record metadata (created date, last updated, recorded by)
  - Comprehensive display of all labour findings
  - Edit/Delete action buttons (conditionally shown)
  - Complications alerts displayed prominently

#### 4. **Edit View** (`resources/views/midwife/labour/edit.blade.php`)
- Pre-populated edit form
- Same structure as create view
- **Features**:
  - All fields populated with current values
  - Edit mode indicator in sidebar
  - Record metadata display
  - Full validation with error feedback

#### 5. **Patient Records View** (`resources/views/midwife/labour/patient-records.blade.php`)
- All labour records for a specific patient
- **Displays**:
  - Patient summary card
  - Sortable history table with key metrics:
    - Date, type, stage, BP, FHR, temperature, mode of delivery
  - Complete timeline view of all records
  - Alert badges for complications
  - Empty state if no records

## Database Integration

### Model Used
- **Labour Model**: `app/Models/Labour.php`
- **Relationships**: 
  - `patient()` - Belongs to Patient
  - `recordedBy()` - Belongs to User

### Labour Fields Captured
- Admission details (date, time, type, stage)
- Cervical findings (dilation, effacement, consistency, position, application)
- Contractions (frequency, duration, intensity)
- Fetal status (position, descent, FHR, movements, meconium)
- Maternal vitals (BP, HR, temperature, RR)
- Delivery mode (vaginal, assisted, caesarean)
- Perineal care (episiotomy, tear degree)
- Complications (maternal and fetal)
- Management (analgesia, augmentation, overall outcome)

### Patient Demographics Access
```php
// Access gender and age from patient demographics
$patient->demographic->gender  // 'Male', 'Female', 'Other'
$patient->demographic->date_of_birth  // Carbon date

// Calculate current age
now()->diffInYears($patient->demographic->date_of_birth)

// Check if female and reproductive age
if ($patient->demographic->gender === 'Female' && ($age >= 13 && $age <= 55))
```

## Permissions Added

### New Permissions
```
Module: Midwifery
- labour.create - Create labour management record
- labour.read   - View labour management records
- labour.update - Edit labour management record
- labour.delete - Delete labour management record
```

### Role Assignments
- **Midwife Role**: All four labour management permissions
- **Administrator Role**: All permissions (globally)

## Authorization Flow

```
Route Access → Middleware ['role:midwife,administrator']
    ↓
Controller Method → hasAnyRole() check
    ↓
Gender Check → demographic->gender === 'Female'
    ↓
Age Check → age >= 13 AND age <= 55
    ↓
Action Execution
```

## Activity Logging

All CRUD operations are logged using Laravel's Activity Log:

```php
activity()
    ->performedOn($labour)
    ->withProperties(['action' => 'create'])
    ->log('Labour record created');
```

## Validation Rules

```php
[
    'date_of_admission' => 'required|date',
    'time_of_admission' => 'nullable|date_format:H:i',
    'type_of_labour' => 'in:spontaneous,induced,augmented',
    'stage_at_admission' => 'in:first,second,third,fourth',
    'cervical_dilation' => 'nullable|integer|min:0|max:10',
    'cervical_effacement' => 'nullable|integer|min:0|max:100',
    'fetal_position' => 'in:cephalic,breech,oblique,transverse',
    'fetal_heart_rate' => 'nullable|integer|min:100|max:160',
    'systolic_bp' => 'nullable|integer|min:60|max:250',
    'diastolic_bp' => 'nullable|integer|min:40|max:150',
    'mode_of_delivery' => 'in:vaginal,assisted_vaginal,caesarean',
]
```

## Labour-Specific Features

### 1. Stage Tracking
- **First Stage**: Cervical dilation from 0-10 cm
- **Second Stage**: Active pushing phase
- **Third Stage**: Placental delivery
- **Fourth Stage**: Immediate postpartum recovery (1-2 hours)

### 2. Cervical Assessment
- Dilation measurement (0-10 cm)
- Effacement percentage (0-100%)
- Consistency (firm, medium, soft)
- Position (posterior, middle, anterior)

### 3. Contraction Monitoring
- Frequency per 10 minutes
- Duration in seconds
- Intensity level (mild, moderate, strong)

### 4. Fetal Monitoring
- Position (cephalic, breech, oblique, transverse)
- Descent station (-5 to +5)
- Heart rate (100-160 bpm)
- Movement and meconium staining assessment

### 5. Delivery Outcomes
- Mode: vaginal, assisted (forceps/vacuum), or caesarean
- Perineal care: episiotomy type and tear classification
- Complications tracking (maternal and fetal)

## Usage Examples

### Create Labour Record
```
GET  /midwife/labour/patient/{id}/create
POST /midwife/labour/patient/{id}/store
```

### View Patient's Labour Records
```
GET /midwife/labour/patient/{id}/records
```

### View Single Record
```
GET /midwife/labour/{labour}/show
```

### Edit Record
```
GET /midwife/labour/{labour}/edit
PUT /midwife/labour/{labour}/update
```

### Delete Record
```
DELETE /midwife/labour/{labour}/delete
```

## Security Considerations

✅ **Role-Based Access**: Only midwives and administrators can access
✅ **Gender Filtering**: Only female patients shown
✅ **Age Range Filtering**: Only 13-55 year old patients eligible
✅ **Soft Deletes**: Records are soft-deleted (not permanently removed)
✅ **Audit Logging**: All operations tracked for compliance
✅ **CSRF Protection**: All forms protected with @csrf tokens
✅ **Authorization Checks**: Manual checks in controller methods
✅ **Validation**: Comprehensive input validation on all fields

## Testing the Feature

### Prerequisites
```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PermissionSeeder
```

### Test Access
1. Create a female patient aged 20-40 in the system
2. Log in as a midwife user
3. Navigate to `/midwife/labour/`
4. Verify patient appears in the list
5. Create a new labour record
6. View, edit, and test delete operations

### Test Filtering
- Try accessing with a male patient (should not appear in list)
- Try accessing with patient aged < 13 or > 55 (should not appear in list)
- Try accessing without midwife/admin role (should get error)

## Related Features

### Complementary Modules (Models & Migrations Ready)
- **Antenatal Care**: Pregnancy monitoring (CRUD implemented)
- **Delivery Management**: Post-labour delivery tracking
- **Newborn Assessment**: APGAR and newborn examination
- **Postnatal Care**: Maternal and infant recovery
- **Child Follow-up**: Infant growth and development

## Future Enhancements

- [ ] Labour progress chart with graphical representation
- [ ] Partograph integration
- [ ] Alert system for prolonged labour
- [ ] Integration with fetal monitoring devices
- [ ] Automated risk stratification
- [ ] SMS notifications for high-risk cases
- [ ] PDF generation for labour summaries
- [ ] Mobile app for field data collection
- [ ] Video/image documentation of delivery
- [ ] Integration with delivery suite scheduling

## Files Modified/Created

### Created Files
- `app/Http/Controllers/LabourController.php`
- `resources/views/midwife/labour/index.blade.php`
- `resources/views/midwife/labour/create.blade.php`
- `resources/views/midwife/labour/show.blade.php`
- `resources/views/midwife/labour/edit.blade.php`
- `resources/views/midwife/labour/patient-records.blade.php`

### Modified Files
- `routes/midwife.php` - Added labour resource routes
- `database/seeders/PermissionSeeder.php` - Added labour permissions and role sync

### Existing Models Used
- `app/Models/Labour.php` (already created with migrations)
- `app/Models/Patient.php` (already has relationships)
- `app/Models/User.php` (role management)

---

**Implementation Status**: ✅ Complete and Ready for Use
**Last Updated**: April 3, 2026
