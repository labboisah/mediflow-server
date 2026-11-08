# MediFlow Modular Licensing Approach

## Overview

MediFlow should remain one Laravel/Livewire codebase, but different clients should receive different parts of the system based on the license or plan they paid for.

The goal is not to create separate applications for Pharmacy, Clinic, Diagnostic Annex, Maternity, and Hospital. The goal is to keep one system and activate only the modules included in each client's license.

This makes the product easier to maintain, easier to sell, and easier to upgrade.

## Main Concept

The system should use three layers of access control:

1. **License/module access**
   - Decides whether the client has access to a feature area at all.
   - Example: a Pharmacy client should not have access to Maternity, Radiology, or Ward Management.

2. **Admin module-user access**
   - Decides which users inside the client organization are allowed to enter each enabled module.
   - Example: a Hospital license may include Pharmacy, but the admin can give Pharmacy access only to pharmacy staff.

3. **Role/permission access**
   - Decides what a user can do inside an enabled module.
   - Example: a hospital may have Pharmacy enabled, but only users with the pharmacist role should dispense medications.

The rule should be:

```text
Client license enables the module.
Client admin assigns users to enabled modules.
User role and permissions control actions inside assigned modules.
```

So access should only be granted when all checks pass:

```text
Module is enabled for the client
AND
User has been granted access to that module by an admin
AND
User has the required role or permission
```

## Why This Approach

This approach allows MediFlow to be sold as different products without splitting the codebase.

Examples:

- A pharmacy receives only pharmacy-related features.
- A clinic receives patient records, doctor, nurse, clinical care, and billing.
- A diagnostic center receives patient records, billing, lab, radiology, and reports.
- A maternity center receives patient records, antenatal care, labour, delivery, newborn care, lab, and billing.
- A full hospital receives all operational modules.

The same application is installed, but the active license controls what is visible and usable.

## Proposed Sellable Modules

### 1. Core System

Required for every installation.

Includes:

- Login and authentication
- Main dashboard shell
- User profile
- Basic application settings
- Shared layout and navigation

### 2. User & Access Control

Includes:

- Users
- Roles
- Permissions
- Temporary permissions
- Module access assignment
- User access per enabled module

This can be included in most plans, but full access should normally be restricted to administrator users.

## Module User Access

Every sellable module should have an admin-controlled user access list.

This means the license controls what the organization bought, while the admin controls who inside the organization can use each purchased module.

Example:

```text
Hospital Plan includes:
- Patient Records
- Clinical Care
- Pharmacy
- Billing
- Finance

Admin assigns:
- Nurse A -> Patient Records, Clinical Care
- Doctor B -> Patient Records, Clinical Care
- Pharmacist C -> Pharmacy, Billing
- Accountant D -> Billing, Finance
```

Even though the hospital license includes Pharmacy, Nurse A should not see Pharmacy unless the admin also gives Nurse A access to the Pharmacy module.

Recommended access flow:

```text
License enables module for the client
Admin grants module access to user
Role/permission grants actions inside the module
```

Recommended table:

```text
module_user_access
- id
- user_id
- license_module
- granted_by
- starts_at
- expires_at
- is_active
- created_at
- updated_at
```

Alternative table name:

```text
user_modules
```

This table should be used for broad module entry access. The existing permissions should still control detailed actions such as create, update, delete, approve, print, and export.

Example:

```text
User has pharmacy module access
AND user has medicine.read
= user can see Medicines

User has pharmacy module access
BUT user does not have medicine_stock.read
= user cannot see Pharmacy Stock

User has medicine.read
BUT user does not have pharmacy module access
= user cannot see Medicines
```

### 3. Patient Records

Includes:

- Patient registration
- Patient demographics
- Next of kin
- Patient search
- Patient history
- Patient visits
- Patient register exports
- Walk-in patients

This is a core business module for clinics, hospitals, maternity centers, and diagnostic centers.

### 4. Clinical Care

Includes:

- Vital signs
- Observations
- Diagnoses
- Continuation notes
- Admissions
- Discharges
- Prescriptions
- Drug charts
- Fluid balance
- Investigation requests

