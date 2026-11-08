<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ModulePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->createDepartmentHeadRoles();

        foreach ($this->modules() as $index => $item) {
            $module = Module::updateOrCreate(
                ['name' => $item['name']],
                [
                    'label' => $item['label'],
                    'route' => $item['route'],
                    'icon' => $item['icon'],
                    'group' => $item['sidebar_group'],
                    'license_module' => $item['license_module'],
                    'sidebar_group' => $item['sidebar_group'],
                    'sidebar_patterns' => $item['patterns'] ?? [$item['route']],
                    'sort_order' => $item['sort_order'] ?? ($index + 1),
                    'is_active' => $item['is_active'] ?? true,
                    'is_sidebar_visible' => $item['is_sidebar_visible'] ?? true,
                ]
            );

            foreach ($item['permissions'] as $permissionName) {
                $permission = Permission::updateOrCreate(
                    ['name' => $permissionName],
                    [
                        'display_name' => Str::headline(str_replace('.', ' ', $permissionName)),
                        'description' => 'Allows ' . str_replace('.', ' ', $permissionName),
                        'module' => Str::headline($item['license_module']),
                        'module_id' => $module->id,
                        'action' => Str::afterLast($permissionName, '.'),
                    ]
                );

                foreach ($item['roles'] as $roleName) {
                    $role = Role::where('name', $roleName)->first();

                    if ($role) {
                        $role->permissions()->syncWithoutDetaching([$permission->id]);
                    }
                }
            }

            foreach ($item['roles'] as $roleName) {
                $role = Role::where('name', $roleName)->first();

                if ($role) {
                    $role->modules()->syncWithoutDetaching([$module->id]);
                }
            }
        }

    }

    private function createDepartmentHeadRoles(): void
    {
        foreach (Department::all() as $department) {
            $roleName = 'head_of_' . Str::slug($department->name, '_');

            Role::updateOrCreate(
                ['name' => $roleName],
                [
                    'display_name' => 'Head of '.ucwords(strtolower($department->name)),
                    'description' => 'Head of department role for ' . $department->name,
                ]
            );
        }
    }

    private function modules(): array
    {
        return [
            $this->item('dashboard', 'Dashboard', 'bi-speedometer2', 'dashboard', 'core', 'dashboard', ['administrator', 'medical_director', 'record', 'nurse', 'doctor', 'midwife', 'accountant', 'finance_officer', 'pharmacist', 'lab_scientist', 'lab_technician', 'radiologist', 'radiographer', 'head_of_department'], ['dashboard.read'], ['dashboard', 'admin.index', 'medical-director.index'], 1),

            $this->item('record_patients', 'Patients', 'bi-people-fill', 'record.patients.index', 'patient_records', 'patients', ['record'], ['patient.read'], ['record.patients.*'], 10),
            $this->item('register_patient', 'Register Patient', 'bi-person-plus', 'record.patients.register.form', 'patient_records', 'patients', ['record'], ['patient.create'], ['record.patients.register.*'], 11),
            $this->item('record_patient_register', 'Patient Register', 'bi-file-earmark-spreadsheet', 'record.patient-register.index', 'patient_records', 'patients', ['record'], ['patient.read', 'patient_register.read'], ['record.patient-register.*'], 12),
            $this->item('admin_patient_register', 'Patient Register', 'bi-file-earmark-spreadsheet', 'admin.patient-register.index', 'patient_records', 'patients', ['administrator'], ['patient.read', 'patient_register.read'], ['admin.patient-register.*'], 13),
            $this->item('medical_director_patient_register', 'Patient Register', 'bi-file-earmark-spreadsheet', 'medical-director.patient-register.index', 'patient_records', 'patients', ['medical_director'], ['patient.read', 'patient_register.read'], ['medical-director.patient-register.*'], 14),
            $this->item('doctor_patients', 'Patients', 'bi-person-vcard', 'doctor.patient.index', 'doctor', 'patients', ['doctor'], ['patient.read'], ['doctor.patient.*'], 15),
            $this->item('nurse_patients', 'Patients', 'bi-clipboard-pulse', 'nurse.patient.index', 'nursing', 'patients', ['nurse'], ['patient.read'], ['nurse.patient.*'], 16),
            $this->item('midwife_patients', 'Maternity Patients', 'bi-people-fill', 'midwife.patient.index', 'maternity', 'patients', ['midwife'], ['patient.read', 'antenatal_care.read'], ['midwife.patient.*'], 17),

            $this->item('nurse_vital_signs', 'Vital Signs', 'bi-heart-pulse', 'nurse.clinicals.vital-signs', 'clinical_care', 'clinical', ['nurse'], ['vital_sign.read', 'vital_sign.create'], ['nurse.clinicals.vital-signs'], 30),
            $this->item('doctor_vital_signs', 'Vital Signs', 'bi-heart-pulse', 'doctor.clinicals.vital-signs', 'clinical_care', 'clinical', ['doctor'], ['vital_sign.read'], ['doctor.clinicals.vital-signs'], 31),
            $this->item('nurse_observations', 'Observations', 'bi-eye', 'nurse.clinicals.observations', 'clinical_care', 'clinical', ['nurse'], ['observation.read', 'observation.create'], ['nurse.clinicals.observations'], 32),
            $this->item('doctor_observations', 'Observations', 'bi-eye', 'doctor.clinicals.observations', 'clinical_care', 'clinical', ['doctor'], ['observation.read'], ['doctor.clinicals.observations'], 33),
            $this->item('doctor_clinical_admissions', 'Admissions', 'bi-hospital', 'doctor.clinicals.admissions', 'clinical_care', 'clinical', ['doctor'], ['admission.read'], ['doctor.clinicals.admissions'], 34),
            $this->item('doctor_continuations', 'Continuation Notes', 'bi-pencil-square', 'doctor.clinicals.continuations', 'clinical_care', 'clinical', ['doctor'], ['continuation.read', 'prescription.read'], ['doctor.clinicals.continuations'], 35),
            $this->item('nurse_fluid_balances', 'Fluid Balance', 'bi-droplet', 'nurse.clinicals.fluid-balances', 'clinical_care', 'clinical', ['nurse'], ['fluid_balance.read', 'fluid_balance.create'], ['nurse.clinicals.fluid-balances'], 36),
            $this->item('doctor_fluid_balances', 'Fluid Balance', 'bi-droplet', 'doctor.clinicals.fluid-balances', 'clinical_care', 'clinical', ['doctor'], ['fluid_balance.read'], ['doctor.clinicals.fluid-balances'], 37),
            $this->item('nurse_clinical_investigations', 'Investigation Requests', 'bi-clipboard2-pulse', 'nurse.clinicals.investigations', 'clinical_care', 'clinical', ['nurse'], ['investigation_request.read'], ['nurse.clinicals.investigations'], 38),
            $this->item('doctor_clinical_investigations', 'Investigation Requests', 'bi-clipboard2-pulse', 'doctor.clinicals.investigations', 'clinical_care', 'clinical', ['doctor'], ['investigation_request.read', 'investigation_request.create'], ['doctor.clinicals.investigations'], 39),

            $this->item('doctor_prescriptions', 'Prescriptions', 'bi-prescription2', 'doctor.clinicals.prescriptions', 'clinical_care', 'medications', ['doctor'], ['prescription.read', 'prescription.create'], ['doctor.clinicals.prescriptions'], 50),
            $this->item('doctor_drug_charts', 'Drug Chart', 'bi-capsule-pill', 'doctor.clinicals.drug-charts', 'clinical_care', 'medications', ['doctor'], ['drug_chart.read', 'prescription.read'], ['doctor.clinicals.drug-charts'], 51),
            $this->item('nurse_drug_charts', 'Drug Chart', 'bi-capsule-pill', 'nurse.clinicals.drug-charts', 'clinical_care', 'medications', ['nurse'], ['drug_chart.read', 'nursing_note.read'], ['nurse.clinicals.drug-charts'], 52),
            $this->item('pharmacy_prescriptions', 'Prescription Dispensing', 'bi-prescription2', 'pharmacy.prescriptions.index', 'pharmacy', 'medications', ['pharmacist', 'pharmacy_technician'], ['dispense.read', 'pharmacy_sale.read'], ['pharmacy.prescriptions.*'], 53),
            $this->item('pharmacy_transactions', 'Stock Transactions', 'bi-arrow-left-right', 'pharmacy.transactions.index', 'pharmacy', 'medications', ['pharmacist', 'pharmacy_technician'], ['stock_transaction.read', 'pharmacy_sale.read'], ['pharmacy.transactions.index', 'pharmacy.transactions.create'], 54),
            $this->item('pharmacy_medicines', 'Medicines', 'bi-capsule', 'pharmacy.medicines.index', 'pharmacy', 'medications', ['pharmacist', 'head_of_department'], ['medicine.read'], ['pharmacy.medicines.*'], 55),
            $this->item('pharmacy_stock', 'Pharmacy Stock', 'bi-box-seam', 'pharmacy.stocks.index', 'pharmacy', 'medications', ['pharmacist', 'head_of_department'], ['medicine_stock.read'], ['pharmacy.stocks.index', 'pharmacy.stocks.create'], 56),
            $this->item('pharmacy_stock_reconciliation', 'Stock Reconciliation', 'bi-clipboard-check', 'pharmacy.stocks.reconciliation', 'pharmacy', 'medications', ['head_of_department'], ['stock_reconciliation.read', 'medicine_stock.read'], ['pharmacy.stocks.reconciliation'], 57),
            $this->item('pharmacy_batches', 'Medicine Batches', 'bi-layers', 'pharmacy.batches.index', 'pharmacy', 'medications', ['pharmacist', 'head_of_department'], ['medicine_stock.read'], ['pharmacy.batches.*'], 58),
            $this->item('pharmacy_expiry_alerts', 'Expiry Alerts', 'bi-exclamation-triangle', 'pharmacy.expiries.index', 'pharmacy', 'medications', ['pharmacist', 'head_of_department'], ['expiry_alert.read'], ['pharmacy.expiries.*'], 59),

            $this->item('lab_requests', 'Lab Requests', 'bi-vial', 'lab.requests.index', 'laboratory', 'diagnostics', ['lab_technician', 'lab_scientist'], ['laboratory_request.read', 'investigation_request.read'], ['lab.requests.*'], 70),
            $this->item('lab_results', 'Results Entry', 'bi-clipboard2-data', 'lab.result', 'laboratory', 'diagnostics', ['lab_technician', 'lab_scientist'], ['laboratory_result.create', 'investigation_result.create'], ['lab.result'], 71),
            $this->item('lab_investigations', 'Lab Investigations', 'bi-list-check', 'lab.investigations.index', 'laboratory', 'diagnostics', ['lab_technician', 'lab_scientist'], ['laboratory_investigation.read', 'investigation.read'], ['lab.investigations.*'], 72),
            $this->item('radiology_requests', 'Radiology Requests', 'bi-radioactive', 'radiology.requests.index', 'radiology', 'diagnostics', ['radiologist', 'radiographer'], ['radiology_request.read', 'investigation_request.read'], ['radiology.requests.*'], 73),
            $this->item('radiology_investigations', 'Radiology Investigations', 'bi-list-check', 'radiology.investigations.index', 'radiology', 'diagnostics', ['radiologist'], ['radiology_investigation.read', 'investigation.read'], ['radiology.investigations.*'], 74),

            $this->item('antenatal_care', 'Antenatal Care', 'bi-heart-pulse-fill', 'midwife.antenatal.index', 'maternity', 'maternity', ['midwife'], ['antenatal_care.read', 'antenatal_care.create'], ['midwife.antenatal.*', 'midwife.anc-management'], 90),
            $this->item('labour', 'Labour', 'bi-activity', 'midwife.labour.index', 'maternity', 'maternity', ['midwife'], ['labour.read', 'labour.create'], ['midwife.labour.*', 'midwife.labour-management'], 91),
            $this->item('delivery', 'Delivery', 'bi-hospital-fill', 'midwife.delivery.index', 'maternity', 'maternity', ['midwife'], ['delivery.read', 'delivery.create'], ['midwife.delivery.*', 'midwife.delivery-management'], 92),
            $this->item('newborns', 'Newborns', 'bi-bandaid-fill', 'midwife.newborn.index', 'maternity', 'maternity', ['midwife'], ['newborn.read', 'newborn.create'], ['midwife.newborn.*', 'midwife.newborn-management'], 93),
            $this->item('newborn_examinations', 'Newborn Exams', 'bi-clipboard2-pulse', 'midwife.newborn-examination.index', 'maternity', 'maternity', ['midwife'], ['newborn_examination.read', 'newborn_examination.create'], ['midwife.newborn-examination.*'], 94),
            $this->item('postnatal_examinations', 'Postnatal', 'bi-journal-medical', 'midwife.postnatal-examination.index', 'maternity', 'maternity', ['midwife'], ['postnatal_examination.read', 'postnatal_examination.create'], ['midwife.postnatal-examination.*', 'midwife.postnatal-management'], 95),
            $this->item('child_follow_ups', 'Child Follow-up', 'bi-arrow-repeat', 'midwife.child-follow-up.index', 'maternity', 'maternity', ['midwife'], ['child_follow_up.read', 'child_follow_up.create'], ['midwife.child-follow-up.*', 'midwife.child-follow-up-management'], 96),

            $this->item('accountant_bills', 'Bills', 'bi-receipt', 'accountant.bills.index', 'billing', 'billing', ['accountant'], ['bill.read', 'bill.create'], ['accountant.bills.*'], 110),
            $this->item('finance_bills', 'Bills', 'bi-receipt', 'finance.bills.index', 'billing', 'billing', ['finance_officer'], ['bill.read'], ['finance.bills.*'], 111),
            $this->item('admin_bills', 'Bills', 'bi-receipt', 'admin.bills.index', 'billing', 'billing', ['administrator', 'medical_director'], ['bill.read'], ['admin.bills.*'], 112),

            $this->item('accountant_payments', 'Payments', 'bi-credit-card-2-front', 'accountant.payments.index', 'billing', 'payments_finance', ['accountant'], ['payment.read', 'payment.create'], ['accountant.payments.*'], 130),
            $this->item('finance_payments', 'Payments', 'bi-credit-card-2-front', 'finance.payments.index', 'billing', 'payments_finance', ['finance_officer'], ['payment.read'], ['finance.payments.*'], 131),
            $this->item('admin_payments', 'Payments', 'bi-credit-card-2-front', 'admin.payments.index', 'billing', 'payments_finance', ['administrator', 'medical_director'], ['payment.read'], ['admin.payments.*'], 132),
            $this->item('expenses', 'Expenses', 'bi-cash-stack', 'finance.expenses.index', 'finance', 'payments_finance', ['finance_officer'], ['expense.read'], ['finance.expenses.*'], 133),
            $this->item('revenues', 'Revenues', 'bi-graph-up-arrow', 'finance.revenues.index', 'finance', 'payments_finance', ['finance_officer'], ['revenue.read'], ['finance.revenues.*'], 134),
            $this->item('admin_expenses', 'Expenses', 'bi-cash-stack', 'admin.expenses.index', 'finance', 'payments_finance', ['administrator', 'medical_director'], ['expense.read'], ['admin.expenses.*'], 135),
            $this->item('admin_revenues', 'Revenues', 'bi-graph-up-arrow', 'admin.revenues.index', 'finance', 'payments_finance', ['administrator', 'medical_director'], ['revenue.read'], ['admin.revenues.*'], 136),
            $this->item('pharmacy_finance_report', 'Pharmacy Finance', 'bi-file-earmark-text', 'pharmacy.finance.report', 'pharmacy', 'payments_finance', ['pharmacist', 'head_of_department'], ['pharmacy_sale.read', 'department_report.read'], ['pharmacy.finance.*'], 137),

            $this->item('nurse_admissions', 'Admissions', 'bi-hospital', 'nurse.admissions.index', 'wards_beds', 'wards_admissions', ['nurse'], ['admission.read'], ['nurse.admissions.*'], 150),
            $this->item('admin_admissions', 'Admissions', 'bi-hospital', 'admin.admissions.index', 'wards_beds', 'wards_admissions', ['administrator', 'medical_director'], ['admission.read'], ['admin.admissions.*'], 151),
            $this->item('admin_wards', 'Wards', 'bi-hospital', 'admin.wards.index', 'wards_beds', 'wards_admissions', ['administrator', 'medical_director'], ['ward.read', 'bed.read'], ['admin.wards.*', 'admin.beds.*'], 152),
            $this->item('medical_director_admissions', 'Admissions', 'bi-hospital', 'medical-director.admissions.index', 'wards_beds', 'wards_admissions', ['medical_director'], ['admission.read'], ['medical-director.admissions.*'], 153),
            $this->item('medical_director_wards', 'Wards', 'bi-hospital', 'medical-director.wards.index', 'wards_beds', 'wards_admissions', ['medical_director'], ['ward.read', 'bed.read'], ['medical-director.wards.*', 'medical-director.beds.*'], 154),

            $this->item('departments', 'Departments', 'bi-buildings', 'admin.departments.index', 'department_management', 'departments_inventory', ['administrator', 'medical_director'], ['department.read'], ['admin.departments.*'], 170),
            $this->item('department_users', 'Department Users', 'bi-people', 'department.users.index', 'access_control', 'departments_inventory', ['head_of_department'], ['department_user.read', 'user.read'], ['department.users.*'], 171),
            $this->item('department_investigations', 'Department Investigations', 'bi-clipboard2-data', 'department.investigations.index', 'department_management', 'departments_inventory', ['head_of_department'], ['investigation.read'], ['department.investigations.*'], 172),
            $this->item('department_consumables', 'Consumables', 'bi-box-seam', 'department.consumables.index', 'department_management', 'departments_inventory', ['head_of_department'], ['consumable.read'], ['department.consumables.*'], 173),
            $this->item('department_stocks', 'Consumable Stock', 'bi-boxes', 'department.stocks.index', 'department_management', 'departments_inventory', ['head_of_department'], ['consumable_stock.read'], ['department.stocks.*'], 174),
            $this->item('department_stock_usage', 'Stock Usage', 'bi-clipboard-check', 'department.stock-usage.index', 'department_management', 'departments_inventory', ['head_of_department'], ['consumable_usage.read', 'consumable_stock.read'], ['department.stock-usage.*'], 175),

            $this->item('my_activity_report', 'My Activities', 'bi-activity', 'reports.my-activities.index', 'reports', 'reports', ['administrator', 'medical_director', 'record', 'nurse', 'doctor', 'midwife', 'accountant', 'finance_officer', 'pharmacist', 'lab_scientist', 'lab_technician', 'radiologist', 'radiographer', 'head_of_department'], ['activity.read'], ['reports.my-activities.*'], 190),
            $this->item('finance_report', 'Financial Report', 'bi-file-earmark-text', 'reports.finance.index', 'reports', 'reports', ['administrator', 'medical_director', 'accountant', 'finance_officer'], ['financial_report.read', 'report.read', 'bill.read'], ['reports.finance.*'], 191),
            $this->item('payment_report', 'Payment Report', 'bi-bar-chart-line', 'reports.payments.index', 'reports', 'reports', ['administrator', 'medical_director', 'accountant', 'finance_officer'], ['payment_report.read', 'report.read', 'payment.read'], ['reports.payments.*'], 192),
            $this->item('medical_director_statistics', 'Statistics Report', 'bi-bar-chart-line', 'medical-director.statistics.index', 'medical_director', 'reports', ['medical_director'], ['statistics_report.read', 'report.read'], ['medical-director.statistics.*'], 193),
            $this->item('department_reports', 'Department Report', 'bi-file-earmark-medical', 'department.reports.index', 'department_management', 'reports', ['head_of_department'], ['department_report.read'], ['department.reports.*'], 194),

            $this->item('access_control', 'Access Control', 'bi-shield-check', 'admin.access-control', 'access_control', 'administration', ['administrator'], ['role.read', 'permission.read', 'role.update', 'permission.update'], ['admin.access-control'], 210),
            $this->item('users', 'Users', 'bi-people-fill', 'admin.users.index', 'access_control', 'administration', ['administrator'], ['user.read', 'user.create', 'user.update', 'user.delete'], ['admin.users.*'], 211),
            $this->item('roles', 'Roles', 'bi-person-lock', 'admin.roles.index', 'access_control', 'administration', ['administrator'], ['role.read'], ['admin.roles.*'], 212),
            $this->item('permissions', 'Permissions', 'bi-key', 'admin.permissions.index', 'access_control', 'administration', ['administrator'], ['permission.read'], ['admin.permissions.*'], 213),
            $this->item('temporary_permissions', 'Temporary Permissions', 'bi-clock-history', 'admin.temporary-permissions.index', 'access_control', 'administration', ['administrator'], ['temporary_permission.read'], ['admin.temporary-permissions.*'], 214),
            $this->item('services', 'Services', 'bi-gear-fill', 'admin.services.index', 'billing', 'administration', ['administrator', 'medical_director'], ['service.read'], ['admin.services.*'], 215),
            $this->item('admin_investigations', 'Investigations Setup', 'bi-clipboard2-data', 'admin.investigations.index', 'laboratory', 'administration', ['administrator', 'medical_director'], ['investigation.read'], ['admin.investigations.*'], 216),
            $this->item('file_types', 'File Types', 'bi-folder2-open', 'admin.file-types.index', 'access_control', 'administration', ['administrator', 'medical_director'], ['file_type.read', 'patient.read'], ['admin.file-types.*'], 217),
            $this->item('module_access', 'Module Access', 'bi-ui-checks-grid', 'admin.access-control', 'access_control', 'administration', ['administrator'], ['module_access.manage'], ['admin.access-control'], 218, false),
            $this->item('data_sync', 'Data Sync', 'bi-cloud-arrow-up', 'admin.sync.index', 'synchronization', 'system', ['administrator'], ['sync.read', 'sync.create', 'sync.update'], ['admin.sync.*'], 230),
            $this->item('system_update', 'System Update', 'bi-arrow-repeat', 'admin.system.update', 'maintenance', 'system', ['administrator'], ['system.update'], ['admin.system.*'], 231),
            $this->item('database_backup', 'Data Backup', 'bi-database-down', 'admin.backup.index', 'maintenance', 'system', ['administrator'], ['backup.read', 'backup.create'], ['admin.backup.*'], 232),
        ];
    }

    private function item(
        string $name,
        string $label,
        string $icon,
        string $route,
        string $licenseModule,
        string $sidebarGroup,
        array $roles,
        array $permissions,
        array $patterns,
        int $sortOrder,
        bool $isSidebarVisible = true
    ): array {
        return [
            'name' => $name,
            'label' => $label,
            'icon' => $icon,
            'route' => $route,
            'license_module' => $licenseModule,
            'sidebar_group' => $sidebarGroup,
            'roles' => collect($roles)->unique()->values()->all(),
            'permissions' => $permissions,
            'patterns' => $patterns,
            'sort_order' => $sortOrder,
            'is_sidebar_visible' => $isSidebarVisible,
        ];
    }
}

