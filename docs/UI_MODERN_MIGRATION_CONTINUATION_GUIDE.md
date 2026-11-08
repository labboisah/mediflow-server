# Modern UI Migration Continuation Guide

## Purpose

Use this document as the prompt guide for continuing the Mediflow modern UI migration later. It records where the work stopped, what has already been completed, and the exact order to follow until the full system is migrated.

## Installation Configuration Update

Super admin Installation Setup is available at `/admin/installation`. See [INSTALLATION_SETUP.md](INSTALLATION_SETUP.md) for package configuration, system branding, welcome-page templates, account provisioning, route enforcement, and tests.

## Specialist Package

Specialist is implemented as the seventh package. See [Specialist Operations](SPECIALIST_OPERATIONS.md) for deployment, configuration, verification and facility acceptance, and [Specialist Package Implementation Specification](SPECIALIST_PACKAGE_IMPLEMENTATION_SPEC.md) for the design contract.

## Current Stop Point

Phase 8 billing, finance, and reports are complete. Continue with the final cleanup phase for remaining legacy/fallback screens.

Completed modern UI areas:

1. Modern UI foundation
2. Admin dashboard
3. Access Control
4. Admin Users
5. Admin Departments
6. Admin Services
7. Admin Investigations
8. Admin Wards
9. Admin Beds
10. Admin Temporary Permissions
11. System Sync Dashboard
12. System Update
13. Backup
14. Record Officer Dashboard
15. Patient List
16. Patient Registration
17. Patient Search - completed
18. Patient Show/Profile - completed
19. Patient Edit - completed
20. Patient History - completed
21. Visits - completed
22. Appointments - completed
23. Referrals - completed
24. Patient Register Report - completed
25. Admissions - completed
26. Discharges - completed
27. Patient Export Route - completed
28. Clinical workflows - completed
29. Pharmacy workflows - completed
30. Diagnostics workflows - completed
31. Maternity workflows - completed
32. Billing, finance, and reports - completed

Do not restart these from scratch unless a bug is found. Continue from the final cleanup phase for remaining legacy/fallback screens.

## Foundation Already Created

Modern layout and shell:

- `resources/views/layouts/modern.blade.php`
- `resources/views/layouts/partials/modern-sidebar.blade.php`
- `resources/views/layouts/partials/modern-topbar.blade.php`
- `resources/views/layouts/partials/modern-alerts.blade.php`

Modern assets:

- `resources/css/modern.css`
- `resources/js/modern.js`
- `public/css/modern-fallback.css`
- `tailwind.config.js`
- `postcss.config.js`

Reusable components:

- `resources/views/components/ui/page.blade.php`
- `resources/views/components/ui/card.blade.php`
- `resources/views/components/ui/button.blade.php`
- `resources/views/components/ui/input.blade.php`
- `resources/views/components/ui/select.blade.php`
- `resources/views/components/ui/textarea.blade.php`
- `resources/views/components/ui/badge.blade.php`
- `resources/views/components/ui/table.blade.php`
- `resources/views/components/ui/empty-state.blade.php`

Reference docs:

- `docs/UI_ARCHITECTURE.md`
- `docs/UI_PHASE_1_FOUNDATION.md`
- `docs/UI_PHASE_2_ADMIN_CORE.md`
- `docs/UI_PHASE_3_PATIENT_FRONT_DESK.md`
- `docs/UI_PHASE_4_CLINICAL_WORKFLOWS.md`
- `docs/UI_PHASE_5_PHARMACY.md`
- `docs/UI_PHASE_6_DIAGNOSTICS.md`
- `docs/UI_PHASE_7_MATERNITY.md`
- `docs/UI_PHASE_8_BILLING_FINANCE_REPORTS.md`
- `docs/SIDEBAR_GROUPING_IMPLEMENTATION.md`
- `docs/RBAC_MODULE_ACCESS_IMPLEMENTATION.md`
- `docs/MODULE_LICENSING_APPROACH.md`