This module supports doctor and nurse workflows.

### 5. Doctor Workspace

Includes:

- Doctor patient list
- Patient review
- Clinical record index
- Prescriptions
- Investigation requests
- Visit closure

Depends on:

- Patient Records
- Clinical Care

### 6. Nursing Workspace

Includes:

- Nurse dashboard
- Vital signs queue
- Patient monitoring
- Admission monitoring
- SAMA and absconded admission actions
- Nursing clinical records

Depends on:

- Patient Records
- Clinical Care

### 7. Maternity / Midwife

Includes:

- Antenatal care
- Labour
- Labour progress
- Delivery
- Newborn records
- Newborn examination
- Postnatal examination
- Child follow-up
- Maternal medication

Depends on:

- Patient Records

Optional dependencies:

- Laboratory
- Billing
- Pharmacy

### 8. Laboratory

Includes:

- Lab investigations
- Investigation parameters
- Lab requests
- Result entry
- Result viewing
- Result printing

Depends on:

- Patient Records

Optional dependency:

- Billing, if tests must be paid for before processing.

### 9. Radiology

Includes:

- Radiology investigation requests
- Radiology result entry
- Result editing
- Result images/files
- Radiology investigation setup
- Radiology parameters

Depends on:

- Patient Records

Optional dependency:

- Billing

### 10. Pharmacy

Includes:

- Medicines
- Medicine types
- Suppliers
- Medicine batches
- Stock inventory
- Stock transactions
- Prescription dispensing
- Expiry tracking
- Stock reconciliation
- Pharmacy finance reports

Optional dependencies:

- Patient Records, if dispensing patient prescriptions.
- Billing, if pharmacy payments are processed in the system.

### 11. Billing

Includes:

- Bills
- Bill services
- Bill investigations
- Payment verification
- Receipts
- Walk-in billing
- Insurance billing

This should be separate from Finance because some small clients may only need bills and receipts, not full accounting.

### 12. Finance

Includes:

- Payments
- Revenues
- Revenue categories
- Expenses
- Expense categories
- Salary payments
- Financial reports
- Payment reports

Depends on:

- Billing

### 13. Department Management

Includes:

- Departments
- Department users
- Department investigations
- Consumables
- Consumable stock
- Consumable usage
- Department reports

Best suited for larger hospitals.

### 14. Ward & Bed Management

Includes:

- Wards
- Beds
- Bed availability
- Admission bed assignment
- Admission oversight

Depends on:

- Clinical Care

### 15. Reports

Includes:

- My activity report
- Activity reports
- Payment reports
- Finance reports
- Patient register reports
- Department reports
- PDF and Excel exports

Reports should respect enabled modules. For example, a Diagnostic Annex should not see Pharmacy reports unless Pharmacy is enabled.

### 16. Medical Director

Includes:

- Management dashboard
- Patient register oversight
- Statistics reports
- Admission oversight
- Revenue and expense visibility
- Department overview

Best suited for hospital and enterprise plans.

### 17. Audit Trail

Includes:

- Audit logs
- User activity history
- Activity reports
- Sensitive action tracking

This may be included in all plans for safety, or sold as a compliance add-on.

### 18. Data Synchronization

Includes:

- Sync operations
- Sync conflicts
- Sync API
- Sync dashboard
- Offline/local server synchronization

This should be treated as a premium module, especially for multi-branch or online/offline deployments.

### 19. Backup & Maintenance

Includes:

- Database backup
- System update
- Server utilities
- WiFi sharing/status tools

This is mainly for technical administrators or enterprise support.

## Recommended Plans

### Pharmacy Plan

For standalone pharmacy businesses.

Enabled modules:

- Core System
- User & Access Control
- Pharmacy
- Billing
- Reports
- Backup & Maintenance, optional

### Clinic Plan

For small clinics.

Enabled modules:

- Core System
- User & Access Control
- Patient Records
- Clinical Care
- Doctor Workspace
- Nursing Workspace
- Billing
- Reports, optional

### Diagnostic Annex Plan

