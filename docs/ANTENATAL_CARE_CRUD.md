# Antenatal Care CRUD Implementation

## Overview
Complete CRUD (Create, Read, Update, Delete) implementation for antenatal care management with role-based access control and gender/age-based filtering.

## Features Implemented

### ✅ Gender & Age Filtering
- **Female Only**: Antenatal care features only appear for female patients
- **Reproductive Age**: Only patients aged 13-55 years are eligible
- **Dynamic Filtering**: Automatically calculates and filters based on DOB stored in `patient_demographics`

### ✅ Role-Based Access Control
- **Midwife Role**: Full CRUD access to antenatal care records
- **Administrator Role**: Full CRUD access for system management
- **Permission Checks**: All endpoints protected with role middleware and manual authorization checks
- **Audit Logging**: All operations logged for compliance and tracking

### ✅ Complete CRUD Operations

#### Create (Create View)
- Comprehensive form with auto-calculated fields
- **Pregnancy Dating**: Auto-calculates gestational weeks and EDD from LMP
- **Patient Validation**: Checks female gender and reproductive age before allowing record creation
- **Validation Rules**: 
  - Gestational weeks: 1-42
  - Number of fetuses: 1-8
  - Weight: 30-250 kg
  - Height: 100-250 cm
- **Sections**:
  - Pregnancy details
  - Vital signs (BP, weight, height)
  - Physical examination
  - Investigations
  - Risk assessment
  - Management & counseling
  - Clinical notes

#### Read (Show & Patient Records Views)
- **Individual Record View**: Full display of a single antenatal care record with all details formatted
- **Patient Records View**: Timeline view of all antenatal care records for a patient
  - Sortable history table with key metrics
  - Visual timeline showing progression
  - Quick access to edit/delete actions

#### Update (Edit View)
- Complete form with all antenatal care fields pre-populated
- Same validation as create
- Auto-calculation features (LMP → EDD, gestational weeks)
- Activity logging on updates

#### Delete
- Soft delete implementation
- Confirmation required before deletion
- Audit trail maintained

## File Structure

### Controller
- **Location**: `app/Http/Controllers/AntenatalCareController.php`
- **Methods**:
  - `index()` - List all female patients with antenatal care eligibility
  - `create()` - Show form to create antenatal record (with gender/age checks)
  - `store()` - Save new record to database
  - `show()` - Display single record
  - `edit()` - Show form to edit record
  - `update()` - Save changes to database
  - `destroy()` - Soft delete record
  - `patientRecords()` - Show all records for a patient

### Routes
- **Location**: `routes/midwife.php`
- **Route Group**: Protected by `middleware(['auth', 'verified', 'role:midwife,administrator'])`
- **Prefix**: `/midwife/antenatal-care`
- **Named Routes**:
  - `midwife.antenatal.index` - List patients
  - `midwife.antenatal.create` - Create form
  - `midwife.antenatal.store` - Store operation
  - `midwife.antenatal.show` - View record
  - `midwife.antenatal.edit` - Edit form
  - `midwife.antenatal.update` - Update operation
  - `midwife.antenatal.destroy` - Delete operation
  - `midwife.antenatal.patient-records` - Patient history
- **Route File Registration**: Added to `routes/web.php`

### Views
All views inherit from `layouts.app` and use Bootstrap 5 styling

#### 1. **Index View** (`resources/views/midwife/antenatal/index.blade.php`)
- Lists all female patients aged 13-55
- Shows:
  - Hospital number, name, age, contact
  - Number of antenatal records per patient
  - Last record date and status
  - Quick action buttons
- Status Badge Colors:
  - 🟢 Normal (Green)
  - 🟡 Complicated (Yellow)
  - 🔴 High Risk (Red)

#### 2. **Create View** (`resources/views/midwife/antenatal/create.blade.php`)
- Comprehensive form with multiple sections
- **Features**:
  - Auto-calculation of gestational weeks from LMP
  - Auto-calculation of EDD (Expected Delivery Date)
  - Dynamic section headers with icons
  - Sticky right sidebar with quick reference guide
  - Bootstrap 5 form validation
- **Sections**:
  - Patient information (read-only)
  - Pregnancy details
  - Vital signs
  - Physical examination
  - Investigations
  - Risk assessment
  - Management & counseling
  - Clinical notes

#### 3. **Show View** (`resources/views/midwife/antenatal/show.blade.php`)
- Read-only display of complete antenatal care record
- Organized by sections matching creation form
- **Features**:
  - Color-coded status badges
  - BMI calculation display
  - Overdue pregnancy alerts
  - Record metadata (created date, last updated, recorded by)
  - Edit/Delete action buttons (conditionally shown)