## Screens Already Migrated

### Admin Dashboard

Files:

- `app/Livewire/Admin/Dashboard.php`
- `resources/views/components/admin/dashboard.blade.php`

Status:

- Uses `layouts.modern`.
- Uses modern dashboard card layout.
- Uses `pageTitle` and `pageSubtitle`.
- Sidebar collapse/scroll behavior is tested from this page.

### Access Control

Files:

- `app/Livewire/Admin/AccessControlManager.php`
- `resources/views/components/admin/access-control-manager.blade.php`

Status:

- Uses `layouts.modern`.
- Preserves role editing.
- Preserves permission creation.
- Preserves role-user assignment.
- Preserves user-module access toggles.
- Preserves protected `administrator` role behavior.

### Admin Users

Files:

- `app/Http/Controllers/Admin/UserController.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/users/show.blade.php`

Status:

- Uses `layouts.modern`.
- User list, filters, create, edit, and show pages migrated.
- Department lookup moved from Blade to controller.
- User index eager-loads roles and departments.

### Admin Departments

Files:

- `app/Http/Controllers/Admin/DepartmentController.php`
- `resources/views/admin/departments/index.blade.php`
- `resources/views/admin/departments/create.blade.php`
- `resources/views/admin/departments/edit.blade.php`

Status:

- Uses `layouts.modern`.
- Department index/create/edit pages migrated.
- Preserves create, edit, delete, validation, redirects, and route names.
- Department list is ordered by name.
- Edit now fails with a normal 404 when the department does not exist.

### Admin Services

Files:

- `app/Http/Controllers/Admin/ServiceController.php`
- `resources/views/admin/services/index.blade.php`
- `resources/views/admin/services/create.blade.php`
- `resources/views/admin/services/edit.blade.php`
- `resources/views/admin/services/show.blade.php`

Status:

- Uses `layouts.modern`.
- Service index/create/edit/show pages migrated.
- Preserves search, category filter, status filter, deleted-service filter, pagination, create, edit, delete, restore, and show routes.
- Department lookup moved from Blade to the controller.
- Service show bill count moved from Blade to `withCount`.
- `is_active` checkbox state is normalized in the controller so active/inactive saves reliably.

### Admin Investigations

Files:

- `app/Http/Controllers/Admin/InvestigationController.php`
- `resources/views/admin/investigations/index.blade.php`
- `resources/views/admin/investigations/create.blade.php`
- `resources/views/admin/investigations/edit.blade.php`

Status:

- Uses `layouts.modern`.
- Investigation index/create/edit pages migrated.
- Preserves department/type grouping, create, edit, delete guard, validation, redirects, and route names.
- Investigation type lookup moved from Blade to the controller.
- Parameter counts are eager-loaded with the investigation list.
- Generated show route redirects to edit to avoid missing-method crashes.

### Admin Wards And Beds

Files:

- `app/Http/Controllers/Admin/WardController.php`
- `app/Http/Controllers/Admin/BedController.php`
- `resources/views/admin/wards/index.blade.php`
- `resources/views/admin/wards/create.blade.php`
- `resources/views/admin/wards/edit.blade.php`
- `resources/views/admin/wards/beds/index.blade.php`
- `resources/views/admin/wards/beds/create.blade.php`
- `resources/views/admin/wards/beds/edit.blade.php`

Status:

- Uses `layouts.modern`.
- Ward index/create/edit pages migrated.
- Bed index/create/edit pages migrated.
- Preserves ward CRUD, bed CRUD, capacity-generated beds, delete guards, validation, redirects, and route names.
- Ward list uses eager counts for total, occupied, and vacant beds.
- Views support both `admin.*` and `medical-director.*` route prefixes.
- Generated ward show route redirects to edit to avoid missing-detail-page crashes.

### Admin Temporary Permissions

Files:

