# UI Phase 4: Clinical Workflows

## Status

Phase 4 is completed for the shared clinical workflow surface used by doctor and nurse routes.

## Migrated Screens

1. Nurse patient workspace
2. Doctor patient workspace
3. Shared clinical patient profile
4. Vital signs recorder
5. Observation recorder
6. Continuation sheet
7. Fluid balance workspace
8. Drug chart workspace
9. Admission workspace
10. Discharge workspace
11. Investigation request workspace
12. Prescription workspace
13. Clinical record indexes for doctor and nurse
14. Patient index/search/history wrappers used by clinical routes
15. Investigation result/detail view
16. Nurse dashboard
17. Nurse admissions list
18. Legacy global vital-sign route redirects

## Updated Files

Livewire classes:

- `app/Livewire/Nurse/PatientManagement.php`
- `app/Livewire/Doctor/PatientManagement.php`
- `app/Livewire/Patient/PatientManagement.php`
- `app/Livewire/Clinical/VitalSignRecorder.php`
- `app/Livewire/Clinical/ObservationRecorder.php`
- `app/Livewire/Clinical/ContinuationSheet.php`
- `app/Livewire/Clinical/FluidBalanceWorkspace.php`
- `app/Livewire/Clinical/DrugChartWorkspace.php`
- `app/Livewire/Clinical/AdmissionWorkspace.php`
- `app/Livewire/Clinical/DischargeWorkspace.php`
- `app/Livewire/Clinical/InvestigationRequestWorkspace.php`
- `app/Livewire/Clinical/PrescriptionWorkspace.php`
- `app/Livewire/Clinical/ClinicalRecordIndex.php`

Views:

- `resources/views/components/nurse/patient-management.blade.php`
- `resources/views/components/doctor/patient-management.blade.php`
- `resources/views/components/patient/patient-management.blade.php`
- `resources/views/components/clinical/_feedback.blade.php`
- `resources/views/components/clinical/*.blade.php`
- `resources/views/components/clinical/record-indexes/*.blade.php`
- `resources/views/patient/show.blade.php`
- `resources/views/patient/index.blade.php`
- `resources/views/patient/search.blade.php`
- `resources/views/patient/history.blade.php`
- `resources/views/patient/investigation/show.blade.php`
- `resources/views/doctor/patient/show.blade.php`
- `resources/views/nurse/patient/show.blade.php`
- `resources/views/nurse/dashboard.blade.php`
- `resources/views/nurse/admissions/index.blade.php`

Controllers:

- `app/Http/Controllers/Doctor/PatientController.php`
- `app/Http/Controllers/Nurse/PatientController.php`
- `app/Http/Controllers/Patient/PatientController.php`
- `app/Http/Controllers/Patient/VitalSignController.php`
- `app/Http/Controllers/Patient/PrescriptionController.php`
- `app/Http/Controllers/Patient/InvestigationController.php`
- `app/Http/Controllers/Nurse/NurseController.php`
- `app/Http/Controllers/VitalSignsController.php`

## Behavior Preserved

- Nurse and doctor patient queues still filter by service request status, visit status, service, and patient search.
- Queue complete and close-visit actions are unchanged.
- Patient profile still exposes role-based clinical actions.
- Clinical Livewire workspaces preserve existing state, validation, and save/edit/cancel actions.
- Investigation request billing logic remains intact, including accumulated bill creation and bill sync on edit/remove.
- Prescription item add/edit/remove/start/stop/submit behavior remains intact.
- Admission still reserves beds and generates bed-space bills.
- Discharge still closes admission and visit status and releases the bed.

## Small Fixes Included

- `ContinuationSheet::cancelEdit()` now resets `diagnose` correctly.
- `Patient\InvestigationController` now imports `Investigation` and fixes the old `$invstigation` typo in the legacy POST path.
- `Patient\PrescriptionController@show` redirects to the modern prescription workspace with the selected prescription loaded.
- `Patient\VitalSignController@edit` redirects to the modern vital signs workspace because direct legacy edit was routed but not implemented.
- `VitalSignsController` global legacy routes now redirect to the modern vital-sign/history pages.
- `Nurse\NurseController@dashboard` was added because `nurse.dashboard` referenced it but it was missing.
- `PatientManagement` now passes `pageTitle` and `pageSubtitle` and uses Tailwind pagination.

## Legacy Files Still Present

Some old `resources/views/patient/...` files remain because they are duplicate legacy controller views or maternity/profile partials that belong to later phases. The active clinical GET routes now use the modern Livewire workspaces or modern display pages.

Do not delete these until route coverage is audited again during final cleanup.

## Verification

Run after follow-up edits:

```bash
php artisan view:cache
php artisan route:list --name=clinical --except-vendor
php artisan route:list --path=patient --except-vendor
php artisan view:clear
```

## Next Phase

Start Phase 5: Pharmacy.

Suggested prompt:

```text
Start Phase 5 modern UI migration from docs/UI_MODERN_MIGRATION_CONTINUATION_GUIDE.md and docs/UI_PHASE_4_CLINICAL_WORKFLOWS.md. Migrate Pharmacy screens to layouts.modern using x-ui components. Preserve prescription queue, dispensing, stock movement, batches, expiry alerts, transactions, reconciliation, and pharmacy finance behavior.
```

