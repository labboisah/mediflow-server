# MediFlow Sidebar Grouping Implementation

## Overview

The sidebar should not be grouped directly by role names or by sellable license modules. That makes the system feel technical and less professional.

The sidebar should be grouped by how hospital staff think about their work:

- Dashboard
- Patients
- Clinical
- Medications
- Diagnostics
- Maternity
- Billing
- Payments & Finance
- Wards & Admissions
- Departments & Inventory
- Reports
- Administration
- System

The license still controls what feature areas are available, but the sidebar should present those features in clean workflow groups.

## Current Problem

The current sidebar in:

```text
resources/views/layouts/partials/admin-sidebar.blade.php
```

builds menu sections mainly from user roles:

```text
Administrator
Doctor
Nurse
Midwife
Pharmacist
Accountant
Head of Department
```

This works technically, but it creates a menu that feels like internal system structure instead of a polished hospital workflow.

For example, a user with multiple roles may see repeated or scattered items:

- Patients under Record Officer
- Patients under Doctor
- Patients under Nurse
- Prescriptions under Doctor
- Prescriptions under Pharmacy
- Finance under Accountant
- Finance under Pharmacy

The better approach is to render the sidebar from sidebar groups and sidebar items.

## Core Rule

Every sidebar item should be visible only when all checks pass:

```text
1. The item's license module is enabled for the client.
2. The item's route exists.
3. The logged-in user has admin-granted access to that license module.
4. The logged-in user has the required role or permission.
5. Any department-specific rule passes.
```

Then the sidebar group should be shown only if it has at least one visible item.

This means if the client has the Medications group available but a user only has one medication permission, the user should see:

```text
Medications
    Prescriptions
```

The user should not see medicine stock, batches, expiry alerts, or reconciliation unless they have those permissions.

## License Modules vs Sidebar Groups

These are different concepts.

### License Module

Controls whether the client paid for a feature area.

Examples:

- `patient_records`
- `clinical_care`
- `pharmacy`
- `laboratory`
- `radiology`
- `maternity`
- `billing`
- `finance`
- `reports`
- `department_management`
- `wards_beds`
- `audit`
- `synchronization`

### Sidebar Group

Controls where the item appears in the user interface.

Examples:

- `patients`
- `clinical`
- `medications`
- `diagnostics`
- `maternity`
- `billing`
- `payments_finance`
- `wards_admissions`
- `departments_inventory`
- `reports`
- `administration`
- `system`

Example:

```text
Route: pharmacy.medicines.index
License module: pharmacy
Sidebar group: medications
```

This means the feature is sold as part of Pharmacy, but appears professionally under Medications.

## Module User Access

The sidebar must also respect admin-controlled module access.

The license answers:

```text
Did the client buy this module?
```

Module user access answers:

```text
Did the admin allow this user to enter this module?
```

Permissions answer:

```text
What can this user do inside the module?
```

Example:

```text
Client license includes Pharmacy.
Admin gives Pharmacy access to User A.
User A has medicine.read only.
```

Sidebar result:

```text
Medications
    Medicines
```

The user should not see:

```text
Prescriptions
Stock Transactions
Stock Reconciliation
Expiry Alerts
```

because those require other permissions.

Another example:

```text
Client license includes Pharmacy.
User B has medicine.read permission.
Admin did not give User B Pharmacy module access.
```

Sidebar result:

```text
No Pharmacy/Medication pharmacy items should show.
```

This prevents permissions alone from accidentally exposing a module the admin did not assign to that user.

## Recommended Sidebar Groups

### 1. Dashboard

Items:

- Dashboard
- Admin Dashboard
- Medical Director Dashboard
- Role-specific dashboard where needed

Example routes:

```text
dashboard
admin.index
medical-director.index
```

### 2. Patients

Items:

- Patient List
- Register Patient
- Patient Search
- Patient Register
- Patient History
- Patient Summary
- Export Patient Record

