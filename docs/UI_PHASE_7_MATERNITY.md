# UI Phase 7: Maternity Migration

## Status

Phase 7 is complete.

The maternity area has been migrated to the modern UI architecture using `layouts.modern`, Tailwind utility classes, and the reusable `x-ui.*` components where appropriate.

## Migrated Areas

- Midwife dashboard
- Midwife patient queue
- Maternity Livewire workspace layout and internals
- Antenatal care index, create, edit, show, patient history, shared form, and legacy partials
- Labour index, create, edit, show, patient history, and shared form
- Labour progress index, create, edit, show, and shared form
- Delivery index, create, edit, show, patient history, and shared form
- Newborn index, create, edit, show, patient history, and shared form
- Newborn examination index, record timeline, create, edit, show, and shared form
- Postnatal examination index, record timeline, create, edit, show, and shared form
- Child follow-up index, record timeline, create, edit, show, and shared form
- Maternal medication summary page
- Maternal journey progress tracker

## Main Files Updated

- `app/Livewire/Midwife/MaternityVisitWorkspace.php`
- `app/Http/Controllers/LabourProgressController.php`
- `resources/views/components/midwife/maternity-visit-workspace.blade.php`
- `resources/views/midwife/dashboard.blade.php`
- `resources/views/midwife/patient/index.blade.php`
- `resources/views/midwife/progress.blade.php`
- `resources/views/midwife/medication/index.blade.php`
- `resources/views/midwife/antenatal/*.blade.php`
- `resources/views/midwife/antenatal/partials/*.blade.php`
- `resources/views/midwife/labour/*.blade.php`
- `resources/views/midwife/labour-progress/*.blade.php`
- `resources/views/midwife/delivery/*.blade.php`
- `resources/views/midwife/newborn/*.blade.php`
- `resources/views/midwife/newborn-examination/*.blade.php`
- `resources/views/midwife/postnatal-examination/*.blade.php`
- `resources/views/midwife/child-follow-up/*.blade.php`

## Behavior Preserved

- Patient search and selection in the Livewire maternity workspace.
- Fixed-activity Livewire entry screens for ANC, labour, delivery, newborn, postnatal, and child follow-up.
- Existing route names and route parameters for maternity records.
- Controller validation payload names for form-backed create/edit screens.
- Patient history and timeline views for antenatal, labour, delivery, newborn, newborn examination, postnatal examination, and child follow-up.
- Record show/edit/delete entry points where they already existed.

## Notes

- The maternal medication controller currently only implements `index`, while routes also declare create/store/show/edit/update/destroy actions. The migrated medication page intentionally avoids linking to missing controller actions.
- The Livewire maternity workspace was rewritten as a data-driven Tailwind component. It preserves existing `wire:model="form.*"` bindings from the old workflow and removes Bootstrap accordion/button/card dependencies.
- Legacy antenatal partials were modernized even though the active migrated antenatal show view no longer includes them.

## Verification Completed

Commands run successfully:

```bash
php -l app/Livewire/Midwife/MaternityVisitWorkspace.php
php -l app/Http/Controllers/NewbornExaminationController.php
php -l app/Http/Controllers/PostnatalExaminationController.php
php -l app/Http/Controllers/ChildFollowUpController.php
php -l app/Http/Controllers/MaternalMedicationController.php
php artisan route:list --name=midwife --except-vendor
php artisan view:cache
rg "layouts\.app" resources/views/midwife resources/views/components/midwife -g "*.blade.php"
rg "container-fluid|d-flex|btn-|card-header|form-control|form-select|form-label|accordion|dropdown-menu|progress-bar|badge bg" resources/views/midwife resources/views/components/midwife -g "*.blade.php"
```

Result:

- Midwife routes generated successfully.
- Blade templates cached successfully.
- No `layouts.app` references remain under the midwife maternity view folders.
- No obvious Bootstrap-era class patterns remain under the midwife maternity view folders.

## Next Phase

Continue with Phase 8: Billing, Finance, and Reports.

Recommended prompt:

```text
Start Phase 8 modern UI migration. Migrate Billing, Finance, and Reports screens to layouts.modern. Preserve calculations, filters, exports, PDFs, receipts, role restrictions, and audit behavior. Keep tables readable and money/status values easy to scan.
```
