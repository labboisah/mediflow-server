# Phase 3: Patient And Front Desk Modern UI Migration

## Objective

Move all record officer and front desk patient workflows into `layouts.modern` while preserving registration, register reporting, search, patient profile, edit, visit, appointment, referral, admission, discharge, and export behavior.

## Migration Status

Phase 3 is complete.

Completed areas:

1. Record Officer Dashboard
2. Patient List
3. Patient Registration
4. Patient Register Report
5. Patient Search
6. Patient Show/Profile
7. Patient Edit
8. Patient History / Visit History
9. Visit Creation
10. Appointments
11. Referrals
12. Admissions
13. Discharges
14. Patient Export Route
15. Legacy Record Register Compatibility View

## Completed In This Phase

### Record Officer Dashboard

Files updated:

- `app/Http/Controllers/RecordOfficerController.php`
- `resources/views/record/dashboard.blade.php`

Changes:

- Switched from `layouts.app` to `layouts.modern`.
- Rebuilt dashboard statistics, quick actions, recent registrations, and upcoming appointments with `x-ui.*` components.
- Dashboard search now routes to `record.patients.search`.
- Added dashboard access to the appointment list.

### Patient List And Registration

Files updated:

- `app/Livewire/Patient/PatientManagement.php`
- `app/Livewire/Patient/PatientRegistration.php`
- `resources/views/record/patient/list.blade.php`
- `resources/views/record/patient/register.blade.php`
- `resources/views/components/patient/patient-management.blade.php`
- `resources/views/components/patient/patient-registration.blade.php`

Changes:

- Switched patient list and active registration to `layouts.modern`.
- Rebuilt list filters, sorting, pagination, registration form sections, flags, and billing preview with `x-ui.*` components.
- Converted the old standalone `record.patient.register` view into a modern compatibility page that points to the active Livewire registration route.

### Patient Register Report

Files updated:

- `resources/views/record/patient/register-report.blade.php`

Changes:

- Switched from Bootstrap layout to `layouts.modern`.
- Rebuilt summary cards, filters, export actions, data table, pagination, and empty state with `x-ui.*` components.
- Preserved CSV/PDF export links and all existing filters.

### Patient Search, Profile, And Edit

Files updated:

- `app/Http/Controllers/RecordOfficerController.php`
- `resources/views/record/patient/search.blade.php`
- `resources/views/record/patient/show.blade.php`
- `resources/views/record/patient/edit.blade.php`

Changes:

- Switched record search, profile, and edit screens to `layouts.modern`.
- Fixed stale `record_officer.*` route references.
- Kept the generic clinical `patient.search` and shared clinical patient profile partials untouched for Phase 4.
- Moved state/LGA lookup out of Blade into the controller.
- Added dependent LGA refresh using the existing Ajax endpoint.

### Visits

Files updated:

- `app/Http/Controllers/RecordOfficerController.php`
- `resources/views/record/visit/create.blade.php`
- `resources/views/record/patient/show.blade.php`

Changes:

- Switched visit creation to `layouts.modern`.
- Moved active service lookup out of Blade and into the controller.
- Preserved visit date, visit type, bill generation, service request generation, validation, and redirect behavior.
- Kept visit history visible on the modern record patient profile.

### Appointments

Files updated:

- `routes/web.php`
- `app/Http/Controllers/RecordOfficerController.php`
- `resources/views/record/appointment/list.blade.php`
- `resources/views/record/appointment/create.blade.php`
- `resources/views/record/dashboard.blade.php`
- `resources/views/record/patient/show.blade.php`

Changes:

- Added working `record.appointments.index`, `record.appointments.create`, and `record.appointments.store` routes.
- Rebuilt appointment list and creation screens with `layouts.modern`.
- Preserved appointment date, time, notes, scheduled-by user, and scheduled status behavior.

### Referrals

Files updated:

- `routes/web.php`
- `app/Models/Patient.php`
- `app/Http/Controllers/RecordOfficerController.php`
- `resources/views/record/referral/create.blade.php`
- `resources/views/record/patient/show.blade.php`

Changes:

- Added the missing `Patient::referrals()` relationship.
- Added working `record.referrals.create` and `record.referrals.store` routes.
- Rebuilt referral creation with `layouts.modern`.
- Preserved referral date, referred-to department, reason, notes, referred-by user, and pending status behavior.

### Admissions And Discharges

Files updated:

- `routes/web.php`
- `app/Http/Controllers/RecordOfficerController.php`
- `resources/views/record/admission/create.blade.php`
- `resources/views/record/discharge/create.blade.php`
- `resources/views/record/patient/show.blade.php`

Changes:

- Added working `record.admissions.create`, `record.admissions.store`, `record.discharges.create`, and `record.discharges.store` routes.
- Rebuilt admission and discharge forms with `layouts.modern`.
- Admission uses real vacant bed data from wards and preserves bed occupancy update, bed-space bill generation, and visit activity logging.
- Discharge closes the active admission, updates the visit status, releases the bed when appropriate, and logs activity.

### Record Route Health

Files updated:

- `app/Http/Controllers/RecordOfficerController.php`

Changes:

- Added public `exportRecord()` for the existing `record.patients.export` route.
- Added a graceful `requestForVitalSigns()` response for the existing `record.vital-signs.request` route because this codebase does not currently include a `VitalSignsRequest` model/schema.

## Continue From Here

Phase 3 record/front-desk migration is complete. Next phase: Clinical Workflows.

Recommended prompt:

```text
Start Phase 4 modern UI migration from docs/UI_MODERN_MIGRATION_CONTINUATION_GUIDE.md. Begin with the nurse patient workspace, then doctor patient workspace and shared clinical patient profile partials. Preserve clinical actions, route parameters, patient context, and validation. Use layouts.modern and x-ui components.
```

## Verification Completed

```bash
php -l app/Http/Controllers/RecordOfficerController.php
php -l app/Models/Patient.php
php artisan route:list --name=record --except-vendor
php artisan view:cache
php artisan view:clear
```

Final old-UI scan:

```bash
rg "layouts\.app|layouts\.live|form-control|form-select|btn btn|record_officer|App\\Models|datatable|input-group|card-header bg|card-body|table-responsive" -n resources/views/record
```

Result: no matches.
