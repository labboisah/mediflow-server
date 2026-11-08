# Records Officer Module - Implementation Guide

## Overview

A comprehensive Records Officer account system has been successfully implemented in the Hospital Record Management System (HRMFYH). This module provides complete functionality for managing patient records, appointments, visits, admissions, discharges, and referrals.

## What Was Implemented

### 1. **Database Models & Migrations**

#### Models Created:
- **Patient** - Main patient record with hospital number generation
- **PatientDemographic** - Patient personal and demographic information
- **PatientVisit** - Patient visit history and records
- **Appointment** - Appointment scheduling and management
- **NextOfKin** - Next of kin contact information
- **PatientAdmission** - Patient hospital admissions
- **PatientReferral** - Inter-departmental patient referrals

#### Database Tables:
```
- patients (hospital_number, payment_id, registration_date, is_walkIn)
- patient_demographics (name, gender, DOB, age, contact info, etc.)
- patient_visits (visit records, reason, clinical notes)
- appointments (scheduled appointments with date/time/status)
- next_of_kin (emergency contact information)
- patient_admissions (admission/discharge records)
- patient_referrals (referral tracking)
```

### 2. **Authorization & Permissions**

#### New Permissions Added:
- `manage_patient_visits` - Record and maintain patient visit histories
- `manage_patient_admissions` - Document patient admissions
- `manage_patient_discharges` - Document patient discharges
- `manage_patient_referrals` - Document patient referrals
- `search_patients` - Search patient records by various criteria

#### Record Officer Full Permissions:
- `view_records` - View patient medical records
- `create_records` - Create patient records
- `edit_records` - Edit patient records
- `export_records` - Export patient records
- `view_patients` - View patient list
- `create_patients` - Register new patients
- `edit_patients` - Edit patient information
- `view_appointments` - View appointment schedule
- `create_appointments` - Schedule appointments
- `edit_appointments` - Modify appointments
- `cancel_appointments` - Cancel appointments
- `manage_patient_visits` - Record visits
- `manage_patient_admissions` - Record admissions
- `manage_patient_discharges` - Record discharges
- `manage_patient_referrals` - Record referrals
- `search_patients` - Search patients

### 3. **Record Officer Controller**

#### RecordOfficerController Methods:

**Dashboard**
- `dashboard()` - Show dashboard with statistics and quick stats

**Patient Management**
- `registerForm()` - Show patient registration form
- `register()` - Register new patient
- `listPatients()` - List all patients (paginated)
- `showPatient()` - Show patient details
- `editForm()` - Show patient edit form
- `update()` - Update patient information
- `search()` - Search patients by hospital number, payment ID, or phone

**Appointments**
- `listAppointments()` - List all appointments
- `appointmentForm()` - Show appointment scheduling form
- `scheduleAppointment()` - Schedule new appointment
- `cancelAppointment()` - Cancel appointment

**Patient Visits**
- `visitForm()` - Show visit recording form
- `storeVisit()` - Record patient visit

**Admissions**
- `admissionForm()` - Show admission recording form
- `recordAdmission()` - Record patient admission

**Discharges**
- `dischargeForm()` - Show discharge recording form
- `recordDischarge()` - Record patient discharge

**Referrals**
- `referralForm()` - Show referral creation form
- `recordReferral()` - Create patient referral

**Export**
- `exportRecord()` - Export patient records

### 4. **Routes**

All routes are under the `/record-officer` prefix and protected by:
- Authentication middleware (`auth`)
- Verification middleware (`verified`)
- Role middleware (`role:record_officer`)

#### Route Structure:
```
/record-officer/                                    - Dashboard
/record-officer/patients/list                      - Patient list
/record-officer/patients/search                    - Patient search
/record-officer/patients/register                  - Patient registration
/record-officer/patients/{patient}                 - Patient details
/record-officer/patients/{patient}/edit            - Edit patient
/record-officer/appointments                       - Appointments list
/record-officer/patients/{patient}/appointments    - Schedule appointment
/record-officer/patients/{patient}/visits          - Record visit
/record-officer/patients/{patient}/admissions      - Record admission
/record-officer/patients/{patient}/discharges      - Record discharge
/record-officer/patients/{patient}/referrals       - Create referral
/record-officer/patients/{patient}/export          - Export record
```

