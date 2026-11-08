<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Bill;
use App\Models\Payment;

// Home / Dashboard
Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', route('dashboard'));
});

// Profile
Breadcrumbs::for('profile.edit', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Profile', route('profile.edit'));
});

// Patients
Breadcrumbs::for('patients.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Patients', route('patients.index'));
});

Breadcrumbs::for('patients.create', function (BreadcrumbTrail $trail) {
    $trail->parent('patients.index');
    $trail->push('New Patient', route('patients.create'));
});

// Records
Breadcrumbs::for('records.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Records', route('records.index'));
});

Breadcrumbs::for('records.create', function (BreadcrumbTrail $trail) {
    $trail->parent('records.index');
    $trail->push('New Record', route('records.create'));
});

// Admin Dashboard
Breadcrumbs::for('admin.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Admin', route('admin.index'));
});

// Admin Roles
Breadcrumbs::for('admin.roles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.index');
    $trail->push('Roles', route('admin.roles.index'));
});

Breadcrumbs::for('admin.roles.create', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.roles.index');
    $trail->push('Create Role', route('admin.roles.create'));
});

Breadcrumbs::for('admin.roles.edit', function (BreadcrumbTrail $trail, Role $role) {
    $trail->parent('admin.roles.index');
    $trail->push("Edit: {$role->name}", route('admin.roles.edit', $role));
});

// Admin Permissions
Breadcrumbs::for('admin.permissions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.index');
    $trail->push('Permissions', route('admin.permissions.index'));
});

Breadcrumbs::for('admin.permissions.create', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.permissions.index');
    $trail->push('Create Permission', route('admin.permissions.create'));
});

Breadcrumbs::for('admin.permissions.edit', function (BreadcrumbTrail $trail, Permission $permission) {
    $trail->parent('admin.permissions.index');
    $trail->push("Edit: {$permission->name}", route('admin.permissions.edit', $permission));
});

// Admin Users
Breadcrumbs::for('admin.users.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.index');
    $trail->push('Users', route('admin.users.index'));
});

Breadcrumbs::for('admin.users.create', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.users.index');
    $trail->push('Create User', route('admin.users.create'));
});

Breadcrumbs::for('admin.users.show', function (BreadcrumbTrail $trail, User $user) {
    $trail->parent('admin.users.index');
    $trail->push($user->name, route('admin.users.show', $user));
});

Breadcrumbs::for('admin.users.edit', function (BreadcrumbTrail $trail, User $user) {
    $trail->parent('admin.users.index');
    $trail->push("Manage: {$user->name}", route('admin.users.edit', $user));
});

// Admin Temporary Permissions
Breadcrumbs::for('admin.temporary-permissions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.index');
    $trail->push('Temporary Permissions', route('admin.temporary-permissions.index'));
});

Breadcrumbs::for('admin.temporary-permissions.create', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.temporary-permissions.index');
    $trail->push('Grant Permission', route('admin.temporary-permissions.create'));
});

// ===== RECORD OFFICER ROUTES =====

// Record Officer Dashboard
Breadcrumbs::for('record_officer.dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('dashboard'));
    $trail->push('Record Officer', route('record_officer.dashboard'));
});

// Record Officer - Patient Management
Breadcrumbs::for('record_officer.patients.register.form', function (BreadcrumbTrail $trail) {
    $trail->parent('record_officer.dashboard');
    $trail->push('Patients', route('record_officer.patients.list'));
    $trail->push('Register New', route('record_officer.patients.register.form'));
});

Breadcrumbs::for('record_officer.patients.list', function (BreadcrumbTrail $trail) {
    $trail->parent('record_officer.dashboard');
    $trail->push('Patients', route('record_officer.patients.list'));
});

Breadcrumbs::for('record_officer.patients.show', function (BreadcrumbTrail $trail, Patient $patient) {
    $trail->parent('record_officer.dashboard');
    $trail->push('Patients', route('record_officer.patients.list'));
    $trail->push($patient->demographic->full_name ?? 'Patient', route('record_officer.patients.show', $patient));
});

Breadcrumbs::for('record_officer.patients.edit.form', function (BreadcrumbTrail $trail, Patient $patient) {
    $trail->parent('record_officer.dashboard');
    $trail->push('Patients', route('record_officer.patients.list'));
    $trail->push($patient->demographic->full_name ?? 'Patient', route('record_officer.patients.show', $patient));
    $trail->push('Edit', route('record_officer.patients.edit.form', $patient));
});

Breadcrumbs::for('record_officer.patients.search', function (BreadcrumbTrail $trail) {
    $trail->parent('record_officer.dashboard');
    $trail->push('Search Patient', route('record_officer.patients.search'));
});

