# Phase 2: Admin/Core Modern UI Migration

## Objective

Move the core administration screens into `layouts.modern` first. These screens define users, roles, permissions, modules, services, departments, wards, and other setup data used by the rest of Mediflow.

If the migration resumes later, start from `docs/UI_MODERN_MIGRATION_CONTINUATION_GUIDE.md`.

## Migration Order

1. Admin Dashboard - completed in Phase 1 trial
2. Access Control - completed
3. Users - completed
4. Departments - completed
5. Services - completed
6. Investigations - completed
7. Wards - completed
8. Beds - completed
9. Temporary Permissions - completed
10. System tools - completed

## Completed In This Phase

### Access Control

Files updated:

- `app/Livewire/Admin/AccessControlManager.php`
- `resources/views/components/admin/access-control-manager.blade.php`

Changes:

- Switched from `layouts.live` to `layouts.modern`.
- Added `pageTitle` and `pageSubtitle` for the modern topbar.
- Rebuilt the screen with `x-ui.*` components.
- Preserved all existing Livewire methods and bindings.
- Preserved protected-role behavior for `administrator`.
- Preserved licensed module access toggles.
- Preserved role permission editing.
- Preserved permission creation.
- Preserved user-to-role assignment.

### Users

Files updated:

- `app/Http/Controllers/Admin/UserController.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/users/show.blade.php`

Changes:

- Switched user index/create/edit/show pages from `layouts.app` to `layouts.modern`.
- Rebuilt filters, tables, profile view, and forms with `x-ui.*` components.
- Preserved search, role filter, deleted-user filter, pagination, create, update, restore, and delete actions.
- Moved department lookup out of Blade views and into the controller.
- Eager-loaded user departments on the index/show paths for cleaner rendering.

### Departments

Files updated:

- `app/Http/Controllers/Admin/DepartmentController.php`
- `resources/views/admin/departments/index.blade.php`
- `resources/views/admin/departments/create.blade.php`
- `resources/views/admin/departments/edit.blade.php`

Changes:

- Switched department index/create/edit pages from `layouts.app` to `layouts.modern`.
- Rebuilt the table and forms with `x-ui.page`, `x-ui.table`, `x-ui.card`, `x-ui.input`, `x-ui.button`, and `x-ui.empty-state`.
- Preserved create, update, delete, validation, redirects, and route names.
- Ordered department records by name for a steadier admin list.
- Changed edit lookup to `findOrFail` so missing departments use Laravel's normal 404 behavior.

### Services

Files updated:

- `app/Http/Controllers/Admin/ServiceController.php`
- `resources/views/admin/services/index.blade.php`
- `resources/views/admin/services/create.blade.php`
- `resources/views/admin/services/edit.blade.php`
- `resources/views/admin/services/show.blade.php`

Changes:

- Switched service index/create/edit/show pages from `layouts.app` to `layouts.modern`.
- Rebuilt filters, table, forms, detail view, restore/delete actions, and pagination with `x-ui.*` components.
- Preserved search, category filter, status filter, deleted-service filter, create, update, delete, restore, and show behavior.
- Moved department lookups out of Blade into the controller.
- Moved bill usage count out of Blade into the controller with `withCount`.
- Normalized `is_active` checkbox handling so inactive services can be saved reliably.

### Investigations

Files updated:

- `app/Http/Controllers/Admin/InvestigationController.php`
- `resources/views/admin/investigations/index.blade.php`
- `resources/views/admin/investigations/create.blade.php`
- `resources/views/admin/investigations/edit.blade.php`

Changes:

- Switched investigation index/create/edit pages from `layouts.app` to `layouts.modern`.
- Rebuilt department/type grouped investigation tables and forms with `x-ui.*` components.
- Preserved create, update, delete guard, validation, redirects, and route names.
- Moved investigation type lookup out of Blade into the controller.
- Eager-loaded investigation types, investigations, and parameter counts for cleaner rendering.
- Added a generated `show` route redirect to edit, matching the current no-detail-page behavior.

### Wards And Beds

Files updated:

- `app/Http/Controllers/Admin/WardController.php`
- `app/Http/Controllers/Admin/BedController.php`
- `resources/views/admin/wards/index.blade.php`
- `resources/views/admin/wards/create.blade.php`
- `resources/views/admin/wards/edit.blade.php`
- `resources/views/admin/wards/beds/index.blade.php`
- `resources/views/admin/wards/beds/create.blade.php`
- `resources/views/admin/wards/beds/edit.blade.php`

Changes:

- Switched ward and bed screens from `layouts.app` to `layouts.modern`.
- Rebuilt ward and bed tables/forms with `x-ui.*` components.
- Preserved ward CRUD, bed CRUD, capacity-generated beds, delete guards, validation, redirects, and route names.
- Added route-prefix awareness so shared controllers/views work under both `admin.*` and `medical-director.*`.
- Replaced per-row bed collection counts with eager ward bed counts.
- Added a generated ward `show` route redirect to edit, matching the current no-detail-page behavior.

### Temporary Permissions

Files updated:

- `app/Http/Controllers/Admin/TemporaryPermissionController.php`
- `resources/views/admin/temporary-permissions/index.blade.php`
- `resources/views/admin/temporary-permissions/create.blade.php`

Changes:

- Switched temporary permission index/create pages from `layouts.app` to `layouts.modern`.
- Rebuilt the grant form, status table, revoke action, delete action, and pagination with `x-ui.*` components.
- Preserved grant creation, duplicate active-grant guard, revocation, deletion, expiry display, validation, pagination, and audit logging.
- Ordered user and permission lists in the controller.

### Local License Activation

Local License Management was removed. Agents, clients, plans, subscription activation, and license activation are now managed in the centralized platform. The local hospital app keeps only module enforcement data pushed from that platform.

## Verification

Run after each Admin/Core screen migration:

```bash
php -l app/Livewire/Admin/ChangedComponent.php
php artisan view:cache
php artisan route:list --name=admin.target-route --except-vendor
php artisan view:clear
```

## Notes

The current environment still needs Node/NPM available on PATH to run the real Tailwind build. Until then, `layouts.modern` uses `public/css/modern-fallback.css`.