Example routes:

```text
record.patients.index
record.patients.register.form
record.patients.search
record.patient-register.index
patient.index
patient.search
admin.patient-register.index
medical-director.patient-register.index
```

### 3. Clinical

Items:

- Vital Signs
- Observations
- Continuation Notes
- Admissions
- Discharges
- Fluid Balance
- Clinical Records
- Investigation Requests

Example routes:

```text
patient.vitalsign.create
patient.observation.record
patient.continuation.create
patient.admission.create
patient.discharge.create
patient.fluidbalance.record
doctor.clinicals.vital-signs
doctor.clinicals.observations
doctor.clinicals.investigations
doctor.clinicals.admissions
doctor.clinicals.continuations
doctor.clinicals.fluid-balances
nurse.clinicals.vital-signs
nurse.clinicals.observations
nurse.clinicals.investigations
nurse.clinicals.fluid-balances
nurse.admissions.index
```

### 4. Medications

Items:

- Prescriptions
- Drug Chart
- Prescription Dispensing
- Medicines
- Medicine Batches
- Pharmacy Stock
- Stock Transactions
- Stock Reconciliation
- Expiry Tracking

Example routes:

```text
patient.prescription.create
patient.prescription.show
patient.drugchart.record
doctor.clinicals.prescriptions
doctor.clinicals.drug-charts
nurse.clinicals.drug-charts
pharmacy.prescriptions.index
pharmacy.prescriptions.show
pharmacy.medicines.index
pharmacy.batches.index
pharmacy.stocks.index
pharmacy.stocks.reconciliation
pharmacy.transactions.index
pharmacy.transactions.create
pharmacy.expiries.index
```

### 5. Diagnostics

Items:

- Investigation Requests
- Lab Requests
- Lab Results Entry
- Lab Investigations
- Lab Parameters
- Radiology Requests
- Radiology Results
- Radiology Investigations
- Radiology Parameters

Example routes:

```text
lab.requests.index
lab.result
lab.investigations.index
lab.investigations.parameters.index
radiology.requests.index
radiology.requests.createResult
radiology.requests.editResult
radiology.investigations.index
radiology.investigations.parameters.index
```

### 6. Maternity

Items:

- Maternity Patients
- Antenatal Care
- Labour
- Labour Progress
- Delivery
- Newborns
- Newborn Examination
- Postnatal Examination
- Child Follow-up
- Maternal Medications

Example routes:

```text
midwife.patient.index
midwife.anc-management
midwife.antenatal.index
midwife.labour-management
midwife.labour.index
midwife.labour.progress.index
midwife.delivery-management
midwife.delivery.index
midwife.newborn-management
midwife.newborn.index
midwife.newborn-examination.index
midwife.postnatal-management
midwife.postnatal-examination.index
midwife.child-follow-up-management
midwife.child-follow-up.index
midwife.medications.index
```

### 7. Billing

Items:

- Bills
- Create Bill
- Unpaid Bills
- Deleted Bills
- Verify Payment
- Receipts
- Walk-in Billing
- Insurance Billing

Example routes:

```text
accountant.bills.index
accountant.bills.create
accountant.bills.unpaid
accountant.bills.deleted
accountant.bills.payments.verify
accountant.payments.receipt
accountant.bills.create-walkin
accountant.insurance-billing
admin.bills.index
finance.bills.index
```

### 8. Payments & Finance

Items:

- Payments
- Payment History
- Revenues
- Expenses
- Revenue Categories
- Expense Categories
- Financial Report
- Payment Report
- Pharmacy Finance

Example routes:

```text
accountant.payments.index
accountant.patient-payment-history
finance.payments.index
finance.expenses.index
finance.revenues.index
admin.payments.index
admin.expenses.index
admin.revenues.index
admin.expense-categories.index
admin.revenue-categories.index
reports.finance.index
reports.payments.index
pharmacy.finance.bills
pharmacy.finance.payments
pharmacy.finance.report
```