// Record Officer - Visits
Breadcrumbs::for('record_officer.visits.create.form', function (BreadcrumbTrail $trail, Patient $patient) {
    $trail->parent('record_officer.dashboard');
    $trail->push('Patients', route('record_officer.patients.list'));
    $trail->push($patient->demographic->full_name ?? 'Patient', route('record_officer.patients.show', $patient));
    $trail->push('Record Visit', route('record_officer.visits.create.form', $patient));
});

// ===== ADMIN SERVICES ROUTES =====

// Admin Services
Breadcrumbs::for('admin.services.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.index');
    $trail->push('Services', route('admin.services.index'));
});

Breadcrumbs::for('admin.services.create', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.services.index');
    $trail->push('Create Service', route('admin.services.create'));
});

Breadcrumbs::for('admin.services.show', function (BreadcrumbTrail $trail, Service $service) {
    $trail->parent('admin.services.index');
    $trail->push($service->name, route('admin.services.show', $service));
});

Breadcrumbs::for('admin.services.edit', function (BreadcrumbTrail $trail, Service $service) {
    $trail->parent('admin.services.index');
    $trail->push("Edit: {$service->name}", route('admin.services.edit', $service));
});

// ===== ACCOUNTANT ROUTES =====

// Accountant Dashboard
Breadcrumbs::for('accountant.dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('dashboard'));
    $trail->push('Accountant', route('accountant.dashboard'));
});

// Accountant Bills
Breadcrumbs::for('accountant.bills.index', function (BreadcrumbTrail $trail) {
    $trail->parent('accountant.dashboard');
    $trail->push('Bills', route('accountant.bills.index'));
});

Breadcrumbs::for('accountant.bills.create', function (BreadcrumbTrail $trail) {
    $trail->parent('accountant.bills.index');
    $trail->push('Create Bill', route('accountant.bills.create'));
});

Breadcrumbs::for('accountant.bills.show', function (BreadcrumbTrail $trail, Bill $bill) {
    $trail->parent('accountant.bills.index');
    $trail->push($bill->bill_number, route('accountant.bills.show', $bill));
});

Breadcrumbs::for('accountant.bills.edit', function (BreadcrumbTrail $trail, Bill $bill) {
    $trail->parent('accountant.bills.index');
    $trail->push("Edit: {$bill->bill_number}", route('accountant.bills.edit', $bill));
});

// Accountant Payments
Breadcrumbs::for('accountant.payments.index', function (BreadcrumbTrail $trail) {
    $trail->parent('accountant.dashboard');
    $trail->push('Payments', route('accountant.payments.index'));
});

Breadcrumbs::for('accountant.payments.create', function (BreadcrumbTrail $trail) {
    $trail->parent('accountant.payments.index');
    $trail->push('Record Payment', route('accountant.payments.create'));
});

Breadcrumbs::for('accountant.payment-receipt', function (BreadcrumbTrail $trail, Payment $payment) {
    $trail->parent('accountant.payments.index');
    $trail->push("Receipt: {$payment->id}", route('accountant.payment-receipt', $payment));
});

Breadcrumbs::for('accountant.patient-payment-history', function (BreadcrumbTrail $trail, Patient $patient) {
    $trail->parent('accountant.dashboard');
    $trail->push('Patients', route('accountant.payments.index'));
    $trail->push("Payment History: {$patient->name}", route('accountant.patient-payment-history', $patient));
});

// Accountant Reports
Breadcrumbs::for('accountant.insurance-billing', function (BreadcrumbTrail $trail) {
    $trail->parent('accountant.dashboard');
    $trail->push('Insurance Billing', route('accountant.insurance-billing'));
});

Breadcrumbs::for('accountant.reports.financial', function (BreadcrumbTrail $trail) {
    $trail->parent('accountant.dashboard');
    $trail->push('Financial Reports', route('accountant.reports.financial'));
});

// ===== VITAL SIGNS ROUTES =====

Breadcrumbs::for('vital_signs.create', function (BreadcrumbTrail $trail, Patient $patient) {
    $trail->parent('record_officer.dashboard');
    $trail->push('Patients', route('record_officer.patients.list'));
    $trail->push($patient->demographic->full_name ?? 'Patient', route('record_officer.patients.show', $patient));
    $trail->push('Record Vital Signs', route('vital_signs.create', $patient));
});

Breadcrumbs::for('vital_signs.history', function (BreadcrumbTrail $trail, Patient $patient) {
    $trail->parent('record_officer.dashboard');
    $trail->push('Patients', route('record_officer.patients.list'));
    $trail->push($patient->demographic->full_name ?? 'Patient', route('record_officer.patients.show', $patient));
    $trail->push('Vital Signs History', route('vital_signs.history', $patient));
});