- `app/Http/Controllers/Admin/TemporaryPermissionController.php`
- `resources/views/admin/temporary-permissions/index.blade.php`
- `resources/views/admin/temporary-permissions/create.blade.php`

Status:

- Uses `layouts.modern`.
- Temporary permission index/create pages migrated.
- Preserves temporary grant creation, revocation, deletion, expiry display, validation, pagination, and audit logging.
- User and permission lists are ordered in the controller.

## Important Technical Notes

### Phase 3 Started: Record Officer Dashboard And Patient List

Files:

- `app/Http/Controllers/RecordOfficerController.php`
- `app/Livewire/Patient/PatientManagement.php`
- `resources/views/record/dashboard.blade.php`
- `resources/views/record/patient/list.blade.php`
- `resources/views/components/patient/patient-management.blade.php`

Status:

- Record Officer dashboard uses `layouts.modern`.
- Patient List wrapper uses `layouts.modern`.
- Patient Management Livewire component uses `layouts.modern` when opened directly.
- Dashboard counts moved from Blade into `RecordOfficerController`.
- Patient list filters, sorting, pagination, and record actions are preserved.

### Patient Registration

Files:

- `app/Livewire/Patient/PatientRegistration.php`
- `resources/views/components/patient/patient-registration.blade.php`

Status:

- Uses `layouts.modern`.
- Registration form migrated to `x-ui.*` components.
- Preserves file type, ANC flag, walk-in flag, discount, patient demographics, next of kin, billing preview, validation, and save behavior.


### Patient Search, Profile, And Edit

Files:

- `app/Http/Controllers/RecordOfficerController.php`
- `resources/views/record/patient/search.blade.php`
- `resources/views/record/patient/show.blade.php`
- `resources/views/record/patient/edit.blade.php`

Status:

- Uses `layouts.modern`.
- Record search returns the record-specific modern search page.
- Stale `record_officer.*` route references were removed from the record search screen.
- Record patient profile no longer nests the old shared clinical `patient.show` layout.
- Edit form uses controller-provided states/LGAs and the existing Ajax LGA endpoint.
- Registration and visit redirects now return to `record.patients.show` for record users.

### Phase 3 Completion: Visits, Appointments, And Referrals

Files:

- `routes/web.php`
- `app/Models/Patient.php`
- `app/Http/Controllers/RecordOfficerController.php`
- `resources/views/record/dashboard.blade.php`
- `resources/views/record/patient/show.blade.php`
- `resources/views/record/visit/create.blade.php`
- `resources/views/record/appointment/list.blade.php`
- `resources/views/record/appointment/create.blade.php`
- `resources/views/record/referral/create.blade.php`

Status:

- Record visit creation uses `layouts.modern` and controller-provided active services.
- Record appointment routes were added and old appointment views now use the modern UI.
- Record referral routes were added and old referral views now use the modern UI.
- Patient profile includes visit history, appointment summary, referral summary, and actions for visit/appointment/referral workflows.
- Record dashboard search now uses `record.patients.search`, and appointments are reachable from the dashboard.
### Asset Build

Node/NPM was not available in the current shell, so `npm run build` could not be verified.

Until the real Vite build is generated, `layouts.modern` uses:

- `public/css/modern-fallback.css`
- existing built `resources/js/app.js`

When Node/NPM is available, run:

```bash
npm run build
```

Then confirm `public/build/manifest.json` contains:

- `resources/css/modern.css`
- `resources/js/modern.js`

### Layout Rule

Only migrated screens should use:

```blade
@extends('layouts.modern')
```

or:

```php
#[Layout('layouts.modern')]
```

Unmigrated screens should remain on:

- `layouts.app`
- `layouts.live`

### Livewire Metadata Rule

Every migrated Livewire component should pass:

```php
'pageTitle' => 'Screen Name',
'pageSubtitle' => 'Short screen purpose.',
```

The view should use:

```blade
<x-ui.page :title="$pageTitle" :subtitle="$pageSubtitle">
    ...
</x-ui.page>
```

