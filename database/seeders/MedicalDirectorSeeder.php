<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MedicalDirectorSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'medical_director'],
            [
                'display_name' => 'Medical Director',
                'description' => 'Executive clinical oversight with read-only access to finance, bills, and payments, excluding access-control and technical system records',
            ]
        );

        $permissions = [
            ['module' => 'Administration', 'name' => 'department.create', 'display_name' => 'Create Department', 'description' => 'Create new department'],
            ['module' => 'Administration', 'name' => 'department.read', 'display_name' => 'View Departments', 'description' => 'View departments'],
            ['module' => 'Administration', 'name' => 'department.update', 'display_name' => 'Update Department', 'description' => 'Edit department'],
            ['module' => 'Administration', 'name' => 'department.delete', 'display_name' => 'Delete Department', 'description' => 'Delete department'],
            ['module' => 'Administration', 'name' => 'ward.create', 'display_name' => 'Create ward', 'description' => 'Create ward'],
            ['module' => 'Administration', 'name' => 'ward.read', 'display_name' => 'View wards', 'description' => 'View wards'],
            ['module' => 'Administration', 'name' => 'ward.update', 'display_name' => 'Update ward', 'description' => 'Edit ward'],
            ['module' => 'Administration', 'name' => 'ward.delete', 'display_name' => 'Delete ward', 'description' => 'Delete ward'],
            ['module' => 'Administration', 'name' => 'bed.create', 'display_name' => 'Create bed', 'description' => 'Create bed'],
            ['module' => 'Administration', 'name' => 'bed.read', 'display_name' => 'View beds', 'description' => 'View beds'],
            ['module' => 'Administration', 'name' => 'bed.update', 'display_name' => 'Update bed', 'description' => 'Edit bed'],
            ['module' => 'Administration', 'name' => 'bed.delete', 'display_name' => 'Delete bed', 'description' => 'Delete bed'],
            ['module' => 'Administration', 'name' => 'service.create', 'display_name' => 'Create Service', 'description' => 'Create service'],
            ['module' => 'Administration', 'name' => 'service.read', 'display_name' => 'View Services', 'description' => 'View services'],
            ['module' => 'Administration', 'name' => 'service.update', 'display_name' => 'Update Service', 'description' => 'Edit service'],
            ['module' => 'Administration', 'name' => 'service.delete', 'display_name' => 'Delete Service', 'description' => 'Delete service'],
            ['module' => 'Administration', 'name' => 'investigation.create', 'display_name' => 'Create investigation', 'description' => 'Create investigation'],
            ['module' => 'Administration', 'name' => 'investigation.read', 'display_name' => 'View investigations', 'description' => 'View investigations'],
            ['module' => 'Administration', 'name' => 'investigation.update', 'display_name' => 'Update investigation', 'description' => 'Edit investigation'],
            ['module' => 'Administration', 'name' => 'investigation.delete', 'display_name' => 'Delete investigation', 'description' => 'Delete investigation'],
            ['module' => 'Administration', 'name' => 'report.generate', 'display_name' => 'Create Report', 'description' => 'Create Report'],
            ['module' => 'Administration', 'name' => 'report.read', 'display_name' => 'View Reports', 'description' => 'View Reports'],
            ['module' => 'Administration', 'name' => 'report.print', 'display_name' => 'Print Report', 'description' => 'Print Report'],
            ['module' => 'Administration', 'name' => 'report.download', 'display_name' => 'Download Report', 'description' => 'Download Report'],
            ['module' => 'Administration', 'name' => 'activity.read', 'display_name' => 'View Activities', 'description' => 'View activity records'],
            ['module' => 'Patient', 'name' => 'patient.read', 'display_name' => 'View Patients', 'description' => 'View patients'],
            ['module' => 'Patient', 'name' => 'visit.read', 'display_name' => 'View Visits', 'description' => 'View visits'],
            ['module' => 'Clinical', 'name' => 'admission.read', 'display_name' => 'View admissions', 'description' => 'View admissions'],
            ['module' => 'Clinical', 'name' => 'discharge.read', 'display_name' => 'View Discharges', 'description' => 'View discharges'],
            ['module' => 'Billing', 'name' => 'bill.read', 'display_name' => 'View Bills', 'description' => 'View bills'],
            ['module' => 'Billing', 'name' => 'bill.print', 'display_name' => 'Print Bills', 'description' => 'Print bills'],
            ['module' => 'Billing', 'name' => 'payment.read', 'display_name' => 'View Payments', 'description' => 'View payments'],
            ['module' => 'Billing', 'name' => 'payment.print', 'display_name' => 'Print Payments', 'description' => 'Print payment receipts'],
            ['module' => 'Finance', 'name' => 'revenue.read', 'display_name' => 'View Revenues', 'description' => 'View revenues'],
            ['module' => 'Finance', 'name' => 'expense.read', 'display_name' => 'View Expenses', 'description' => 'View expenses'],
        ];

        $permissionIds = collect($permissions)
            ->map(function (array $permission) {
                return Permission::updateOrCreate(
                    ['name' => $permission['name']],
                    [
                        'display_name' => $permission['display_name'],
                        'description' => $permission['description'],
                        'module' => $permission['module'],
                    ]
                )->id;
            })
            ->all();

        $role->permissions()->sync($permissionIds);

        $modules = [
            ['name' => 'md_patient_register', 'label' => 'Patient Register', 'icon' => 'bi-file-earmark-spreadsheet', 'route' => 'medical-director.patient-register.index', 'license_module' => 'patient_records', 'sidebar_group' => 'patients'],
            ['name' => 'md_departments', 'label' => 'Departments', 'icon' => 'bi-buildings', 'route' => 'medical-director.departments.index', 'license_module' => 'department_management', 'sidebar_group' => 'departments_inventory'],
            ['name' => 'md_wards', 'label' => 'Wards', 'icon' => 'bi-hospital', 'route' => 'medical-director.wards.index', 'license_module' => 'wards_beds', 'sidebar_group' => 'wards_admissions'],
            ['name' => 'md_beds', 'label' => 'Beds', 'icon' => 'bi-hospital-fill', 'route' => 'medical-director.beds.index', 'license_module' => 'wards_beds', 'sidebar_group' => 'wards_admissions', 'is_sidebar_visible' => false],
            ['name' => 'md_investigations', 'label' => 'Investigations', 'icon' => 'bi-clipboard2-data', 'route' => 'medical-director.investigations.index', 'license_module' => 'laboratory', 'sidebar_group' => 'administration'],
            ['name' => 'md_services', 'label' => 'Services', 'icon' => 'bi-gear-fill', 'route' => 'medical-director.services.index', 'license_module' => 'billing', 'sidebar_group' => 'administration'],
            ['name' => 'md_revenues', 'label' => 'Revenues', 'icon' => 'bi-graph-up-arrow', 'route' => 'medical-director.revenues.index', 'license_module' => 'finance', 'sidebar_group' => 'payments_finance'],
            ['name' => 'md_expenses', 'label' => 'Expenses', 'icon' => 'bi-cash-stack', 'route' => 'medical-director.expenses.index', 'license_module' => 'finance', 'sidebar_group' => 'payments_finance'],
            ['name' => 'accountant_billing_report', 'label' => 'Billing Report', 'icon' => 'bi-file-earmark-text', 'route' => 'reports.finance.index', 'license_module' => 'reports', 'sidebar_group' => 'reports'],
            ['name' => 'accountant_payment_report', 'label' => 'Payment Report', 'icon' => 'bi-bar-chart-line', 'route' => 'reports.payments.index', 'license_module' => 'reports', 'sidebar_group' => 'reports'],
            ['name' => 'activity_report', 'label' => 'Activities', 'icon' => 'bi-activity', 'route' => 'reports.my-activities.index', 'license_module' => 'reports', 'sidebar_group' => 'reports'],
        ];

        $moduleIds = collect($modules)
            ->map(function (array $module, int $index) {
                return Module::updateOrCreate(
                    ['name' => $module['name']],
                    [
                        'label' => $module['label'],
                        'route' => $module['route'],
                        'icon' => $module['icon'],
                        'group' => $module['sidebar_group'],
                        'license_module' => $module['license_module'],
                        'sidebar_group' => $module['sidebar_group'],
                        'sidebar_patterns' => [$module['route']],
                        'sort_order' => 900 + $index,
                        'is_active' => true,
                        'is_sidebar_visible' => $module['is_sidebar_visible'] ?? true,
                    ]
                )->id;
            })
            ->all();

        $role->modules()->sync($moduleIds);
    }
}