#### 4. **Edit View** (`resources/views/midwife/antenatal/edit.blade.php`)
- Pre-populated edit form
- Same structure as create view
- **Features**:
  - All fields populated with current values
  - Auto-calculation functions enabled
  - Edit mode indicator in sidebar
  - Record metadata display

#### 5. **Patient Records View** (`resources/views/midwife/antenatal/patient-records.blade.php`)
- All antenatal records for a specific patient
- **Displays**:
  - Patient summary card
  - Sortable history table with key metrics:
    - Date, gestational weeks, BP, weight, FHR, status, recorded by
  - Complete timeline view of all records
  - Alert badges for complications and risk factors
  - Empty state if no records

## Database Integration

### Model Used
- **AntenatalCare Model**: `app/Models/AntenatalCare.php`
- **Relationships**: 
  - `patient()` - Belongs to Patient
  - `visit()` - Belongs to PatientVisit
  - `recordedBy()` - Belongs to User

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
- antenatal_care.create - Create antenatal care record
- antenatal_care.read   - View antenatal care records
- antenatal_care.update - Edit antenatal care record
- antenatal_care.delete - Delete antenatal care record
```

### Role Assignments
- **Midwife Role**: All four antenatal care permissions
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
    ->performedOn($antenatalCare)
    ->withProperties(['action' => 'create'])
    ->log('Antenatal care record created');
```

## Validation Rules

```php
[
    'last_menstrual_period' => 'nullable|date',
    'expected_delivery_date' => 'nullable|date|after:last_menstrual_period',
    'gestational_weeks' => 'nullable|integer|min:1|max:42',
    'number_of_fetuses' => 'nullable|integer|min:1|max:8',
    'blood_pressure' => 'nullable|string|max:20',
    'weight' => 'nullable|numeric|min:30|max:250',
    'height' => 'nullable|numeric|min:100|max:250',
    'status' => 'in:normal,complicated,high_risk',
]
```

## Smart Features

### 1. Auto-Calculation
JavaScript functions automatically calculate:
- **Gestational Weeks**: Days since LMP ÷ 7
- **Expected Delivery Date**: LMP + 280 days (40 weeks)

### 2. Status Tracking
- **Normal**: Routine pregnancy, no concerns
- **Complicated**: Identified complications requiring monitoring
- **High Risk**: Significant risk factors requiring frequent monitoring

### 3. Risk Assessment
Tracks:
- Risk factors identified
- Complications encountered
- Management plans implemented
- Counseling provided

### 4. Patient-Centered Views
- Individual record view for detailed examination
- Timeline view for progression tracking
- Summary cards for quick overview

## Usage Examples

### Create Antenatal Record
```
GET  /midwife/antenatal-care/patient/{id}/create
POST /midwife/antenatal-care/patient/{id}/store
```

### View Patient's Records
```
GET /midwife/antenatal-care/patient/{id}/records
```

### View Single Record
```
GET /midwife/antenatal-care/{antenatalCare}/show
```

### Edit Record
```
GET /midwife/antenatal-care/{antenatalCare}/edit
PUT /midwife/antenatal-care/{antenatalCare}/update
```

### Delete Record
```
DELETE /midwife/antenatal-care/{antenatalCare}/delete
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
3. Navigate to `/midwife/antenatal-care/`
4. Verify patient appears in the list
5. Create a new antenatal care record
6. View, edit, and test delete operations

### Test Filtering
- Try accessing with a male patient (should not appear in list)
- Try accessing with patient aged < 13 or > 55 (should not appear in list)
- Try accessing without midwife/admin role (should get error)

## Future Enhancements

- [ ] Add lab integration for test results
- [ ] Add risk scoring algorithm
- [ ] Send alerts for overdue records
- [ ] PDF export for records
- [ ] Generate reports (antenatal coverage, high-risk pregnancies)
- [ ] Mobile app integration
- [ ] SMS reminders for follow-up visits
- [ ] Integrate with ultrasound images/videos
- [ ] Add referral tracking
- [ ] Integration with labour management system

## Files Modified/Created

### Created Files
- `app/Http/Controllers/AntenatalCareController.php`
- `routes/midwife.php`
- `resources/views/midwife/antenatal/index.blade.php`
- `resources/views/midwife/antenatal/create.blade.php`
- `resources/views/midwife/antenatal/show.blade.php`
- `resources/views/midwife/antenatal/edit.blade.php`
- `resources/views/midwife/antenatal/patient-records.blade.php`

### Modified Files
- `routes/web.php` - Added midwife route file requirement
- `database/seeders/PermissionSeeder.php` - Added antenatal care permissions

### Existing Models Used
- `app/Models/AntenatalCare.php` (already created with migrations)
- `app/Models/Patient.php` (already has relationships)
- `app/Models/User.php` (role management)

---

**Implementation Status**: ✅ Complete and Ready for Use
**Last Updated**: April 3, 2026