### Controller View Metadata Rule

Every migrated Blade controller view should include:

```blade
@section('title', 'Screen Name')
@section('page-title', 'Screen Name')
@section('page-subtitle', 'Short screen purpose.')
```

and wrap the content with:

```blade
<x-ui.page title="Screen Name" subtitle="Short screen purpose.">
    ...
</x-ui.page>
```

## Continue From Here

Start with the final cleanup phase for remaining legacy/fallback screens.

Recommended next prompt:

```text
Start Phase 8 modern UI migration from docs/UI_MODERN_MIGRATION_CONTINUATION_GUIDE.md. Migrate Billing, Finance, and Reports screens to layouts.modern. Preserve calculations, filters, exports, PDFs, receipts, role restrictions, and audit behavior. Keep tables readable and money/status values easy to scan.
```

## Phase 2: Admin/Core

Phase 2 is complete. See docs/UI_PHASE_2_ADMIN_CORE.md for the completed admin/core migration record.

## Phase 3: Patient And Front Desk

Start only after Phase 2 is complete.

Order:

1. Record Officer Dashboard
2. Patient List
3. Patient Registration
4. Patient Search - completed
5. Patient Show/Profile - completed
6. Patient Edit - completed
7. Patient History - completed
8. Visits - completed
9. Appointments - completed
10. Referrals - completed
11. Patient Register Report - completed
12. Admissions - completed
13. Discharges - completed

Prompt:

```text
Start Phase 8 modern UI migration from docs/UI_MODERN_MIGRATION_CONTINUATION_GUIDE.md. Migrate Billing, Finance, and Reports screens to layouts.modern. Preserve calculations, filters, exports, PDFs, receipts, role restrictions, and audit behavior. Keep tables readable and money/status values easy to scan.
```

## Phase 4: Clinical Workflows

Order:

1. Nurse patient workspace - completed
2. Doctor patient workspace - completed
3. Vital signs - completed
4. Observations - completed
5. Prescriptions - completed
6. Drug charts - completed
7. Fluid balance - completed
8. Admissions - completed
9. Discharge - completed
10. Continuation sheets - completed
11. Investigation requests - completed
12. Clinical record indexes - completed

Prompt:

```text
Start Phase 4 modern UI migration. Migrate clinical workflows module by module using layouts.modern. Preserve all patient context, route parameters, Livewire state, validation, and clinical record relationships. Do not change medical workflow logic unless required for UI compatibility.
```


## Phase 4 Completion: Clinical Workflows

See `docs/UI_PHASE_4_CLINICAL_WORKFLOWS.md` for the full migration record.

Status:

- Nurse and doctor patient workspaces use `layouts.modern`.
- Shared patient clinical profile uses `layouts.modern`.
- Shared clinical Livewire workspaces use `layouts.modern` and `x-ui.*` components.
- Clinical record indexes for doctor and nurse use modern tables and Tailwind pagination.
- Patient search/history and investigation result pages reachable from clinical routes use the modern layout.
- Nurse dashboard and nurse admissions list use the modern layout.
- Legacy duplicate patient clinical Blade files remain for final route cleanup; active clinical GET routes now use modern workspaces.
## Phase 5: Pharmacy

Order:

1. Prescriptions queue
2. Dispensing workspace
3. Medicines
4. Stock
5. Batches
6. Expiry alerts
7. Transactions
8. Stock reconciliation
9. Pharmacy finance

Prompt:

```text
Start Phase 5 modern UI migration. Migrate Pharmacy screens to layouts.modern using x-ui components. Preserve dispensing, stock movement, batches, expiry alerts, transactions, reconciliation, and pharmacy finance behavior. Keep inventory tables dense but readable.
```

## Phase 6: Diagnostics

Order:

1. Lab requests
2. Lab result entry
3. Lab investigations
4. Lab parameters
5. Radiology requests
6. Radiology result entry
7. Radiology investigations
8. Radiology parameters

