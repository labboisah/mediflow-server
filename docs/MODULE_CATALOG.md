# MediFlow Module Catalog

## Purpose

This document describes the functional modules in MediFlow.

It should be used together with:

- `MODULE_LICENSING_APPROACH.md`
- `SIDEBAR_GROUPING_IMPLEMENTATION.md`
- `RBAC_MODULE_ACCESS_IMPLEMENTATION.md`

Each module in this document can be enabled by a client license, assigned to users by an admin, and controlled internally by roles and permissions.

## Access Model

Every module should follow this access chain:

```text
Client license enables the module
AND
Admin grants the user access to the module
AND
User has the required role or permission
```

The sidebar should only show items after all three checks pass.

## Module Summary

| Module | License Key | Main Sidebar Group | Typical Plans |
| --- | --- | --- | --- |
| Core System | `core` | Dashboard | All plans |
| User & Access Control | `access_control` | Administration | All plans |
| Patient Records | `patient_records` | Patients | Clinic, Diagnostic, Maternity, Hospital |
| Clinical Care | `clinical_care` | Clinical | Clinic, Hospital |
| Doctor Workspace | `doctor` | Patients, Clinical | Clinic, Hospital |
| Nursing Workspace | `nursing` | Patients, Clinical | Clinic, Hospital |
| Maternity / Midwife | `maternity` | Maternity | Maternity, Hospital |
| Laboratory | `laboratory` | Diagnostics | Diagnostic, Maternity, Hospital |
| Radiology | `radiology` | Diagnostics | Diagnostic, Hospital |
| Pharmacy | `pharmacy` | Medications | Pharmacy, Hospital |
| Billing | `billing` | Billing | Most plans |
| Finance | `finance` | Payments & Finance | Hospital, Enterprise |
| Department Management | `department_management` | Departments & Inventory | Hospital, Enterprise |
| Ward & Bed Management | `wards_beds` | Wards & Admissions | Hospital, Enterprise |
| Reports | `reports` | Reports | Most plans |
| Medical Director | `medical_director` | Dashboard, Reports | Hospital, Enterprise |
| Audit Trail | `audit` | Reports, System | Hospital, Enterprise |
| Data Synchronization | `synchronization` | System | Enterprise |
| Backup & Maintenance | `maintenance` | System | Hospital, Enterprise |

## 1. Core System

### License Key

```text
core
```

### Purpose

Provides the base application shell required for every MediFlow installation.

### Main Users

- All authenticated users
- System administrators

### Features

- Login/logout
- Authentication
- User profile
- Main dashboard entry
- Shared layouts
- Navigation shell
- Welcome/home screen

### Main Routes

```text
/
dashboard
profile.edit
profile.update
profile.destroy
```

### Dependencies

None. This module is required by all other modules.

### Sidebar Group

```text
Dashboard
```

### Access Notes

Core should always be available when the license is valid. Some core pages, such as profile, should not require separate module-user access.

## 2. User & Access Control

### License Key

```text
access_control
```

### Purpose

Controls users, roles, permissions, temporary permissions, and module access assignment.

### Main Users

- Administrator
- Authorized client admin
- Owner/super-admin if added later

### Features

- User management
- Role management
- Permission management
- Access control manager
- Temporary permission grants
- Module-user access assignment
- Optional license management screen

### Main Routes

```text
admin.users.index
admin.roles.index
admin.permissions.index
admin.access-control
admin.temporary-permissions.index
```

### Suggested Permissions

```text
user.read
user.create
user.update
user.delete
role.read
role.create
role.update
role.delete
permission.read
permission.create
permission.update
permission.delete
module_access.manage
```

### Dependencies

- Core System

### Sidebar Group

```text
Administration
```

### Access Notes

This module should be enabled for most plans, but only trusted admin users should receive permissions inside it.

## 3. Patient Records

### License Key

```text
patient_records
```

### Purpose

Manages patient identity, registration, demographic data, patient visits, and patient record lookup.

### Main Users

- Record Officer
- Doctor
- Nurse
- Midwife
- Accountant
- Medical Director
- Administrator