For laboratories, scan centers, and diagnostic units.

Enabled modules:

- Core System
- User & Access Control
- Patient Records
- Billing
- Laboratory
- Radiology
- Reports

### Maternity Plan

For maternity centers.

Enabled modules:

- Core System
- User & Access Control
- Patient Records
- Maternity / Midwife
- Laboratory
- Billing
- Reports
- Pharmacy, optional

### Hospital Plan

For full hospital operations.

Enabled modules:

- Core System
- User & Access Control
- Patient Records
- Clinical Care
- Doctor Workspace
- Nursing Workspace
- Maternity / Midwife
- Laboratory
- Radiology
- Pharmacy
- Billing
- Finance
- Department Management
- Ward & Bed Management
- Reports
- Audit Trail

### Enterprise Plan

For large hospitals, multi-branch hospitals, and installations requiring advanced support.

Enabled modules:

- All Hospital Plan modules
- Data Synchronization
- Backup & Maintenance
- Advanced audit/compliance features
- Multi-branch or online/offline support where required

## Plan Matrix

| Module | Pharmacy | Clinic | Diagnostic | Maternity | Hospital | Enterprise |
| --- | --- | --- | --- | --- | --- | --- |
| Core System | Yes | Yes | Yes | Yes | Yes | Yes |
| User & Access Control | Yes | Yes | Yes | Yes | Yes | Yes |
| Patient Records | Optional | Yes | Yes | Yes | Yes | Yes |
| Clinical Care | No | Yes | No | Optional | Yes | Yes |
| Doctor Workspace | No | Yes | No | Optional | Yes | Yes |
| Nursing Workspace | No | Yes | No | Optional | Yes | Yes |
| Maternity / Midwife | No | No | No | Yes | Yes | Yes |
| Laboratory | No | Optional | Yes | Yes | Yes | Yes |
| Radiology | No | Optional | Yes | No | Yes | Yes |
| Pharmacy | Yes | Optional | No | Optional | Yes | Yes |
| Billing | Yes | Yes | Yes | Yes | Yes | Yes |
| Finance | Optional | Optional | Optional | Optional | Yes | Yes |
| Department Management | No | No | Optional | No | Yes | Yes |
| Ward & Bed Management | No | Optional | No | Optional | Yes | Yes |
| Reports | Yes | Optional | Yes | Yes | Yes | Yes |
| Medical Director | No | No | No | Optional | Yes | Yes |
| Audit Trail | Optional | Optional | Optional | Optional | Yes | Yes |
| Data Synchronization | No | No | Optional | Optional | Optional | Yes |
| Backup & Maintenance | Optional | Optional | Optional | Optional | Yes | Yes |

## Technical Implementation Plan

### Step 1: Create Module Configuration

Create a config file:

```text
config/mediflow_modules.php
```

Example:

```php
return [
    'plans' => [
        'pharmacy' => [
            'core',
            'access_control',
            'pharmacy',
            'billing',
            'reports',
        ],

        'clinic' => [
            'core',
            'access_control',
            'patient_records',
            'clinical_care',
            'doctor',
            'nursing',
            'billing',
        ],

        'diagnostic' => [
            'core',
            'access_control',
            'patient_records',
            'billing',
            'laboratory',
            'radiology',
            'reports',
        ],

        'maternity' => [
            'core',
            'access_control',
            'patient_records',
            'maternity',
            'laboratory',
            'billing',
            'reports',
        ],

        'hospital' => [
            'core',
            'access_control',
            'patient_records',
            'clinical_care',
            'doctor',
            'nursing',
            'maternity',
            'laboratory',
            'radiology',
            'pharmacy',
            'billing',
            'finance',
            'department_management',
            'wards_beds',
            'reports',
            'audit',
        ],

        'enterprise' => ['*'],
    ],
];
```

### Step 2: Create License Tables

Recommended tables:

```text
client_licenses
client_enabled_modules
```

`client_licenses` should store:

- Client name
- Plan name
- License key
- Start date
- Expiry date
- Active status

`client_enabled_modules` should store:

- License ID
- Module name
- Enabled/disabled status

The `client_enabled_modules` table allows manual add-ons. For example, a Clinic client may pay extra for Pharmacy.

### Step 3: Create License Models

Recommended models:

```text
app/Models/ClientLicense.php
app/Models/ClientEnabledModule.php
```

Relationships:

```php
ClientLicense hasMany ClientEnabledModule
ClientEnabledModule belongsTo ClientLicense
```

### Step 4: Create LicenseService

Create:

```text
app/Services/LicenseService.php
```

The service should expose methods like:

```php
currentLicense()
currentPlan()
enabledModules()
moduleEnabled(string $module): bool
userHasModuleAccess(User $user, string $module): bool
licenseIsActive(): bool
```

Example:

```php
public function moduleEnabled(string $module): bool
{
    $license = $this->currentLicense();

    if (! $license || ! $license->is_active || $license->expires_at < now()) {
        return false;
    }

    $planModules = config("mediflow_modules.plans.{$license->plan}", []);

    if (in_array('*', $planModules)) {
        return true;
    }

    if (in_array($module, $planModules)) {
        return true;
    }

    return $license->enabledModules()
        ->where('module_name', $module)
        ->where('is_enabled', true)
        ->exists();
}
```

Add a user module access method:

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

The administrator bypass is optional. If the business wants even administrators to be restricted by module assignment, remove that condition.

### Step 5: Create Module Middleware

Create middleware:

```bash
php artisan make:middleware EnsureModuleEnabled
```

Middleware behavior:

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

Register the middleware alias in `bootstrap/app.php`:

```php
'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
```

### Step 6: Protect Route Groups

Every module route group must have module middleware. Hiding the menu is not enough because users can type URLs manually.

Examples:

```php
Route::prefix('pharmacy')
    ->middleware(['auth', 'verified', 'module:pharmacy'])
    ->name('pharmacy.')
    ->group(function () {
        // Pharmacy routes
    });
```

```php
Route::middleware(['auth', 'verified', 'module:laboratory'])
    ->prefix('lab')
    ->name('lab.')
    ->group(function () {
        // Lab routes
    });
```

Suggested route-to-module mapping:

| Route File | Module Middleware |
| --- | --- |
| `routes/patient.php` | `module:patient_records` |
| `routes/doctor.php` | `module:doctor` |
| `routes/nurse.php` | `module:nursing` |
| `routes/midwife.php` | `module:maternity` |
| `routes/lab.php` | `module:laboratory` |
| `routes/radiology.php` | `module:radiology` |
| `routes/pharmacy.php` | `module:pharmacy` |
| `routes/department.php` | `module:department_management` |
| `routes/reports.php` | `module:reports` |
| Finance routes in `routes/web.php` | `module:finance` |
| Accountant/billing routes in `routes/web.php` | `module:billing` |
| Sync routes in `routes/api.php` | `module:synchronization` |

### Step 7: Hide Disabled Menus

The sidebar and navigation should only show modules enabled by the license.

Example:

```blade
@if(app(\App\Services\LicenseService::class)->moduleEnabled('pharmacy'))
    <a href="{{ route('pharmacy.transactions.index') }}">Pharmacy</a>
@endif
```

Better long-term option:

Create a helper such as:

```php
module_enabled('pharmacy')
```

Then Blade becomes:

```blade
@if(module_enabled('pharmacy'))
    <a href="{{ route('pharmacy.transactions.index') }}">Pharmacy</a>
@endif
```

### Step 8: Connect License Modules To Existing Modules Table

The system already has a `modules` table and module permissions.

Add a column to `modules`:

```text
license_module
```

Example mapping:

| Existing Module Name | License Module |
| --- | --- |
| `record_patients` | `patient_records` |
| `doctor_patients` | `doctor` |
| `nurse_patients` | `nursing` |
| `midwife_patients` | `maternity` |
| `vital_signs` | `clinical_care` |
| `observations` | `clinical_care` |
| `investigation_requests` | `clinical_care` |
| `lab_requests` | `laboratory` |
| `lab_results` | `laboratory` |
| `bills` | `billing` |
| `payments` | `billing` |
| `revenues` | `finance` |
| `expenses` | `finance` |
| `data_sync` | `synchronization` |
| `access_control` | `access_control` |
| `departments` | `department_management` |
| `admin_wards` | `wards_beds` |
| `beds` | `wards_beds` |