### 9. Wards & Admissions

Items:

- Admissions
- Wards
- Beds
- Bed Management
- Discharge/SAMA Oversight

Example routes:

```text
admin.admissions.index
medical-director.admissions.index
nurse.admissions.index
admin.wards.index
admin.beds.index
medical-director.wards.index
medical-director.beds.index
```

### 10. Departments & Inventory

Items:

- Departments
- Department Users
- Department Investigations
- Consumables
- Consumable Stock
- Stock Usage
- Department Reports

Example routes:

```text
admin.departments.index
medical-director.departments.index
department.users.index
department.investigations.index
department.consumables.index
department.stocks.index
department.stock-usage.index
department.reports.index
```

### 11. Reports

Items:

- My Activities
- Activity Reports
- Patient Register Report
- Financial Report
- Payment Report
- Department Report
- Statistics Report

Example routes:

```text
reports.my-activities.index
reports.activities.show
reports.finance.index
reports.payments.index
record.patient-register.index
admin.patient-register.index
medical-director.statistics.index
department.reports.index
```

### 12. Administration

Items:

- Users
- Roles
- Permissions
- Access Control
- Services
- Investigations Setup
- File Types
- Temporary Permissions

Example routes:

```text
admin.users.index
admin.roles.index
admin.permissions.index
admin.access-control
admin.services.index
admin.investigations.index
admin.file-types.index
admin.temporary-permissions.index
```

### 13. System

Items:

- Backup
- Sync Dashboard
- System Update
- WiFi Sharing
- License Management

Example routes:

```text
admin.backup.index
admin.sync.index
admin.sync.dashboards
admin.system.update
wifi-sharing.status
wifi-sharing.connect
```

## Required Database Changes

The existing `modules` table should be extended so each module can be rendered in the sidebar.

Add these columns:

```text
license_module
sidebar_group
sidebar_label
sidebar_icon
sidebar_route
sidebar_patterns
sort_order
is_sidebar_visible
```

Recommended meaning:

| Column | Purpose |
| --- | --- |
| `license_module` | The license feature that must be enabled for this item |
| `sidebar_group` | The professional UI group where this item appears |
| `sidebar_label` | Text displayed in the sidebar |
| `sidebar_icon` | Bootstrap icon class |
| `sidebar_route` | Main named route for the item |
| `sidebar_patterns` | Route patterns used to mark active state |
| `sort_order` | Item order inside its group |
| `is_sidebar_visible` | Whether this module should appear in the sidebar |

If preferred, the existing columns can be reused:

```text
label -> sidebar_label
route -> sidebar_route
icon -> sidebar_icon
group -> sidebar_group
```

But `license_module` should still be added.

Add a module-user access table:

```text
module_user_access
```

Recommended columns:

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

This table controls whether a user is attached to a module. It should not replace roles and permissions.

Example:

```text
license_module: pharmacy
user_id: 15
granted_by: 1
is_active: true
```

That means user 15 is allowed to enter the Pharmacy module if the client license also includes Pharmacy.

## Sidebar Group Config

Create:

```text
config/sidebar.php
```

Example:

```php
return [
    'groups' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'icon' => 'bi-speedometer2',
            'sort' => 1,
        ],
        'patients' => [
            'label' => 'Patients',
            'icon' => 'bi-people',
            'sort' => 2,
        ],
        'clinical' => [
            'label' => 'Clinical',
            'icon' => 'bi-heart-pulse',
            'sort' => 3,
        ],
        'medications' => [
            'label' => 'Medications',
            'icon' => 'bi-capsule',
            'sort' => 4,
        ],
        'diagnostics' => [
            'label' => 'Diagnostics',
            'icon' => 'bi-clipboard2-pulse',
            'sort' => 5,
        ],
        'maternity' => [
            'label' => 'Maternity',
            'icon' => 'bi-gender-female',
            'sort' => 6,
        ],
        'billing' => [
            'label' => 'Billing',
            'icon' => 'bi-receipt',
            'sort' => 7,
        ],
        'payments_finance' => [
            'label' => 'Payments & Finance',
            'icon' => 'bi-cash-stack',
            'sort' => 8,
        ],
        'wards_admissions' => [
            'label' => 'Wards & Admissions',
            'icon' => 'bi-hospital',
            'sort' => 9,
        ],
        'departments_inventory' => [
            'label' => 'Departments & Inventory',
            'icon' => 'bi-building',
            'sort' => 10,
        ],
        'reports' => [
            'label' => 'Reports',
            'icon' => 'bi-bar-chart',
            'sort' => 11,
        ],
        'administration' => [
            'label' => 'Administration',
            'icon' => 'bi-shield-lock',
            'sort' => 12,
        ],
        'system' => [
            'label' => 'System',
            'icon' => 'bi-gear',
            'sort' => 13,
        ],
    ],
];
```

## Sidebar Item Data Structure

Each sidebar item should have this structure:

```php
[
    'label' => 'Prescriptions',
    'icon' => 'bi-prescription2',
    'route' => 'pharmacy.prescriptions.index',
    'patterns' => ['pharmacy.prescriptions.*'],
    'license_module' => 'pharmacy',
    'sidebar_group' => 'medications',
    'permissions' => ['dispense.read', 'pharmacy_sale.read'],
    'roles' => ['pharmacist'],
    'sort_order' => 10,
]
```

## Permission Behavior

Permissions should be checked at item level, not group level.

Correct behavior:

```text
License has Medications features.
User has prescription.read only.
Sidebar shows Medications group with Prescriptions only.
```

Wrong behavior:

```text
License has Medications features.
User has one medication permission.
Sidebar shows all medication items.
```

The group should appear only because one child item is visible.

## SidebarService

Create:

```text
app/Services/SidebarService.php
```

Responsibilities:

- Load active sidebar items.
- Check license module access.
- Check user role/permission access.
- Check department restrictions.
- Remove inaccessible items.
- Group remaining items by sidebar group.
- Sort groups and items.
- Return data to the Blade view.

Example:

```php
namespace App\Services;

use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class SidebarService
{
    public function groupsFor(User $user): array
    {
        $license = app(LicenseService::class);
        $groupConfig = config('sidebar.groups', []);

        $items = Module::query()
            ->with('permissions')
            ->where('is_active', true)
            ->where('is_sidebar_visible', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(function (Module $module) use ($user, $license) {
                return $this->canShowItem($user, $module, $license);
            })
            ->groupBy('sidebar_group');

        return collect($groupConfig)
            ->sortBy('sort')
            ->map(function (array $group, string $groupKey) use ($items) {
                return [
                    'key' => $groupKey,
                    'label' => $group['label'],
                    'icon' => $group['icon'],
                    'items' => $items->get($groupKey, collect())->values(),
                ];
            })
            ->filter(fn (array $group) => $group['items']->isNotEmpty())
            ->values()
            ->all();
    }

    private function canShowItem(User $user, Module $module, LicenseService $license): bool
    {
        if (! $license->moduleEnabled($module->license_module)) {
            return false;
        }

        if (! $license->userHasModuleAccess($user, $module->license_module)) {
            return false;
        }

        if (! Route::has($module->route)) {
            return false;
        }

        $permissionNames = $module->permissions->pluck('name')->all();

        if (! empty($permissionNames) && $user->hasAnyPermission($permissionNames)) {
            return true;
        }

        return $module->roles()
            ->whereIn('name', $user->roles->pluck('name'))
            ->exists();
    }
}
```

Recommended `LicenseService` method:

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

The administrator bypass is a business decision. If the admin should also be explicitly attached to modules, remove the bypass.

## Blade Rendering