Prompt:

```text
Start Phase 6 modern UI migration. Migrate Laboratory and Radiology screens to layouts.modern. Preserve request queues, result entry, investigation setup, parameters, file/image result handling, and permissions. Keep lab/radiology layouts consistent.
```


## Phase 6 Completion: Diagnostics

See `docs/UI_PHASE_6_DIAGNOSTICS.md` for the full migration record.

Status:

- Laboratory requests, result entry, investigations, and parameters use the modern UI.
- Radiology requests, result entry/editing, printable reports, investigations, parameters, and dashboard partial use the modern UI.
- Lab grouped result creation route now accepts the required group parameters.
- Radiology investigation forms now use `radiology.*` route names instead of the incorrect `lab.*` route names.
- Tailwind pagination is used for diagnostic Livewire request tables.

## Phase 7: Maternity

Order:

1. Midwife dashboard - completed
2. ANC patients - completed
3. Antenatal care - completed
4. Maternity workspace internals - in progress
5. Labour - completed
6. Labour progress
7. Delivery
8. Newborn
9. Newborn examination
10. Postnatal examination
11. Child follow-up

Prompt:

```text
Start Phase 7 modern UI migration. Migrate Maternity screens to layouts.modern. Preserve antenatal, labour, delivery, newborn, postnatal, and child follow-up workflows. Keep forms sectioned, readable, and faithful to existing validation and route parameters.
```

## Phase 8: Billing, Finance, And Reports

Order:

1. Bills
2. Payments
3. Expenses
4. Revenues
5. Finance reports
6. Payment reports
7. Activity reports
8. Department reports
9. Export/PDF views

Prompt:

```text
Start Phase 8 modern UI migration. Migrate Billing, Finance, and Reports screens to layouts.modern. Preserve calculations, filters, exports, PDFs, receipts, role restrictions, and audit behavior. Keep tables readable and money/status values easy to scan.
```

## Standard Verification For Every Migration Slice

Run:

```bash
php -l path/to/changed/php/file.php
php artisan view:cache
php artisan route:list --name=target.route.prefix --except-vendor
php artisan view:clear
```

If a migrated screen uses a controller view, test:

- index route
- create route
- edit route with an existing record
- show route if available
- POST/PUT/DELETE action paths if safe in the local environment

If a migrated screen uses Livewire, test:

- component mounts successfully
- filters update
- buttons fire the expected Livewire action
- validation errors display correctly
- toast events still display

## Do Not Forget

- Keep old layouts until all screens are migrated.
- Keep module licensing and user module access as the sidebar authority.
- Do not add hardcoded sidebar links.
- Avoid broad backend refactors during UI migration.
- Update this document after each completed module.
- Update `docs/UI_PHASE_2_ADMIN_CORE.md` while Phase 2 is active.

















## Specialist package implementation (2026-09-29)

The Specialist workspace is implemented using the modern shell and existing `x-ui` components at `/specialist`. Practice setup, scoped appointments/patients, consultations, orders/results/referrals, billing, reports and history use `resources/views/specialist` and controller routes. The sidebar entry is seeded through the module registry, not hardcoded. Specialist package combinations and branding extend Installation Setup.

See [Specialist Operations](SPECIALIST_OPERATIONS.md) for activation, staff privileges, verified checks and remaining facility acceptance. Do not reseed users or enable optional modules to make standalone screens work. Automatic synchronization of Specialist clinical records is intentionally blocked until a complete exchange contract exists.

## Care Network implementation

The internal Partner Network at `/care-network` uses the modern shell and seeded module navigation. The restricted portal at `/partner-portal` uses a separate authentication guard and limited shell. Registration/review/agreements, disclosure preview, cases, reports and private documents are implemented in `resources/views/network`. See [Care Network Operations](CARE_NETWORK_OPERATIONS.md) for activation, validation and remaining browser/facility acceptance.