When building menus, filter by:

```text
User role/permission
AND
Module license_module is enabled
```

### Step 9: Receive Central Activation Data

License activation is managed by the centralized MediFlow platform, not inside the local hospital app. The local app should receive or sync:

- Client name
- Current plan
- License key
- License start date
- License expiry date
- Active/inactive status
- Enabled add-on modules
- Users attached to each enabled module

Also create a module access screen for client admins:

```text
Administration -> Module Access
```

This screen should allow admins to:

- Select an enabled module.
- View users who currently have access to that module.
- Add users to the module.
- Remove users from the module.
- Optionally set start and expiry dates for module access.
- Assign recommended roles/permissions for that module.

Example:

```text
Module: Pharmacy
Users:
- Grace Okoro, Pharmacist, Active
- Musa Bello, Head of Department, Active

Available users:
- Ada Nurse
- Chinedu Accountant
- Tola Doctor
```

When a user is added to a module, the admin should still assign the correct role or permissions. Module access alone should not grant every action.

### Step 10: Enforce Module Dependencies

Some modules require other modules to work properly.

Suggested dependencies:

| Module | Requires |
| --- | --- |
| Doctor Workspace | Patient Records, Clinical Care |
| Nursing Workspace | Patient Records, Clinical Care |
| Maternity / Midwife | Patient Records |
| Laboratory | Patient Records |
| Radiology | Patient Records |
| Pharmacy | None for standalone stock, Patient Records for prescriptions |
| Finance | Billing |
| Ward & Bed Management | Clinical Care |
| Medical Director | Reports, Patient Records |
| Data Synchronization | Core System |

The license setup should prevent invalid combinations where possible.

## Implementation Order

Recommended order:

1. Define plan and module names.
2. Create `config/mediflow_modules.php`.
3. Create license migrations and models.
4. Create `LicenseService`.
5. Create `module_user_access` or `user_modules`.
6. Add `userHasModuleAccess()` to `LicenseService`.
7. Create and register `EnsureModuleEnabled` middleware.
8. Apply module middleware to route groups.
9. Hide disabled modules from menus.
10. Add `license_module` to the existing `modules` table.
11. Update module seeders with license module mapping.
12. Connect local activation data to the centralized platform.
13. Create module user access screen.
14. Add dependency validation.
15. Test each plan by changing the active license.

## Testing Checklist

For each plan, test:

- The correct dashboard loads.
- Disabled modules do not appear in the sidebar.
- Disabled module URLs return `403`.
- Enabled module URLs work normally.
- Enabled modules remain hidden from users who were not assigned to those modules.
- Users assigned to a module only see the actions allowed by their permissions.
- User permissions still work inside enabled modules.
- Reports only show data for enabled modules.
- Expired licenses block paid modules.
- Add-on modules work when enabled manually.

## Important Rules

- Do not split MediFlow into separate apps.
- Do not rely only on hiding menus.
- Protect all module routes with middleware.
- Do not treat module access as permission to do everything.
- Keep RBAC for user-level permissions.
- Use licensing for client-level module access.
- Use module-user access for admin-controlled user access per enabled module.
- Keep module names consistent across config, database, routes, and menus.
- Treat synchronization, audit, backup, and enterprise support as premium modules.

## Final Architecture

```text
MediFlow Codebase
    |
    |-- License Plan
    |      |
    |      |-- Enables modules for the client
    |
    |-- Enabled Modules
    |      |
    |      |-- Controls client route/menu/module availability
    |
    |-- Module User Access
    |      |
    |      |-- Controls which users can enter each enabled module
    |
    |-- Roles and Permissions
           |
           |-- Controls what each user can do inside available modules
```

This gives MediFlow a flexible product structure while keeping development and maintenance centralized in one codebase.
