# MediFlow RBAC And Module Access Implementation

## Purpose

This document explains how MediFlow's current RBAC system should be upgraded so it obeys:

- `MODULE_LICENSING_APPROACH.md`
- `SIDEBAR_GROUPING_IMPLEMENTATION.md`

The goal is to keep the existing roles and permissions, but add two missing layers:

```text
Client license access
Admin-controlled user module access
```

After this upgrade, the system should decide access using this full chain:

```text
Client license enables module
AND
Admin attaches user to module
AND
User has required role or permission
```

Platform ownership is now centralized:

```text
Central MediFlow platform
    -> Manages licensing, client activations, invoices, payments, modules, and partners

Administrator
    -> Manages one hospital/client installation inside the modules activated by that client's license
```

The local hospital application should not seed or depend on a local platform-owner role.

## Current RBAC Status

MediFlow already has a working role and permission structure.

Current tables:

```text
users
roles
permissions
role_user
role_permission
modules
module_role
temporary_permissions
```

Current models:

```text
App\Models\User
App\Models\Role
App\Models\Permission
App\Models\Module
App\Models\TemporaryPermission
```

Current middleware:

```text
role
permission
access
```

The current RBAC can answer:

```text
Does this user have this role?
Does this user have this permission?
```

But it cannot yet answer:

```text
Did the client pay for this module?
Did the admin attach this user to this module?
Should this sidebar item appear under a professional workflow group?
```

Those missing parts are what this implementation adds.

## Target Access Model

Access should be checked in this order:

```text
1. License check
2. User module access check
3. Role/permission check
4. Optional department check
```

Activation, subscription, plan, agent, and client management routes live in the centralized platform, not in the local hospital app.

### 1. License Check

This checks whether the client license includes the module.

Example:

```text
Client plan: Clinic
Enabled modules: patient_records, clinical_care, doctor, nursing, billing
```

The client should not access Pharmacy, Radiology, or Maternity unless those modules are included or added as paid add-ons.

### 2. User Module Access Check

This checks whether the admin has attached the user to the enabled module.

Example:

```text
Hospital license includes Pharmacy.
Admin attaches only Pharmacist A and HOD B to Pharmacy.
Nurse C should not see Pharmacy.
```

### 3. Role/Permission Check

This checks what the user can do inside the module.

Example:

```text
User has Pharmacy module access.
User has medicine.read.
User does not have medicine_stock.read.
```

The user can see Medicines but not Pharmacy Stock.

### 4. Department Check

This is for department-specific rules already used in the current sidebar.

Example:

```text
Only a pharmacy department HOD should see pharmacy stock reconciliation.
Only lab/radiology department HODs should see department investigations.
```

## Required Database Changes

### 1. Extend `modules`

The existing `modules` table should become the source of truth for sidebar items and module permission grouping.

Add:

```text
license_module
sidebar_group
sidebar_patterns
is_sidebar_visible
```

Optional, if you want clearer names:

```text
sidebar_label
sidebar_icon
sidebar_route
```

If you do not add the optional fields, reuse existing fields:

```text
label -> sidebar label
icon -> sidebar icon
route -> sidebar route
group -> sidebar group
```

Recommended final `modules` fields:

```text
id
name
label
route
icon
group
license_module
sidebar_group
sidebar_patterns
sort_order
is_active
is_sidebar_visible
created_at
updated_at
```

### 2. Add `client_licenses`

Stores the active license for the installation.

Fields:

```text
id
client_name
plan
license_key
starts_at
expires_at
is_active
created_at
updated_at
```

For the current single-hospital installation style, one active license row is enough.

### 3. Add `client_enabled_modules`

Stores add-on modules or manual module overrides.

Fields:

```text
id
client_license_id
module_name
is_enabled
created_at
updated_at
```

Example:

```text
Clinic plan + pharmacy add-on
```

### 4. Add `module_user_access`

Stores which users are allowed into which enabled modules.

Fields:

```text
id
user_id
license_module
granted_by
starts_at
expires_at
is_active
created_at
updated_at
```

Recommended indexes:

```text
unique: user_id, license_module
index: license_module
index: is_active
index: starts_at
index: expires_at
```

## Model Changes

### User Model

Add:

```php
public function moduleAccess()
{
    return $this->hasMany(ModuleUserAccess::class);
}

public function hasModuleAccess(string $module): bool
{
    return app(\App\Services\LicenseService::class)
        ->userHasModuleAccess($this, $module);
}
```

### ModuleUserAccess Model

Create:

```text
app/Models/ModuleUserAccess.php
```

Recommended fillable fields:

```php
protected $fillable = [
    'user_id',
    'license_module',
    'granted_by',
    'starts_at',
    'expires_at',
    'is_active',
];
```

Relationships:

```php
public function user()
{
    return $this->belongsTo(User::class);
}

public function grantedBy()
{
    return $this->belongsTo(User::class, 'granted_by');
}
```

### ClientLicense Model

Create:

```text
app/Models/ClientLicense.php
```

Relationship:

```php
public function enabledModules()
{
    return $this->hasMany(ClientEnabledModule::class);
}
```

### ClientEnabledModule Model

Create:

```text
app/Models/ClientEnabledModule.php
```

Relationship:

```php
public function license()
{
    return $this->belongsTo(ClientLicense::class, 'client_license_id');
}
```

### Module Model

Update fillable:

```php
protected $fillable = [
    'name',
    'label',
    'route',
    'icon',
    'group',
    'license_module',
    'sidebar_group',
    'sidebar_patterns',
    'sort_order',
    'is_active',
    'is_sidebar_visible',
];
```

Cast `sidebar_patterns` if stored as JSON:

```php
protected $casts = [
    'sidebar_patterns' => 'array',
    'is_active' => 'boolean',
    'is_sidebar_visible' => 'boolean',
];
```

## Services

### LicenseService

Create:

```text
app/Services/LicenseService.php
```

Responsibilities:

- Get the active license.
- Check if a license is active and not expired.
- Check whether a license module is enabled.
- Check whether a user has admin-granted module access.

Required methods:

```php
currentLicense()
currentPlan(): ?string
enabledModules(): array
moduleEnabled(string $module): bool
userHasModuleAccess(User $user, string $module): bool
```

Access logic:

```php
public function moduleEnabled(string $module): bool
{
    $license = $this->currentLicense();

    if (! $license || ! $license->is_active || $license->expires_at < now()) {
        return false;
    }

    $planModules = config("mediflow_modules.plans.{$license->plan}", []);

    if (in_array('*', $planModules, true)) {
        return true;
    }

    if (in_array($module, $planModules, true)) {
        return true;
    }

    return $license->enabledModules()
        ->where('module_name', $module)
        ->where('is_enabled', true)
        ->exists();
}
```

User module access logic:

```php
public function userHasModuleAccess(User $user, string $module): bool
{
    if (! $this->moduleEnabled($module)) {
        return false;
    }

    if ($user->hasRole('administrator')) {
        return true;
    }

    return $user->moduleAccess()
        ->where('license_module', $module)
        ->where('is_active', true)
        ->where(function ($query) {
            $query->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now());
        })
        ->where(function ($query) {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now());
        })
        ->exists();
}
```

The administrator bypass is optional. If admins should also be explicitly attached to modules, remove this part:

```php
if ($user->hasRole('administrator')) {
    return true;
}
```

Do not add a local platform-owner bypass. Local access should remain hospital scoped.

### SidebarService

Create:

```text
app/Services/SidebarService.php
```

Responsibilities:

- Load sidebar-visible modules.
- Filter by license.
- Filter by user module access.
- Filter by permission.
- Filter by department rules if needed.
- Group items by professional sidebar group.
- Remove empty groups.

Access flow:

```text
Load modules
Filter by license_module
Filter by module_user_access
Filter by permissions
Group by sidebar_group
Render
```

## Middleware

### EnsureModuleEnabled

Create:

```text
app/Http/Middleware/EnsureModuleEnabled.php
```

Purpose:

```text
Protect direct URL access to disabled or unassigned modules.
```

Example:

```php
public function handle($request, Closure $next, string $module)
{
    $license = app(\App\Services\LicenseService::class);

    if (! $license->moduleEnabled($module)) {
        abort(403, 'This module is not enabled for your license.');
    }

    if (! $license->userHasModuleAccess($request->user(), $module)) {
        abort(403, 'You do not have access to this module.');
    }

    return $next($request);
}
```

Register as:

```php
'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
```

## Route Protection

Each route group should be protected by both module middleware and role/permission middleware where appropriate.

Example:

```php
Route::prefix('pharmacy')
    ->middleware(['auth', 'verified', 'module:pharmacy'])
    ->name('pharmacy.')
    ->group(function () {
        Route::prefix('prescriptions')
            ->middleware('permission:dispense.read')
            ->name('prescriptions.')
            ->group(function () {
                // prescription routes
            });
    });
```

Recommended route module mapping:

| Route Area | Module Middleware |
| --- | --- |
| Patient records | `module:patient_records` |
| Doctor workspace | `module:doctor` |
| Nurse workspace | `module:nursing` |
| Shared clinical records | `module:clinical_care` |
| Midwife/maternity | `module:maternity` |
| Laboratory | `module:laboratory` |
| Radiology | `module:radiology` |
| Pharmacy | `module:pharmacy` |
| Billing/accountant | `module:billing` |
| Finance officer/admin finance | `module:finance` |
| Department operations | `module:department_management` |
| Wards/beds/admission oversight | `module:wards_beds` |
| Reports | `module:reports` |
| Sync | `module:synchronization` |
| Backup/system update | `module:maintenance` |
| User/admin access control | `module:access_control` |

## Sidebar Behavior

The sidebar must not be built from hardcoded roles.

It should use:

```text
modules table
license_module
module_user_access
permissions
sidebar_group
```

Correct visibility rule:

```text
Show item only if:
- module is active
- item is sidebar visible
- client license enables item license_module
- user has access to item license_module
- user has at least one required permission or role
- route exists
```

Show group only if:

```text
At least one child item is visible.
```

Example:

```text
License: Hospital
Admin gives user access to Pharmacy
User permission: medicine.read
```

Sidebar:

```text
Medications
    Medicines
```

The group appears, but only the one permitted item appears.

## Admin Screens Required

### 1. Module Access Management

Used by client admin to attach users to enabled modules.

Can manage:

- User module access
- Module access start date
- Module access expiry date
- Active/inactive module access

Recommended permission:

```text
module_access.manage
```

This screen must only show modules enabled by the client license.

Example:

```text
Module: Pharmacy
Available users:
- Ada Nurse
- Musa Pharmacist
- Chinedu Accountant

Assigned users:
- Musa Pharmacist
```

### 2. Role & Permission Management

This already exists, but it should remain separate from module access.

Purpose:

```text
Module Access = can enter the module.
Permissions = what actions can be performed.
```

## Seeder Changes

Update `ModulePermissionSeeder` so every module item has:

```text
license_module
sidebar_group
route
icon
sort_order
permissions
roles
```

Do not seed local platform-management modules such as agents, clients, plans, or license activation. Those belong to the centralized platform.

Example:

```php
[
    'label' => 'Medicines',
    'name' => 'pharmacy_medicines',
    'icon' => 'bi-capsule',
    'route' => 'pharmacy.medicines.index',
    'group' => 'medications',
    'sidebar_group' => 'medications',
    'license_module' => 'pharmacy',
    'roles' => ['pharmacist', 'head_of_department'],
    'permissions' => ['medicine.read'],
]
```

Recommended mapping:

| Existing Area | License Module | Sidebar Group |
| --- | --- | --- |
| Record patients | `patient_records` | `patients` |
| Patient register | `patient_records` | `patients` |
| Vital signs | `clinical_care` | `clinical` |
| Observations | `clinical_care` | `clinical` |
| Admissions | `wards_beds` or `clinical_care` | `wards_admissions` |
| Prescriptions | `clinical_care` | `medications` |
| Drug chart | `clinical_care` | `medications` |
| Lab requests/results | `laboratory` | `diagnostics` |
| Radiology requests/results | `radiology` | `diagnostics` |
| Midwife records | `maternity` | `maternity` |
| Pharmacy stock/dispensing | `pharmacy` | `medications` |
| Bills | `billing` | `billing` |
| Payments | `billing` | `payments_finance` |
| Revenues/expenses | `finance` | `payments_finance` |
| Departments/consumables | `department_management` | `departments_inventory` |
| Reports | `reports` | `reports` |
| Users/roles/permissions | `access_control` | `administration` |
| Sync/update/backup | `synchronization` or `maintenance` | `system` |

## Permission Naming

Use consistent permission names:

```text
resource.action
```

Examples:

```text
patient.read
patient.create
patient.update
patient.delete
bill.read
bill.create
payment.read
medicine.read
medicine_stock.read
laboratory_request.read
radiology_request.read
module_access.manage
```

Avoid mixing names like:

```text
create_records
view_patients
```

with:

```text
patient.read
patient.create
```

The second style is better for module-based access.

## Temporary Permissions

Temporary permissions should still work, but they should not bypass license or module-user access.

Correct:

```text
Temporary permission can give payment.read for 24 hours,
but only if Billing is enabled and the user has Billing module access.
```

Wrong:

```text
Temporary permission exposes Billing even though the license does not include Billing.
```

## Important Fixes Needed In Current RBAC

### 1. Fix Module Pivot Table Down Migration

Current migration creates:

```text
module_role
```

But the down method drops:

```text
role_modules
```

It should drop:

```text
module_role
```

### 2. Align Naming

The system currently has:

```text
module_role table
RoleModule model
role_modules wording in some places
```

Pick one convention and keep it consistent.

Recommended:

```text
module_role
```

because the existing relationships use:

```php
belongsToMany(Module::class, 'module_role')
```

### 3. Avoid Hardcoded Sidebar Items

Move the big `$navigationItems` array out of:

```text
resources/views/layouts/partials/admin-sidebar.blade.php
```

Use:

```text
SidebarService
modules table
config/sidebar.php
```

### 4. Add Module Middleware

Role middleware alone is not enough.

Example problem:

```text
User has pharmacist role.
Client plan does not include Pharmacy.
```

Without module middleware, the route may still be reachable.

## Implementation Order

1. Fix `module_role` migration naming issue.
2. Add license config in `config/mediflow_modules.php`.
3. Add sidebar group config in `config/sidebar.php`.
4. Add license tables.
5. Add `module_user_access`.
6. Add models for license and module access.
7. Update `User` and `Module` relationships/casts.
8. Create `LicenseService`.
9. Create `EnsureModuleEnabled` middleware.
10. Apply module middleware to route groups.
11. Update `ModulePermissionSeeder`.
12. Create `SidebarService`.
13. Replace hardcoded sidebar with service-driven rendering.
14. Build Module Access Management screen.
15. Build License Management screen.
16. Test all roles under each plan.

## Testing Matrix

Test these combinations:

| Case | Expected Result |
| --- | --- |
| License does not include module | Sidebar item hidden and route returns `403` |
| License includes module, user not attached | Sidebar item hidden and route returns `403` |
| User attached, no permission | Sidebar group may hide if no visible items |
| User attached, one permission | Group shows with only that item |
| User attached, many permissions | Group shows only allowed items |
| Temporary permission granted | Item shows only if license and module access pass |
| Administrator user | Behavior depends on whether admin bypass is enabled |
| Expired license | Paid modules hidden and blocked |
| Add-on module enabled | Add-on module items become available to attached users |

## Final Rule

Do not remove RBAC. Extend it.

Final architecture:

```text
License Plan
    -> Enables modules for the client

Module User Access
    -> Admin attaches users to enabled modules

Roles and Permissions
    -> Control actions inside assigned modules

Sidebar Groups
    -> Present allowed actions in professional workflow groups

Central Platform
    -> Controls MediFlow licensing and activation outside the local hospital app
```

This keeps MediFlow flexible for licensing while making the UI cleaner and the security rules stronger.