### 5. **Views**

#### Blade Templates Created:

**Dashboard**
- `resources/views/record_officer/dashboard.blade.php` - Main dashboard

**Patient Management**
- `resources/views/record_officer/patient/register.blade.php` - Patient registration form
- `resources/views/record_officer/patient/list.blade.php` - Patient list view
- `resources/views/record_officer/patient/show.blade.php` - Patient detailed view
- `resources/views/record_officer/patient/edit.blade.php` - Patient edit form
- `resources/views/record_officer/patient/search.blade.php` - Patient search

**Appointments**
- `resources/views/record_officer/appointment/list.blade.php` - Appointments list
- `resources/views/record_officer/appointment/create.blade.php` - Appointment scheduling form

**Patient Visits**
- `resources/views/record_officer/visit/create.blade.php` - Visit recording form

**Admissions & Discharges**
- `resources/views/record_officer/admission/create.blade.php` - Admission form
- `resources/views/record_officer/discharge/create.blade.php` - Discharge form

**Referrals**
- `resources/views/record_officer/referral/create.blade.php` - Referral form

**Menu**
- `resources/views/menu/record_officer.blade.php` - Records Officer portal menu

## Key Features

### 1. **Patient Registration**
- Register new patients with complete demographic information
- Support for walk-in patients
- Automatic hospital number generation (Format: HNYYYYxxxxx)
- Next of Kin information capture
- Duplicate prevention checks

### 2. **Patient Information Management**
- View comprehensive patient profiles
- Edit patient demographic details
- Track next of kin information
- View visit history

### 3. **Appointment Scheduling**
- Schedule appointments for patients
- Set appointment date and time
- Add appointment notes
- Cancel appointments with reason tracking
- View appointment list with filtering

### 4. **Patient Visit Documentation**
- Record patient visits with type (Consultation, Follow-up, Emergency, Walk-in)
- Document reason for visit
- Add clinical notes
- Maintain complete visit history

### 5. **Hospital Admissions**
- Record patient admissions
- Track admission date, department, and bed assignment
- Document reason for admission
- Add clinical notes

### 6. **Hospital Discharges**
- Record patient discharges from admission
- Track discharge date and documentation
- Add discharge instructions and follow-up care notes

### 7. **Patient Referrals**
- Create inter-departmental referrals
- Track referral status (Pending, Accepted, Completed, Rejected)
- Document reasons for referral
- Maintain referral history

### 8. **Patient Search**
- Search by hospital number
- Search by payment ID
- Search by phone number
- Search by patient name

### 9. **Record Export**
- Export patient records (placeholder for PDF/CSV export)

## Roles & Responsibilities

### Record Officer Responsibilities:
✓ Register new patients and walk-in patients
✓ Capture and update patient demographic details  
✓ Automatically generate hospital numbers
✓ Maintain accurate and complete patient visit histories
✓ Schedule patient appointments
✓ Search and retrieve patient records
✓ Prevent duplicate patient records
✓ Support admissions, discharges, and referrals documentation

## Hospital Number Generation

The system automatically generates unique hospital numbers using the format:
```
HN[YYYY][00001]
Example: HN202600001 (first patient registered in 2026)
```

This is implemented in the `Patient` model's `generateHospitalNumber()` method.

## Usage Guide

### 1. **Access the Records Officer Module**

Navigate to `/record-officer` to access the portal (requires Records Officer role).

### 2. **Register a Patient**

1. Click "Register Patient" from the dashboard
2. Fill in patient information:
   - Personal details (name, gender, DOB, etc.)
   - Contact information (phone, email, address)
   - Next of Kin details
3. Click "Register Patient" - Hospital number is generated automatically

### 3. **View & Search Patients**

- **View All**: Go to "Patient Management" → "View Patient List"
- **Search**: Use the search form to find by:
  - Hospital Number
  - Payment ID
  - Phone Number
  - Patient Name