### Features

- Patient registration
- Patient list
- Patient search
- Patient demographics
- Next of kin
- Visit creation
- Patient history
- Patient register
- Patient summary
- Patient record export
- Walk-in patient handling

### Main Routes

```text
record.patients.index
record.patients.register.form
record.patients.search
record.patients.show
record.patients.edit.form
record.visits.create.form
record.patient-register.index
record.patient-register.csv
record.patient-register.pdf
patient.index
patient.search
patient.show
patient.history
admin.patient-register.index
medical-director.patient-register.index
```

### Suggested Permissions

```text
patient.read
patient.create
patient.update
patient.delete
patient.export
visit.read
visit.create
visit.update
```

### Dependencies

- Core System

### Sidebar Group

```text
Patients
```

### Access Notes

Patient Records is foundational for most clinical modules. Maternity, Laboratory, Radiology, Doctor, Nursing, Billing, and Clinical Care commonly depend on it.

## 4. Clinical Care

### License Key

```text
clinical_care
```

### Purpose

Handles general patient care documentation and treatment workflow.

### Main Users

- Doctor
- Nurse
- Midwife, if allowed
- Administrator

### Features

- Vital signs
- Observations
- Continuation sheets
- Diagnoses
- Admissions
- Discharges
- Prescriptions
- Drug charts
- Fluid balance
- Investigation requests

### Main Routes

```text
patient.vitalsign.create
patient.observation.record
patient.continuation.create
patient.admission.create
patient.discharge.create
patient.fluidbalance.record
patient.drugchart.record
patient.prescription.create
patient.prescription.show
patient.investigation.create
doctor.clinicals.vital-signs
doctor.clinicals.observations
doctor.clinicals.investigations
doctor.clinicals.admissions
doctor.clinicals.prescriptions
doctor.clinicals.continuations
doctor.clinicals.drug-charts
doctor.clinicals.fluid-balances
nurse.clinicals.vital-signs
nurse.clinicals.observations
nurse.clinicals.investigations
nurse.clinicals.drug-charts
nurse.clinicals.fluid-balances
```

### Suggested Permissions

```text
vital_sign.read
vital_sign.create
vital_sign.update
observation.read
observation.create
admission.read
admission.create
admission.update
discharge.read
discharge.create
prescription.read
prescription.create
prescription.update
drug_chart.read
drug_chart.create
fluid_balance.read
fluid_balance.create
continuation.read
continuation.create
investigation_request.read
investigation_request.create
```

### Dependencies

- Core System
- Patient Records

### Sidebar Groups

```text
Clinical
Medications
Wards & Admissions
Diagnostics
```

### Access Notes

Clinical Care contains items that may appear under different sidebar groups. For example, prescriptions and drug charts should appear under Medications, while investigation requests may appear under Clinical or Diagnostics depending on the user workflow.

## 5. Doctor Workspace

### License Key

```text
doctor
```

### Purpose

Provides a doctor-focused workspace for reviewing patients, managing clinical records, prescribing treatment, and closing visits.

### Main Users

- Doctor

### Features

- Doctor patient list
- Patient review
- Complete service request
- Close patient visit
- Clinical record indexes
- Prescription review
- Investigation review

### Main Routes

```text
doctor.patient.index
doctor.patient.show
doctor.patient.complete
doctor.patient.close-visit
doctor.clinicals.vital-signs
doctor.clinicals.observations
doctor.clinicals.investigations
doctor.clinicals.admissions
doctor.clinicals.prescriptions
doctor.clinicals.continuations
doctor.clinicals.drug-charts
doctor.clinicals.fluid-balances
```

### Suggested Permissions

```text
patient.read
visit.update
service_request.update
vital_sign.read
observation.read
admission.read
prescription.read
prescription.create
investigation_request.read
investigation_request.create
```

### Dependencies

- Core System
- Patient Records
- Clinical Care

### Sidebar Groups

```text
Patients
Clinical
Medications
Diagnostics
```

### Access Notes

The Doctor Workspace should not grant all clinical permissions by itself. It gives access to the doctor workflow, while specific actions still depend on permissions.