The sidebar Blade file should not contain a large hardcoded `$navigationItems` array.

Instead, it should render the output of `SidebarService`.

Example:

```blade
@php
    $sidebarGroups = app(\App\Services\SidebarService::class)->groupsFor(auth()->user());

    $isRouteActive = function (array $patterns) {
        return collect($patterns)->contains(fn ($pattern) => request()->routeIs($pattern));
    };
@endphp

@foreach($sidebarGroups as $group)
    <button class="admin-sidebar-toggle" type="button">
        <span>
            <i class="bi {{ $group['icon'] }}"></i>
            {{ $group['label'] }}
        </span>
    </button>

    <div class="admin-sidebar-submenu">
        @foreach($group['items'] as $item)
            <a class="admin-sidebar-link {{ $isRouteActive($item->sidebar_patterns ?? [$item->route]) ? 'active' : '' }}"
               href="{{ route($item->route) }}">
                <i class="bi {{ $item->icon }}"></i>
                {{ $item->label }}
            </a>
        @endforeach
    </div>
@endforeach
```

## Example Visibility Flow

### Example 1: Pharmacist on Pharmacy Plan

Client license enables:

```text
pharmacy
billing
reports
```

User permissions:

```text
dispense.read
medicine.read
medicine_stock.read
pharmacy_sale.read
expiry_alert.read
```

Admin-granted module access:

```text
pharmacy
billing
reports
```

Sidebar:

```text
Dashboard
Medications
    Prescriptions
    Medicines
    Pharmacy Stock
    Stock Transactions
    Expiry Alerts
Billing
    Bills
Payments & Finance
    Payments
Reports
    Transaction Report
```

### Example 2: Nurse on Hospital Plan

Client license enables:

```text
patient_records
clinical_care
nursing
wards_beds
reports
```

User permissions:

```text
patient.read
vital_sign.read
observation.read
admission.read
nursing_note.read
```

Admin-granted module access:

```text
patient_records
clinical_care
nursing
wards_beds
reports
```

Sidebar:

```text
Dashboard
Patients
    Patients
Clinical
    Vital Signs
    Observations
    Admissions
    Fluid Balance
Medications
    Drug Chart
Reports
    My Activities
```

### Example 3: Accountant on Hospital Plan

Client license enables:

```text
billing
finance
reports
```

User permissions:

```text
bill.read
payment.read
report.read
expense.read
revenue.read
```

Admin-granted module access:

```text
billing
finance
reports
```

Sidebar:

```text
Dashboard
Billing
    Bills
    Unpaid Bills
    Deleted Bills
Payments & Finance
    Payments
    Expenses
    Revenues
Reports
    Billing Report
    Payment Report
```

## Suggested Item Mapping