### 4. **Schedule Appointments**

1. Open patient record
2. Click "Schedule Appointment"
3. Select appointment date and time
4. Add optional notes
5. Save appointment

### 5. **Record Patient Visit**

1. Open patient record
2. Click "Record Visit"
3. Select visit type and date/time
4. Document reason for visit and clinical notes
5. Save visit record

### 6. **Record Admission**

1. Open patient record
2. Click "Record Admission"
3. Enter admission date, reason, department, and bed assignment
4. Save admission record

### 7. **Record Discharge**

1. Open patient record
2. Click "Record Discharge"
3. Enter discharge date and instructions
4. Save discharge record

### 8. **Create Referral**

1. Open patient record
2. Click "Create Referral"
3. Select receiving department
4. Document reason for referral
5. Save referral

## Database Structure

### Patients Table
```sql
CREATE TABLE patients (
  id BIGINT PRIMARY KEY,
  hospital_number VARCHAR(50) UNIQUE,
  payment_id VARCHAR(100) NULLABLE UNIQUE,
  registration_date DATETIME,
  is_walkIn BOOLEAN DEFAULT FALSE,
  timestamps, soft_deletes
);
```

### Patient Demographics Table
```sql
CREATE TABLE patient_demographics (
  id BIGINT PRIMARY KEY,
  patient_id BIGINT FOREIGN KEY,
  first_name VARCHAR(255),
  last_name VARCHAR(255),
  gender ENUM('Male','Female','Other'),
  date_of_birth DATE,
  age INTEGER,
  lga VARCHAR(255),
  occupation VARCHAR(255),
  marital_status ENUM('Single','Married','Divorced','Widowed'),
  address TEXT,
  phone_number VARCHAR(20) UNIQUE,
  email VARCHAR(255) NULLABLE UNIQUE,
  timestamps, soft_deletes
);
```

### Additional Tables
- `patient_visits` - Tracks patient visits
- `appointments` - Manages appointments
- `next_of_kin` - Stores next of kin information
- `patient_admissions` - Tracks hospital admissions
- `patient_referrals` - Manages inter-departmental referrals

## Security Features

1. **Role-Based Access Control**: Only users with the `record_officer` role can access the module
2. **Permission-Based Actions**: Each action is protected by specific permissions
3. **Data Validation**: All inputs are validated server-side
4. **Unique Constraints**: Hospital numbers and phone numbers are unique
5. **Soft Deletes**: Patient records are soft-deleted for audit trail

## Error Handling

- Comprehensive error messages for validation failures
- Duplicate patient prevention checks
- Proper exception handling with user-friendly messages
- Session flash messages for success/error feedback

## Future Enhancements

1. **PDF Export**: Implement actual PDF export for patient records
2. **Bulk Import**: Import patients from CSV
3. **Advanced Reporting**: Generate custom reports
4. **SMS Integration**: Send appointment reminders via SMS
5. **Appointment Confirmations**: Automated appointment confirmations
6. **Patient Portal**: Allow patients to view their own records
7. **Analytics Dashboard**: Enhanced statistics and charts

## Testing the Implementation

To test the Records Officer module:

1. **Create a Records Officer User**:
   ```bash
   php artisan tinker
   >>> $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com', 'password' => bcrypt('password')]);
   >>> $user->assignRole('record_officer');
   ```

2. **Access the Dashboard**: Navigate to `/record-officer`

3. **Register a Test Patient**: Use the registration form to create a patient

4. **Test All Features**: Try each feature from the dashboard

## File Locations

- **Models**: `app/Models/`
- **Controller**: `app/Http/Controllers/RecordOfficerController.php`
- **Routes**: `routes/web.php` (Records Officer section)
- **Views**: `resources/views/record_officer/`
- **Migrations**: `database/migrations/2026_02_11_*`
- **Seeders**: `database/seeders/PermissionSeeder.php`

## Support

For support or issues with the Records Officer module, please contact the development team.

---

**Implementation Date**: February 11, 2026  
**Status**: Complete and Ready for Testing
