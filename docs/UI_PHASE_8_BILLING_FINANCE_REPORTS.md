# Phase 8 Modern UI Migration: Billing, Finance, And Reports

## Scope

Phase 8 moved the billing, payment, finance, expense, revenue, and reporting routes onto the modern UI shell.

## Completed Work

### Admin Finance Workspaces

Updated these primary Livewire workspaces:

- `app/Livewire/Admin/BillManagement.php`
- `resources/views/components/admin/bill-management.blade.php`
- `app/Livewire/Admin/PaymentManagement.php`
- `resources/views/components/admin/payment-management.blade.php`
- `app/Livewire/Admin/ExpenseManagement.php`
- `resources/views/components/admin/expense-management.blade.php`
- `app/Livewire/Admin/RevenueManagement.php`
- `resources/views/components/admin/revenue-management.blade.php`

Preserved bill filters, payment filters, totals, PDF links, receipts, edit/delete/reverse actions, category management links, sorting, and pagination.

### Accountant Workspaces

Updated these primary Livewire workspaces:

- `app/Livewire/Accountant/BillWorkspace.php`
- `resources/views/components/accountant/bill-workspace.blade.php`
- `app/Livewire/Accountant/PaymentWorkspace.php`
- `resources/views/components/accountant/payment-workspace.blade.php`

Preserved today/unpaid/deleted bill modes, registered and walk-in billing, service/investigation rows, bill restore and soft delete, payment bill lookup, batch bill selection, selected payment amounts, receipt number generation, receipt printing, edit, reverse, filters, pagination, and polling refresh.

### Controller Views

Moved the remaining Phase 8 controller views from `layouts.app` to `layouts.modern` and added page metadata where missing.

Updated route surfaces include:

- `resources/views/admin/bill/**`
- `resources/views/admin/expenses/**`
- `resources/views/admin/revenues/**`
- `resources/views/admin/finances/index.blade.php`
- `resources/views/accountant/**`
- `resources/views/bills/**`
- `resources/views/payments/**`
- `resources/views/reports/**`
- `resources/views/department/expenses/**`
- `resources/views/department/reports/**`

The primary active workspaces use modern components. Some legacy/controller pages still contain older internal utility classes, but they now render inside the modern shell and no longer use the old global layout.

## Verification

Run after this phase:

```bash
php artisan route:list --name=admin --except-vendor
php artisan route:list --name=accountant --except-vendor
php artisan route:list --name=reports --except-vendor
php artisan view:cache
php artisan view:clear
```

Also lint compiled Blade PHP files after `view:cache` to catch generated syntax issues.

## Known Remaining Old UI Outside Phase 8

A full repository sweep still shows old layout references in areas outside Phase 8 or legacy duplicate routes from earlier phases:

- Global dashboard fallback.
- Staff result/report screens.
- Department stock and consumable screens.
- Some old doctor/nurse/patient duplicate clinical Blade routes.
- Admin roles, permissions, categories, salaries, staff reports, and patient report fallbacks.
- Medical director statistics/admissions.
- Admin file types and department consumable Livewire components.

Handle these in the final cleanup phase after confirming which routes are still active in the sidebar and licensing model.