| Sidebar Item | Route | License Module | Sidebar Group | Permission |
| --- | --- | --- | --- | --- |
| Patients | `record.patients.index` | `patient_records` | `patients` | `patient.read` |
| Register Patient | `record.patients.register.form` | `patient_records` | `patients` | `patient.create` |
| Patient Register | `record.patient-register.index` | `patient_records` | `patients` | `patient.read` |
| Vital Signs | `nurse.clinicals.vital-signs` | `clinical_care` | `clinical` | `vital_sign.read` |
| Observations | `nurse.clinicals.observations` | `clinical_care` | `clinical` | `observation.read` |
| Admissions | `nurse.admissions.index` | `wards_beds` | `wards_admissions` | `admission.read` |
| Prescriptions | `doctor.clinicals.prescriptions` | `clinical_care` | `medications` | `prescription.read` |
| Drug Chart | `doctor.clinicals.drug-charts` | `clinical_care` | `medications` | `prescription.read` |
| Lab Requests | `lab.requests.index` | `laboratory` | `diagnostics` | `laboratory_request.read` |
| Results Entry | `lab.result` | `laboratory` | `diagnostics` | `laboratory_result.create` |
| Radiology Requests | `radiology.requests.index` | `radiology` | `diagnostics` | `radiology_request.read` |
| Antenatal Care | `midwife.antenatal.index` | `maternity` | `maternity` | `antenatal_care.read` |
| Labour | `midwife.labour.index` | `maternity` | `maternity` | `labour.read` |
| Delivery | `midwife.delivery.index` | `maternity` | `maternity` | `delivery.read` |
| Newborn | `midwife.newborn.index` | `maternity` | `maternity` | `newborn.read` |
| Pharmacy Prescriptions | `pharmacy.prescriptions.index` | `pharmacy` | `medications` | `dispense.read` |
| Medicines | `pharmacy.medicines.index` | `pharmacy` | `medications` | `medicine.read` |
| Pharmacy Stock | `pharmacy.stocks.index` | `pharmacy` | `medications` | `medicine_stock.read` |
| Expiry Alerts | `pharmacy.expiries.index` | `pharmacy` | `medications` | `expiry_alert.read` |
| Bills | `accountant.bills.index` | `billing` | `billing` | `bill.read` |
| Payments | `accountant.payments.index` | `billing` | `payments_finance` | `payment.read` |
| Expenses | `finance.expenses.index` | `finance` | `payments_finance` | `expense.read` |
| Revenues | `finance.revenues.index` | `finance` | `payments_finance` | `revenue.read` |
| Departments | `admin.departments.index` | `department_management` | `departments_inventory` | `department.read` |
| Consumables | `department.consumables.index` | `department_management` | `departments_inventory` | `consumable.read` |
| My Activities | `reports.my-activities.index` | `reports` | `reports` | `activity.read` |
| Access Control | `admin.access-control` | `access_control` | `administration` | `role.read` |
| Users | `admin.users.index` | `access_control` | `administration` | `user.read` |
| Services | `admin.services.index` | `billing` | `administration` | `service.read` |
| Data Sync | `admin.sync.index` | `synchronization` | `system` | `sync.read` |
| System Update | `admin.system.update` | `maintenance` | `system` | `system.update` |
| Backup | `admin.backup.index` | `maintenance` | `system` | `backup.create` |

## Implementation Order

1. Create `config/sidebar.php`.
2. Add sidebar-related columns to the `modules` table.
3. Update `App\Models\Module` fillable fields.
4. Update `ModulePermissionSeeder` to include `license_module` and `sidebar_group`.
5. Create `module_user_access` or `user_modules`.
6. Add a `moduleAccess()` relationship to `User`.
7. Add `userHasModuleAccess()` to `LicenseService`.
8. Create `SidebarService`.
9. Move hardcoded sidebar item logic out of `admin-sidebar.blade.php`.
10. Render sidebar groups from `SidebarService`.
11. Ensure route protection still uses module middleware.
12. Add an admin screen for attaching users to enabled modules.
13. Test each plan, module assignment, and role combination.

## Testing Checklist

For every role and plan combination, verify:

- Disabled license modules do not appear in the sidebar.
- Disabled license module URLs are blocked by middleware.
- Enabled license modules do not appear for users who are not attached to those modules.
- Attached users only see the specific items allowed by their permissions.
- A group with zero visible items does not show.
- A group with one visible item does show.
- Users only see items they have permission for.
- Temporary permissions can expose only the matching item.
- Department-specific rules still work for pharmacy, lab, radiology, and HOD users.
- Active route highlighting works for each item.
- Duplicate routes do not appear twice.

## Final Design Principle

The sidebar should be built from visible actions, not from roles.

Use this flow:

```text
Load all active sidebar items
Filter by client license
Filter by admin-granted user module access
Filter by user permission
Filter by department rules
Remove duplicate routes
Group by professional sidebar group
Remove empty groups
Render the sidebar
```

This gives MediFlow a clean, professional sidebar while still respecting plans, modules, roles, and permissions.