## 6. Nursing Workspace

### License Key

```text
nursing
```

### Purpose

Provides a nurse-focused workspace for patient monitoring, vital signs, observations, medication chart review, and admission monitoring.

### Main Users

- Nurse

### Features

- Nurse dashboard
- Nurse patient list
- Vital signs
- Observations
- Investigation records
- Drug charts
- Fluid balance
- Admissions list
- Absconded/SAMA admission actions
- Complete service request
- Close visit

### Main Routes

```text
nurse.patient.index
nurse.patient.show
nurse.patient.complete
nurse.patient.close-visit
nurse.clinicals.vital-signs
nurse.clinicals.observations
nurse.clinicals.investigations
nurse.clinicals.drug-charts
nurse.clinicals.fluid-balances
nurse.admissions.index
nurse.admissions.record-absconded
nurse.admissions.record-sama
```

### Suggested Permissions

```text
patient.read
vital_sign.read
vital_sign.create
observation.read
observation.create
nursing_note.read
nursing_note.create
admission.read
admission.update
drug_chart.read
fluid_balance.read
fluid_balance.create
```

### Dependencies

- Core System
- Patient Records
- Clinical Care

### Sidebar Groups

```text
Patients
Clinical
Medications
Wards & Admissions
```

### Access Notes

Nursing should expose only the items permitted for the nurse. If a nurse has vital sign permissions only, the Clinical group should show only Vital Signs.

## 7. Maternity / Midwife

### License Key

```text
maternity
```

### Purpose

Manages maternal and newborn care workflows from antenatal care through labour, delivery, postnatal care, newborn records, and child follow-up.

### Main Users

- Midwife
- Administrator, where allowed
- Medical Director, for oversight

### Features

- Maternity patient list
- Patient maternity progress
- Antenatal care
- Labour
- Labour progress
- Delivery
- Newborn registration
- Newborn examination
- Postnatal examination
- Child follow-up
- Maternal medications

### Main Routes

