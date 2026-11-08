# UI Phase 6: Diagnostics Migration

## Status

Phase 6 diagnostics migration is complete for Laboratory and Radiology screens.

## Scope

This phase migrated diagnostic request queues, result entry, investigation setup, and result parameter management to the modern Laravel UI architecture.

The work preserved existing diagnostic behavior while replacing old Bootstrap page shells with `layouts.modern`, `x-ui.*` components, Tailwind table patterns, and Tailwind pagination where Livewire is used.

## Laboratory

### Files Updated

- `routes/lab.php`
- `app/Http/Controllers/Lab/RequestController.php`
- `app/Livewire/Lab/Result.php`
- `app/Livewire/Lab/InvestigationRequestsTable.php`
- `resources/views/components/lab/result.blade.php`
- `resources/views/components/lab/investigation-requests-table.blade.php`
- `resources/views/lab/request/index.blade.php`
- `resources/views/lab/request/create-result.blade.php`
- `resources/views/lab/request/result.blade.php`
- `resources/views/lab/investigation/index.blade.php`
- `resources/views/lab/investigation/create.blade.php`
- `resources/views/lab/investigation/edit.blade.php`
- `resources/views/lab/investigation/parameter/index.blade.php`
- `resources/views/lab/investigation/parameter/create.blade.php`
- `resources/views/lab/investigation/parameter/edit.blade.php`

### Behavior Preserved

- Bill search and lab result entry.
- Grouped patient and walk-in result creation.
- Result saving, deletion, and report printing.
- Investigation CRUD.
- Investigation parameter CRUD.
- Payment-aware request handling.
- Existing permission and role route middleware.

### Functional Corrections

- The lab result creation route now accepts `groupType` and `groupId`:
  - `lab/requests/group/{groupType}/{groupId}/results/create`
  - route name: `lab.requests.results.create`
- Lab grouped request actions now link result viewing through `lab.requests.show` using the bill id.
- Lab Livewire pagination now uses Tailwind.
- Lab result Livewire screen now uses `layouts.modern`.

## Radiology

### Files Updated

- `app/Livewire/Radiology/InvestigationRequestsTable.php`
- `resources/views/components/radiology/investigation-requests-table.blade.php`
- `resources/views/radiology/dashboard.blade.php`
- `resources/views/radiology/request/index.blade.php`
- `resources/views/radiology/request/create-result.blade.php`
- `resources/views/radiology/request/edit-result.blade.php`
- `resources/views/radiology/request/result.blade.php`
- `resources/views/radiology/investigation/index.blade.php`
- `resources/views/radiology/investigation/create.blade.php`
- `resources/views/radiology/investigation/edit.blade.php`
- `resources/views/radiology/investigation/parameter/index.blade.php`
- `resources/views/radiology/investigation/parameter/create.blade.php`
- `resources/views/radiology/investigation/parameter/edit.blade.php`

### Behavior Preserved

- Radiology request queue polling.
- Paid-request result entry.
- Structured parameter results.
- Optional radiology image uploads.
- Existing image replacement/removal during result editing.
- Result print view.
- Investigation CRUD.
- Investigation parameter CRUD.
- Dashboard request and revenue summaries.

### Functional Corrections

- Radiology investigation create/edit views now use `radiology.*` route names instead of incorrect `lab.*` route names.
- Radiology Livewire pagination now uses Tailwind.
- Radiology request list now handles payment status case-insensitively for display actions.

## UI Patterns Applied

- `layouts.modern` for full-page diagnostic screens.
- `x-ui.page` for page headers and action areas.
- `x-ui.card` for contained forms, request queues, and report panels.
- `x-ui.table` for investigation/request/parameter lists.
- `x-ui.badge` for payment and count status display.
- `x-ui.input` and `x-ui.textarea` for forms and filters.
- Print result pages keep dedicated print CSS so reports remain printable without rendering the whole application shell.

## Verification Completed

Commands run successfully:

```bash
php -l app/Livewire/Lab/Result.php
php -l app/Livewire/Radiology/InvestigationRequestsTable.php
php -l app/Http/Controllers/Lab/RequestController.php
php -l app/Http/Controllers/Radiology/RequestController.php
php -l app/Http/Controllers/Radiology/InvestigationController.php
php -l app/Http/Controllers/Radiology/ParameterController.php
php artisan route:list --name=lab --except-vendor
php artisan route:list --name=radiology --except-vendor
php artisan view:cache
compiled Blade PHP lint
```

Result:

- Blade templates cached successfully.
- All compiled Blade PHP files passed lint.
- Lab and Radiology route lists generated successfully.

## Next Phase

Continue with Phase 7: Maternity.

Recommended prompt:

```text
Start Phase 7 modern UI migration from docs/UI_MODERN_MIGRATION_CONTINUATION_GUIDE.md and docs/UI_PHASE_6_DIAGNOSTICS.md. Migrate Maternity screens to layouts.modern using x-ui components. Preserve antenatal, labour, delivery, newborn, postnatal, and child follow-up workflows. Keep route parameters and validation behavior unchanged.
```