```text
midwife.patient.index
midwife.patient.show
midwife.patient.progress
midwife.anc-management
midwife.antenatal.index
midwife.antenatal.create
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

### Suggested Permissions

```text
antenatal_care.read
antenatal_care.create
antenatal_care.update
labour.read
labour.create
labour.update
labour_progress.read
labour_progress.create
delivery.read
delivery.create
delivery.update
newborn.read
newborn.create
newborn.update
newborn_examination.read
newborn_examination.create
postnatal_examination.read
postnatal_examination.create
child_follow_up.read
child_follow_up.create
maternal_medication.read
maternal_medication.create
```

### Dependencies

- Core System
- Patient Records

### Optional Dependencies

- Laboratory
- Pharmacy
- Billing

### Sidebar Group

```text
Maternity
```

### Access Notes

Maternity is a strong sellable module. It should be visible only to users attached to the Maternity module and given maternity permissions.

## 8. Laboratory

### License Key

```text
laboratory
```

### Purpose

Manages laboratory investigations, requests, result entry, and result viewing.

### Main Users

- Lab Scientist
- Lab Technician
- Doctor, for requesting/viewing results if allowed
- Medical Director, for oversight

### Features

- Lab request list
- Result entry
- Result viewing
- Investigation setup
- Investigation parameters
- Result printing

### Main Routes

```text
lab.requests.index
lab.requests.createResult
lab.requests.results.store
lab.requests.show
lab.requests.editResult
lab.result
lab.investigations.index
lab.investigations.create
lab.investigations.edit
lab.investigations.parameters.index
lab.investigations.parameters.create
lab.investigations.parameters.edit
```

### Suggested Permissions

```text
laboratory_request.read
laboratory_request.create
laboratory_request.update
laboratory_result.read
laboratory_result.create
laboratory_result.update
laboratory_result.print
laboratory_investigation.read
laboratory_investigation.create
laboratory_investigation.update
parameter.read
parameter.create
parameter.update
```

### Dependencies

- Core System
- Patient Records

### Optional Dependencies

- Billing

### Sidebar Group

```text
Diagnostics
```

### Access Notes

Laboratory items should appear under Diagnostics. If Billing is enabled and required, unpaid investigations can be blocked from processing at workflow level.

## 9. Radiology

### License Key

```text
radiology
```

### Purpose

Manages radiology requests, result entry, result images/files, and radiology investigation setup.

### Main Users

- Radiologist
- Radiographer
- Doctor, for requesting/viewing results if allowed

### Features

- Radiology request list
- Radiology result creation
- Radiology result editing
- Radiology result viewing
- Radiology investigation setup
- Radiology parameters
- Result image/file support

### Main Routes

```text
radiology.requests.index
radiology.requests.show
radiology.requests.createResult
radiology.requests.storeResult
radiology.requests.editResult
radiology.requests.updateResult
radiology.investigations.index
radiology.investigations.create
radiology.investigations.edit
radiology.investigations.parameters.index
radiology.investigations.parameters.create
radiology.investigations.parameters.edit
```

### Suggested Permissions

```text
radiology_request.read
radiology_request.update
radiology_result.read
radiology_result.create
radiology_result.update
radiology_result.print
radiology_investigation.read
radiology_investigation.create
radiology_investigation.update
parameter.read
parameter.create
parameter.update
```

### Dependencies

- Core System
- Patient Records

### Optional Dependencies

- Billing

### Sidebar Group

```text
Diagnostics
```

### Access Notes

Radiology and Laboratory are separate license modules but should be grouped together under Diagnostics in the sidebar.

## 10. Pharmacy

### License Key

```text
pharmacy
```

### Purpose

Manages medicines, stock, dispensing, pharmacy transactions, batches, expiry alerts, and pharmacy finance views.

### Main Users

- Pharmacist
- Head of Pharmacy
- Head of Department, where relevant
- Administrator, where allowed

### Features

- Medicine management
- Medicine batches
- Stock inventory
- Stock transactions
- Stock reconciliation
- Prescription dispensing
- Expiry tracking
- Pharmacy bills
- Pharmacy payments
- Pharmacy report

### Main Routes

```text
pharmacy.prescriptions.index
pharmacy.prescriptions.show
pharmacy.medicines.index
pharmacy.medicines.create
pharmacy.stocks.index
pharmacy.stocks.create
pharmacy.stocks.reconciliation
pharmacy.batches.index
pharmacy.transactions.index
pharmacy.transactions.create
pharmacy.transactions.report
pharmacy.expiries.index
pharmacy.finance.bills
pharmacy.finance.payments
pharmacy.finance.payments.receipt
pharmacy.finance.report
pharmacy.finance.report.download
```

### Suggested Permissions

```text
medicine.read
medicine.create
medicine.update
medicine_stock.read
medicine_stock.create
medicine_stock.update
stock_transaction.read
stock_transaction.create
dispense.read
dispense.create
pharmacy_sale.read
pharmacy_sale.create
expiry_alert.read
stock_reconciliation.read
stock_reconciliation.create
stock_reconciliation.approve
```

### Dependencies

- Core System

### Optional Dependencies

- Patient Records
- Clinical Care
- Billing
- Finance
- Reports

### Sidebar Groups

```text
Medications
Payments & Finance
Reports
```

### Access Notes

For standalone pharmacy clients, Patient Records may be optional. For hospital pharmacy dispensing, Patient Records and Clinical Care are useful because prescriptions come from patient visits.

## 11. Billing

### License Key

```text
billing
```

### Purpose

Manages patient bills, bill items, payment verification, receipts, walk-in billing, and insurance billing.

### Main Users

- Accountant
- Finance Officer
- Administrator
- Pharmacy manager, for pharmacy-related bills

### Features

- Bill list
- Bill creation
- Bill editing
- Bill deletion/restoration
- Unpaid bills
- Deleted bills
- Payment verification
- Bill payments
- Receipts
- Walk-in bills
- Insurance billing

### Main Routes

```text
accountant.bills.index
accountant.bills.create
accountant.bills.unpaid
accountant.bills.deleted
accountant.bills.patient-details
accountant.bills.create-walkin
accountant.bills.store
accountant.bills.payments.verify
accountant.bills.payments.verify-now
accountant.bills.payments.create
accountant.bills.payments.store
accountant.bills.show
accountant.bills.edit
accountant.bills.update
accountant.bills.delete
accountant.bills.restore
accountant.insurance-billing
admin.bills.index
admin.bills.show
finance.bills.index
finance.bills.show
```

### Suggested Permissions

```text
bill.read
bill.create
bill.update
bill.delete
bill.restore
bill.print
payment.verify
insurance_billing.read
insurance_billing.create
```

### Dependencies

- Core System

### Optional Dependencies

- Patient Records
- Laboratory
- Radiology
- Pharmacy
- Finance

### Sidebar Group

```text
Billing
```

### Access Notes

Billing should stay separate from Finance. Billing is operational and patient-facing, while Finance is management/accounting-facing.

## 12. Finance

### License Key

```text
finance
```

### Purpose

Manages financial oversight, payments, revenues, expenses, categories, and financial reports.

### Main Users

- Accountant
- Finance Officer
- Administrator
- Medical Director, where allowed

### Features

- Payment list
- Payment receipts
- Patient payment history
- Revenues
- Revenue categories
- Expenses
- Expense categories
- Salary payments
- Finance reports
- Payment reports

### Main Routes

```text
accountant.payments.index
accountant.payments.create
accountant.payments.store
accountant.payments.receipt
accountant.patient-payment-history
finance.payments.index
finance.payments.receipt
finance.expenses.index
finance.revenues.index
admin.payments.index
admin.expenses.index
admin.revenues.index
admin.expense-categories.index
admin.revenue-categories.index
reports.finance.index
reports.finance.search
reports.finance.export
reports.finance.pdf
reports.payments.index
reports.payments.export
reports.payments.pdf
```

### Suggested Permissions

```text
payment.read
payment.create
payment.update
payment.delete
payment.print
expense.read
expense.create
expense.update
expense.delete
revenue.read
revenue.create
revenue.update
revenue.delete
salary_payment.read
salary_payment.create
financial_report.read
financial_report.export
payment_report.read
payment_report.export
```

### Dependencies

- Core System
- Billing

### Sidebar Group

```text
Payments & Finance
```

### Access Notes

Finance should be available to full hospital and enterprise clients. Smaller plans can receive limited payment features through Billing without enabling full Finance.

## 13. Department Management

### License Key

```text
department_management
```

### Purpose

Manages hospital departments, department users, department-level investigations, consumables, stock, usage, and reports.

### Main Users

- Head of Department
- Administrator
- Medical Director

### Features

- Department setup
- Department user list
- Department investigations
- Consumables
- Consumable stock
- Stock usage
- Department reports

### Main Routes

```text
admin.departments.index
admin.departments.create
admin.departments.edit
medical-director.departments.index
medical-director.departments.create
medical-director.departments.edit
department.users.index
department.investigations.index
department.consumables.index
department.stocks.index
department.stock-usage.index
department.reports.index
department.reports.generate
department.reports.pdf
```

### Suggested Permissions

```text
department.read
department.create
department.update
department.delete
department_user.read
investigation.read
consumable.read
consumable.create
consumable.update
consumable_stock.read
consumable_stock.create
consumable_stock.update
consumable_usage.read
consumable_usage.create
department_report.read
department_report.export
```

### Dependencies

- Core System

### Sidebar Group

```text
Departments & Inventory
```

### Access Notes

Department-specific rules should still apply. For example, pharmacy department heads may see pharmacy stock workflows, while lab/radiology department heads may see investigation-related workflows.

## 14. Ward & Bed Management

### License Key

```text
wards_beds
```

### Purpose

Manages wards, beds, admission oversight, bed availability, and inpatient movement.

### Main Users

- Administrator
- Medical Director
- Nurse
- Doctor, where allowed

### Features

- Ward setup
- Bed setup
- Bed status
- Admission list
- Admission discharge
- SAMA records
- Absconded records
- Bed assignment during admission

### Main Routes

```text
admin.wards.index
admin.wards.create
admin.wards.edit
admin.beds.index
admin.beds.create
admin.beds.edit
admin.admissions.index
admin.admissions.discharge
admin.admissions.sama
medical-director.wards.index
medical-director.beds.index
medical-director.admissions.index
medical-director.admissions.discharge
medical-director.admissions.sama
nurse.admissions.index
nurse.admissions.record-absconded
nurse.admissions.record-sama
```

### Suggested Permissions

```text
ward.read
ward.create
ward.update
ward.delete
bed.read
bed.create
bed.update
bed.delete
admission.read
admission.update
admission.discharge
admission.sama
admission.absconded
```

### Dependencies

- Core System
- Clinical Care

### Sidebar Group

```text
Wards & Admissions
```

### Access Notes

Admissions also exist inside Clinical Care, but ward and bed setup/oversight should be grouped separately for hospital installations.

## 15. Reports

### License Key

```text
reports
```

### Purpose

Provides reporting, exports, activity review, financial summaries, patient registers, and department reports.

### Main Users

- Administrator
- Medical Director
- Accountant
- Finance Officer
- Department Head
- Any role with activity report permission

### Features

- My activity report
- Activity reports by department
- Finance report
- Payment report
- Patient register report
- Department report
- Statistics report
- PDF exports
- Excel/CSV exports

### Main Routes

```text
reports.my-activities.index
reports.my-activities.pdf
reports.finance.index
reports.finance.search
reports.finance.export
reports.finance.pdf
reports.payments.index
reports.payments.export
reports.payments.pdf
reports.activities.show
reports.activities.export
reports.activities.pdf
record.patient-register.csv
record.patient-register.pdf
admin.patient-register.csv
admin.patient-register.pdf
medical-director.statistics.index
medical-director.statistics.pdf
department.reports.index
department.reports.generate
department.reports.pdf
```

### Suggested Permissions

```text
report.read
report.export
activity.read
activity.export
financial_report.read
financial_report.export
payment_report.read
payment_report.export
patient_register.read
patient_register.export
department_report.read
department_report.export
statistics_report.read
statistics_report.export
```

### Dependencies

- Core System

### Optional Dependencies

Reports should adapt to enabled modules:

- Finance reports require Finance or Billing.
- Department reports require Department Management.
- Patient register reports require Patient Records.
- Statistics reports require Medical Director or Reports.

### Sidebar Group

```text
Reports
```

### Access Notes

Reports must not expose data from disabled modules. For example, a Diagnostic plan should not see pharmacy reports unless Pharmacy is enabled.

## 16. Medical Director

### License Key

```text
medical_director
```

### Purpose

Provides executive oversight for patient register, admissions, departments, services, investigations, statistics, and financial summaries.

### Main Users

- Medical Director
- Administrator, where allowed

### Features

- Medical Director dashboard
- Patient register oversight
- Patient summary
- Statistics report
- Admissions oversight
- Department oversight
- Ward/bed oversight
- Services oversight
- Investigation oversight
- Revenue and expense visibility

### Main Routes

```text
medical-director.index
medical-director.patient-register.index
medical-director.patient-register.csv
medical-director.patient-register.pdf
medical-director.patient-register.summary
medical-director.statistics.index
medical-director.statistics.pdf
medical-director.admissions.index
medical-director.departments.index
medical-director.wards.index
medical-director.investigations.index
medical-director.services.index
medical-director.file-types.index
medical-director.expenses.index
medical-director.revenues.index
```

### Suggested Permissions

```text
medical_director_dashboard.read
patient.read
patient_register.read
statistics_report.read
admission.read
department.read
ward.read
bed.read
service.read
investigation.read
expense.read
revenue.read
```

### Dependencies

- Core System
- Patient Records
- Reports

### Optional Dependencies

- Billing
- Finance
- Department Management
- Ward & Bed Management

### Sidebar Groups

```text
Dashboard
Patients
Reports
Wards & Admissions
Departments & Inventory
Payments & Finance
Administration
```

### Access Notes

This module is more of a management workspace than a data module. It should only display sections for modules enabled by the license.

## 17. Audit Trail

### License Key

```text
audit
```

### Purpose

Tracks important user actions and supports accountability, compliance, and activity review.

### Main Users

- Administrator
- Medical Director
- Auditor/compliance user, if added later

### Features

- Audit logs
- User activity history
- Sensitive action tracking
- Activity reports
- Model observer-based audit records

### Main Models

```text
AuditLog
VisitActivity
```

### Suggested Permissions

```text
audit.read
audit.export
activity.read
activity.export
```

### Dependencies

- Core System

### Optional Dependencies

- Reports

### Sidebar Groups

```text
Reports
System
```

### Access Notes

Audit should not be bypassed by sidebar visibility. Even if the audit UI is not enabled, critical audit recording can still remain active internally if required.

## 18. Data Synchronization

### License Key

```text
synchronization
```

### Purpose

Supports synchronization between local hospital installations and remote/online servers.

### Main Users

- Administrator
- Technical support
- Enterprise operator

### Features

- Sync dashboard
- Sync operations
- Sync conflict tracking
- Sync API
- Sync health checks
- Syncable model support
- Queue-based sync jobs

### Main Routes

```text
admin.sync.index
admin.sync.dashboards
api/v1/sync/records
api/v1/sync/batch
api/v1/sync/pending
api/v1/sync/status/{syncUuid}
api/v1/sync/health
api/v1/health
```

### Main Models

```text
SyncOperation
SyncConflict
```

### Suggested Permissions

```text
sync.read
sync.create
sync.update
sync.retry
sync.resolve_conflict
```

### Dependencies

- Core System

### Sidebar Group

```text
System
```

### Access Notes

Sync is a premium/enterprise module. API routes must be protected by sync token middleware and module/license checks where practical.

## 19. Backup & Maintenance

### License Key

```text
maintenance
```

### Purpose

Provides technical maintenance utilities for system support, updates, backup, and local network tools.

### Main Users

- Administrator
- Technical support
- Owner/super-admin if added later

### Features

- Database backup
- System update
- WiFi sharing status
- WiFi sharing connection
- Maintenance dashboard, if added later

### Main Routes

```text
admin.backup.index
admin.backup.store
admin.system.update
admin.system.update.run
wifi-sharing.status
wifi-sharing.connect
```

### Suggested Permissions

```text
backup.read
backup.create
system.update
maintenance.read
wifi_sharing.read
wifi_sharing.manage
```

### Dependencies

- Core System

### Sidebar Group

```text
System
```

### Access Notes

Maintenance features should be limited to trusted users. Backup and system update actions should always be logged.

## Recommended Module Dependencies

| Module | Requires |
| --- | --- |
| Core System | None |
| User & Access Control | Core System |
| Patient Records | Core System |
| Clinical Care | Core System, Patient Records |
| Doctor Workspace | Core System, Patient Records, Clinical Care |
| Nursing Workspace | Core System, Patient Records, Clinical Care |
| Maternity / Midwife | Core System, Patient Records |
| Laboratory | Core System, Patient Records |
| Radiology | Core System, Patient Records |
| Pharmacy | Core System |
| Billing | Core System |
| Finance | Core System, Billing |
| Department Management | Core System |
| Ward & Bed Management | Core System, Clinical Care |
| Reports | Core System |
| Medical Director | Core System, Patient Records, Reports |
| Audit Trail | Core System |
| Data Synchronization | Core System |
| Backup & Maintenance | Core System |

## Recommended Plan Coverage

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

## Implementation Notes

Every module should have:

- A stable `license_module` key.
- One or more sidebar items in the `modules` table.
- A professional `sidebar_group`.
- Permissions using `resource.action` naming.
- Route middleware using `module:{license_module}`.
- Admin-controlled user module access.
- Seeder entries that attach permissions and recommended roles.

## Final Rule

Modules are product features. Sidebar groups are user experience. Permissions are action control.

Do not mix them.

```text
License module = what the client bought
Module user access = which users can enter
Permission = what actions the user can perform
Sidebar group = where the action appears
```
