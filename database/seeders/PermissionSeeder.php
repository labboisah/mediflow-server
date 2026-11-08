<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $permissions = [

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRATION MODULE
        |--------------------------------------------------------------------------
        */
            ['module'=>'Administration','name'=>'department.create','display_name'=>'Create Department','description'=>'Create new department'],
            ['module'=>'Administration','name'=>'department.read','display_name'=>'View Departments','description'=>'View departments'],
            ['module'=>'Administration','name'=>'department.update','display_name'=>'Update Department','description'=>'Edit department'],
            ['module'=>'Administration','name'=>'department.delete','display_name'=>'Delete Department','description'=>'Delete department'],

            ['module'=>'Administration','name'=>'service.create','display_name'=>'Create Service','description'=>'Create service'],
            ['module'=>'Administration','name'=>'service.read','display_name'=>'View Services','description'=>'View services'],
            ['module'=>'Administration','name'=>'service.update','display_name'=>'Update Service','description'=>'Edit service'],
            ['module'=>'Administration','name'=>'service.delete','display_name'=>'Delete Service','description'=>'Delete service'],
            ['module'=>'Billing','name'=>'service_request.read','display_name'=>'View Service Requests','description'=>'View service requests'],

            ['module'=>'Administration','name'=>'ward.create','display_name'=>'Create ward','description'=>'Create ward'],
            ['module'=>'Administration','name'=>'ward.read','display_name'=>'View wards','description'=>'View wards'],
            ['module'=>'Administration','name'=>'ward.update','display_name'=>'Update ward','description'=>'Edit ward'],
            ['module'=>'Administration','name'=>'ward.delete','display_name'=>'Delete ward','description'=>'Delete service'],

            ['module'=>'Administration','name'=>'bed.create','display_name'=>'Create bed','description'=>'Create bed'],
            ['module'=>'Administration','name'=>'bed.read','display_name'=>'View beds','description'=>'View beds'],
            ['module'=>'Administration','name'=>'bed.update','display_name'=>'Update bed','description'=>'Edit bed'],
            ['module'=>'Administration','name'=>'bed.delete','display_name'=>'Delete bed','description'=>'Delete bed'],

            ['module'=>'Administration','name'=>'user.create','display_name'=>'Create User','description'=>'Create system user'],
            ['module'=>'Administration','name'=>'user.read','display_name'=>'View Users','description'=>'View users'],
            ['module'=>'Administration','name'=>'user.update','display_name'=>'Update User','description'=>'Edit user'],
            ['module'=>'Administration','name'=>'user.delete','display_name'=>'Delete User','description'=>'Delete user'],

            ['module'=>'Administration','name'=>'permission.create','display_name'=>'Create permission','description'=>'Create permission'],
            ['module'=>'Administration','name'=>'permission.read','display_name'=>'View permissions','description'=>'View permissions'],
            ['module'=>'Administration','name'=>'permission.update','display_name'=>'Update permission','description'=>'Edit permission'],
            ['module'=>'Administration','name'=>'permission.delete','display_name'=>'Delete permission','description'=>'Delete permission'],
            ['module'=>'Administration','name'=>'permission.assign','display_name'=>'assign permission','description'=>'assign permission'],
            ['module'=>'Administration','name'=>'permission.revoke','display_name'=>'revoke permission','description'=>'revoke permission'],

            ['module'=>'Administration','name'=>'temporary_permission.create','display_name'=>'Create permission','description'=>'Create permission'],
            ['module'=>'Administration','name'=>'temporary_permission.read','display_name'=>'View permissions','description'=>'View permissions'],
            ['module'=>'Administration','name'=>'temporary_permission.update','display_name'=>'Update permission','description'=>'Edit permission'],
            ['module'=>'Administration','name'=>'temporary_permission.delete','display_name'=>'Delete permission','description'=>'Delete permission'],
            ['module'=>'Administration','name'=>'temporary_permission.assign','display_name'=>'assign permission','description'=>'assign permission'],
            ['module'=>'Administration','name'=>'temporary_permission.revoke','display_name'=>'revoke permission','description'=>'revoke permission'],

            ['module'=>'Administration','name'=>'report.generate','display_name'=>'Create Report','description'=>'Create Report'],
            ['module'=>'Administration','name'=>'report.read','display_name'=>'View Reports','description'=>'View Reports'],
            ['module'=>'Administration','name'=>'report.print','display_name'=>'Update Report','description'=>'Edit Report'],
            ['module'=>'Administration','name'=>'report.download','display_name'=>'Delete Report','description'=>'Delete Report'],
            ['module'=>'Administration','name'=>'activity.read','display_name'=>'View Activities','description'=>'View activity records'],

            ['module'=>'Administration','name'=>'role.create','display_name'=>'Create Role','description'=>'Create role'],
            ['module'=>'Administration','name'=>'role.read','display_name'=>'View Roles','description'=>'View roles'],
            ['module'=>'Administration','name'=>'role.update','display_name'=>'Update Role','description'=>'Edit role'],
            ['module'=>'Administration','name'=>'role.delete','display_name'=>'Delete Role','description'=>'Delete role'],

            ['module'=>'Administration','name'=>'investigation.create','display_name'=>'Create investigation','description'=>'Create investigation'],
            ['module'=>'Administration','name'=>'investigation.read','display_name'=>'View investigations','description'=>'View investigations'],
            ['module'=>'Administration','name'=>'investigation.update','display_name'=>'Update investigation','description'=>'Edit investigation'],
            ['module'=>'Administration','name'=>'investigation.delete','display_name'=>'Delete investigation','description'=>'Delete investigation'],

            ['module'=>'Administration','name'=>'permission.read','display_name'=>'View Permissions','description'=>'View permissions'],

            /*
            |--------------------------------------------------------------------------
            | PATIENT MANAGEMENT
            |--------------------------------------------------------------------------
            */
            ['module'=>'Patient','name'=>'patient.create','display_name'=>'Create Patient','description'=>'Register patient'],
            ['module'=>'Patient','name'=>'patient.read','display_name'=>'View Patients','description'=>'View patients'],
            ['module'=>'Patient','name'=>'patient.update','display_name'=>'Update Patient','description'=>'Edit patient'],
            ['module'=>'Patient','name'=>'patient.delete','display_name'=>'Delete Patient','description'=>'Delete patient'],

            ['module'=>'Patient','name'=>'patient_history.create','display_name'=>'Create Patient','description'=>'Register patient'],
            ['module'=>'Patient','name'=>'patient_history.read','display_name'=>'View Patients','description'=>'View patients'],
            ['module'=>'Patient','name'=>'patient_history.update','display_name'=>'Update Patient','description'=>'Edit patient'],
            ['module'=>'Patient','name'=>'patient_history.delete','display_name'=>'Delete Patient','description'=>'Delete patient'],

            ['module'=>'Patient','name'=>'visit.create','display_name'=>'Create Visit','description'=>'Create visit'],
            ['module'=>'Patient','name'=>'visit.read','display_name'=>'View Visits','description'=>'View visits'],

            ['module'=>'Billing','name'=>'bill.create','display_name'=>'Create Bill','description'=>'Generate bill'],
            ['module'=>'Billing','name'=>'bill.read','display_name'=>'View Bills','description'=>'View bills'],
            ['module'=>'Billing','name'=>'bill.print','display_name'=>'Print Bills','description'=>'Print bills'],

            ['module'=>'Billing','name'=>'payment.create','display_name'=>'Create Payment','description'=>'Record payment'],
            ['module'=>'Billing','name'=>'payment.read','display_name'=>'View Payments','description'=>'View payments'],
            ['module'=>'Billing','name'=>'payment.print','display_name'=>'Print Payments','description'=>'Print payment receipts'],

            /*
            |--------------------------------------------------------------------------            | MIDWIFERY
            |--------------------------------------------------------------------------
            */
            ['module'=>'Midwifery','name'=>'antenatal_care.create','display_name'=>'Create Antenatal Care','description'=>'Create antenatal care record'],
            ['module'=>'Midwifery','name'=>'antenatal_care.read','display_name'=>'View Antenatal Care','description'=>'View antenatal care records'],
            ['module'=>'Midwifery','name'=>'antenatal_care.update','display_name'=>'Update Antenatal Care','description'=>'Edit antenatal care record'],
            ['module'=>'Midwifery','name'=>'antenatal_care.delete','display_name'=>'Delete Antenatal Care','description'=>'Delete antenatal care record'],

            ['module'=>'Midwifery','name'=>'labour.create','display_name'=>'Create Labour Record','description'=>'Create labour management record'],
            ['module'=>'Midwifery','name'=>'labour.read','display_name'=>'View Labour Records','description'=>'View labour management records'],
            ['module'=>'Midwifery','name'=>'labour.update','display_name'=>'Update Labour Record','description'=>'Edit labour management record'],
            ['module'=>'Midwifery','name'=>'labour.delete','display_name'=>'Delete Labour Record','description'=>'Delete labour management record'],
            ['module'=>'Midwifery','name'=>'labour_progress.create','display_name'=>'Create Labour Progress','description'=>'Create labour progress entry'],
            ['module'=>'Midwifery','name'=>'labour_progress.read','display_name'=>'View Labour Progress','description'=>'View labour progress entries'],
            ['module'=>'Midwifery','name'=>'labour_progress.update','display_name'=>'Update Labour Progress','description'=>'Update labour progress entry'],
            ['module'=>'Midwifery','name'=>'labour_progress.delete','display_name'=>'Delete Labour Progress','description'=>'Delete labour progress entry'],

            ['module'=>'Midwifery','name'=>'delivery.create','display_name'=>'Create Delivery','description'=>'Create delivery record'],
            ['module'=>'Midwifery','name'=>'delivery.read','display_name'=>'View Delivery','description'=>'View delivery records'],
            ['module'=>'Midwifery','name'=>'delivery.update','display_name'=>'Update Delivery','description'=>'Update delivery record'],
            ['module'=>'Midwifery','name'=>'delivery.delete','display_name'=>'Delete Delivery','description'=>'Delete delivery record'],
            ['module'=>'Midwifery','name'=>'newborn.create','display_name'=>'Create Newborn','description'=>'Create newborn record'],
            ['module'=>'Midwifery','name'=>'newborn.read','display_name'=>'View Newborn','description'=>'View newborn records'],
            ['module'=>'Midwifery','name'=>'newborn.update','display_name'=>'Update Newborn','description'=>'Update newborn record'],
            ['module'=>'Midwifery','name'=>'newborn.delete','display_name'=>'Delete Newborn','description'=>'Delete newborn record'],

            ['module'=>'Midwifery','name'=>'newborn_examination.create','display_name'=>'Create Newborn Examination','description'=>'Create newborn examination record'],
            ['module'=>'Midwifery','name'=>'newborn_examination.read','display_name'=>'View Newborn Examination','description'=>'View newborn examination records'],
            ['module'=>'Midwifery','name'=>'newborn_examination.update','display_name'=>'Update Newborn Examination','description'=>'Update newborn examination record'],
            ['module'=>'Midwifery','name'=>'newborn_examination.delete','display_name'=>'Delete Newborn Examination','description'=>'Delete newborn examination record'],

            ['module'=>'Midwifery','name'=>'postnatal_examination.create','display_name'=>'Create Postnatal Examination','description'=>'Create postnatal examination record'],
            ['module'=>'Midwifery','name'=>'postnatal_examination.read','display_name'=>'View Postnatal Examination','description'=>'View postnatal examination records'],
            ['module'=>'Midwifery','name'=>'postnatal_examination.update','display_name'=>'Update Postnatal Examination','description'=>'Update postnatal examination record'],
            ['module'=>'Midwifery','name'=>'postnatal_examination.delete','display_name'=>'Delete Postnatal Examination','description'=>'Delete postnatal examination record'],

            ['module'=>'Midwifery','name'=>'child_follow_up.create','display_name'=>'Create Child Follow-up','description'=>'Create child follow-up record'],
            ['module'=>'Midwifery','name'=>'child_follow_up.read','display_name'=>'View Child Follow-up','description'=>'View child follow-up records'],
            ['module'=>'Midwifery','name'=>'child_follow_up.update','display_name'=>'Update Child Follow-up','description'=>'Update child follow-up record'],
            ['module'=>'Midwifery','name'=>'child_follow_up.delete','display_name'=>'Delete Child Follow-up','description'=>'Delete child follow-up record'],

            /*
            |--------------------------------------------------------------------------            | CLINICAL
            |--------------------------------------------------------------------------
            */
            ['module'=>'Clinical','name'=>'vital_sign.create','display_name'=>'Record Vital Signs','description'=>'Record vital signs'],
            ['module'=>'Clinical','name'=>'vital_sign.read','display_name'=>'View Vital Signs','description'=>'View vital signs'],
            ['module'=>'Clinical','name'=>'vital_sign.update','display_name'=>'View Vital Signs','description'=>'View vital signs'],

            ['module'=>'Clinical','name'=>'fluid_balance.create','display_name'=>'Record Fluid Balance','description'=>'Record Fluid Balance'],
            ['module'=>'Clinical','name'=>'fluid_balance.read','display_name'=>'View Fluid Balance','description'=>'View Fluid Balance'],
            ['module'=>'Clinical','name'=>'fluid_balance.update','display_name'=>'View Fluid Balance','description'=>'View vital signs'],

            ['module'=>'Clinical','name'=>'drug_chart.create','display_name'=>'Record Drug Chart','description'=>'Record Drug Chart'],
            ['module'=>'Clinical','name'=>'drug_chart.read','display_name'=>'View Drug Chart','description'=>'View Drug Chart'],
            ['module'=>'Clinical','name'=>'drug_chart.update','display_name'=>'View Drug Chart','description'=>'View vital signs'],
            ['module'=>'Clinical','name'=>'drug_chart.delete','display_name'=>'View Drug Chart','description'=>'View vital signs'],

            ['module'=>'Clinical','name'=>'observation.create','display_name'=>'Create Observation','description'=>'Record observation'],
            ['module'=>'Clinical','name'=>'observation.read','display_name'=>'View Observation','description'=>'View observation'],
            ['module'=>'Clinical','name'=>'observation.update','display_name'=>'View Observation','description'=>'View observation'],

            ['module'=>'Clinical','name'=>'nursing_note.create','display_name'=>'Create Nursing Note','description'=>'Record Nursing Note'],
            ['module'=>'Clinical','name'=>'nursing_note.read','display_name'=>'View Nursing Note','description'=>'View Nursing Note'],
            ['module'=>'Clinical','name'=>'nursing_note.update','display_name'=>'View Nursing Note','description'=>'View Nursing Note'],

            ['module'=>'Clinical','name'=>'prescription.create','display_name'=>'Create Prescription','description'=>'Create prescription'],
            ['module'=>'Clinical','name'=>'prescription.read','display_name'=>'View Prescriptions','description'=>'View prescriptions'],
            ['module'=>'Clinical','name'=>'prescription.update','display_name'=>'View Prescriptions','description'=>'View prescriptions'],

            ['module'=>'Clinical','name'=>'admission.create','display_name'=>'Create admission','description'=>'Create admission'],
            ['module'=>'Clinical','name'=>'admission.read','display_name'=>'View admissions','description'=>'View admissions'],
            ['module'=>'Clinical','name'=>'admission.update','display_name'=>'View admissions','description'=>'View admissions'],

            ['module'=>'Clinical','name'=>'discharge.create','display_name'=>'Create Prescription','description'=>'Create prescription'],
            ['module'=>'Clinical','name'=>'discharge.read','display_name'=>'View Prescriptions','description'=>'View prescriptions'],
            ['module'=>'Clinical','name'=>'discharge.update','display_name'=>'View Prescriptions','description'=>'View prescriptions'],
            /*
            |--------------------------------------------------------------------------
            | PHARMACY
            |--------------------------------------------------------------------------
            */
            ['module'=>'Pharmacy','name'=>'medicine.create','display_name'=>'Create Medicine','description'=>'Add medicine'],
            ['module'=>'Pharmacy','name'=>'medicine.read','display_name'=>'View Medicines','description'=>'View medicines'],
            ['module'=>'Pharmacy','name'=>'medicine.update','display_name'=>'View Medicines','description'=>'View medicines'],
            ['module'=>'Pharmacy','name'=>'medicine.delete','display_name'=>'View Medicines','description'=>'View medicines'],
            
            ['module'=>'Pharmacy','name'=>'medicine_stock.create','display_name'=>'Add Medicine Stock','description'=>'Add stock'],
            ['module'=>'Pharmacy','name'=>'medicine_stock.read','display_name'=>'View Medicine Stock','description'=>'View stock'],
            ['module'=>'Pharmacy','name'=>'medicine_stock.update','display_name'=>'View Medicine Stock','description'=>'View stock'],
            ['module'=>'Pharmacy','name'=>'medicine_stock.delete','display_name'=>'View Medicine Stock','description'=>'View stock'],

            ['module'=>'Pharmacy','name'=>'expiry_alert.read','display_name'=>'View Medicine Stock','description'=>'View stock'],

            ['module'=>'Pharmacy','name'=>'pharmacy_sale.create','display_name'=>'Create Sale','description'=>'Process pharmacy sale'],
            ['module'=>'Pharmacy','name'=>'pharmacy_sale.read','display_name'=>'View Sales','description'=>'View sales'],

            ['module'=>'Pharmacy','name'=>'dispense.create','display_name'=>'Dispense Medicine','description'=>'Dispense medication'],
            ['module'=>'Pharmacy','name'=>'dispense.read','display_name'=>'View Dispensed Medicines','description'=>'View dispensed drugs'],

            /*
            |--------------------------------------------------------------------------
            | LAB / RADIOLOGY
            |--------------------------------------------------------------------------
            */
            ['module'=>'Laboratory','name'=>'investigation_request.create','display_name'=>'Create Investigation Request','description'=>'Request investigation'],
            ['module'=>'Laboratory','name'=>'investigation_request.read','display_name'=>'View Investigation Requests','description'=>'View requests'],
            ['module'=>'Laboratory','name'=>'investigation_request.update','display_name'=>'View Investigation Requests','description'=>'View requests'],
            ['module'=>'Laboratory','name'=>'investigation_request.delete','display_name'=>'View Investigation Requests','description'=>'View requests'],
            
            ['module'=>'Laboratory','name'=>'investigation_result.create','display_name'=>'Create Investigation Result','description'=>'Enter result'],
            ['module'=>'Laboratory','name'=>'investigation_result.read','display_name'=>'View Investigation Results','description'=>'View results'],
            ['module'=>'Laboratory','name'=>'investigation_result.update','display_name'=>'View Investigation Results','description'=>'View results'],
            ['module'=>'Laboratory','name'=>'investigation_result.delete','display_name'=>'View Investigation Results','description'=>'View results'],
            ['module'=>'Laboratory','name'=>'investigation_result.print','display_name'=>'View Investigation Results','description'=>'View results'],
            ['module'=>'Laboratory','name'=>'investigation_result.download','display_name'=>'View Investigation Results','description'=>'View results'],

            /*
            |--------------------------------------------------------------------------
            | INVENTORY
            |--------------------------------------------------------------------------
            */
            ['module'=>'Inventory','name'=>'consumable.create','display_name'=>'Create Consumable','description'=>'Add consumable'],
            ['module'=>'Inventory','name'=>'consumable.read','display_name'=>'View Consumables','description'=>'View consumables'],
            ['module'=>'Inventory','name'=>'consumable.update','display_name'=>'View Consumables','description'=>'View consumables'],
            ['module'=>'Inventory','name'=>'consumable.delete','display_name'=>'View Consumables','description'=>'View consumables'],
            
            ['module'=>'Inventory','name'=>'consumable_stock.create','display_name'=>'Add Consumable Stock','description'=>'Add stock'],
            ['module'=>'Inventory','name'=>'consumable_stock.read','display_name'=>'View Consumable Stock','description'=>'View stock'],
            ['module'=>'Inventory','name'=>'consumable_stock.update','display_name'=>'View Consumable Stock','description'=>'View stock'],
            ['module'=>'Inventory','name'=>'consumable_stock.delete','display_name'=>'View Consumable Stock','description'=>'View stock'],

            /*
            |--------------------------------------------------------------------------
            | FINANCE
            |--------------------------------------------------------------------------
            */
            ['module'=>'Finance','name'=>'expense.create','display_name'=>'Create Expense','description'=>'Record expense'],
            ['module'=>'Finance','name'=>'expense.read','display_name'=>'View Expenses','description'=>'View expenses'],
            ['module'=>'Finance','name'=>'expense.update','display_name'=>'View Expenses','description'=>'View expenses'],
            ['module'=>'Finance','name'=>'expense.delete','display_name'=>'View Expenses','description'=>'View expenses'],

            ['module'=>'Finance','name'=>'revenue.create','display_name'=>'Create Revenue','description'=>'Record revenue'],
            ['module'=>'Finance','name'=>'revenue.read','display_name'=>'View Revenues','description'=>'View revenues'],
            ['module'=>'Finance','name'=>'revenue.update','display_name'=>'Update Revenue','description'=>'Edit revenue'],
            ['module'=>'Finance','name'=>'revenue.delete','display_name'=>'Delete Revenue','description'=>'Delete revenue'],
            
            ['module'=>'Finance','name'=>'department_report.generate','display_name'=>'View Department Report','description'=>'View financial reports'],
            ['module'=>'Finance','name'=>'department_report.read','display_name'=>'View Department Report','description'=>'View financial reports'],
            ['module'=>'Finance','name'=>'department_report.print','display_name'=>'View Department Report','description'=>'View financial reports'],
            ['module'=>'Finance','name'=>'department_report.share','display_name'=>'View Department Report','description'=>'View financial reports'],
            ['module'=>'Finance','name'=>'department_report.download','display_name'=>'View Department Report','description'=>'View financial reports'],

        ];

        foreach ($permissions as $perm) {

            Permission::updateOrCreate(
                ['name' => $perm['name']],
                [
                    'display_name' => $perm['display_name'],
                    'description' => $perm['description'],
                    'module' => $perm['module']
                ]
            );

        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Assign permissions to their respective roles.
     */
    private function assignPermissionsToRoles(): void
    {
        $roles = Role::whereIn('name', [
            'administrator',
            'record',
            'accountant',
            'finance_officer',
            'nurse',
            'midwife',
            'doctor',
            'pharmacist',
            'lab_scientist',
            'lab_technician',
            'radiologist',
            'radiographer',
            'head_of_department',
        ])->get()->keyBy('name');

        /*
        |--------------------------------------------------------------------------
        | Administrator (ALL PERMISSIONS)
        |--------------------------------------------------------------------------
        */

        $adminPermissions = [
            'department.create',
            'department.read',
            'department.update',
            'department.delete',

            'ward.create',
            'ward.read',
            'ward.update',
            'ward.delete',

            'bed.create',
            'bed.read',
            'bed.update',
            'bed.delete',

            'service.create',
            'service.read',
            'service.update',
            'service.delete',
            'service_request.read',

            'user.create',
            'user.read',
            'user.update',
            'user.delete',

            'role.create',
            'role.read',
            'role.update',
            'role.delete',

            'investigation.create',
            'investigation.read',
            'investigation.update',
            'investigation.delete',

            'report.generate',
            'report.read',
            'report.print',
            'report.download',

            'permission.create',
            'permission.read',
            'permission.update',
            'permission.delete',
            'permission.assign',
            'permission.revoke',

            'temporary_permission.create',
            'temporary_permission.read',
            'temporary_permission.update',
            'temporary_permission.delete',
            'temporary_permission.assign',
            'temporary_permission.revoke',


        ];
        
        if (isset($roles['administrator'])) {
            $roles['administrator']->sync($adminPermissions);
        }

        /*
        |--------------------------------------------------------------------------
        | Record Officer
        |--------------------------------------------------------------------------
        */
        if (isset($roles['record'])) {
            $roles['record']->sync([
                'patient.create','patient.read','patient.update',
                'visit.create','visit.read'
            ]);
        }

        if (isset($roles['accountant'])) {
            $roles['accountant']->sync([
                'bill.create','bill.read',
                'payment.create','payment.read',

            ]);
        }

        if (isset($roles['finance_officer'])) {
            $roles['finance_officer']->sync([
                'bill.read',
                'payment.read',
                'expense.create',
                'expense.read',
                'expense.update',
                'expense.delete',
                'revenue.create',
                'revenue.read',
                'revenue.update',
                'revenue.delete',
                'report.read',
                'report.download',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Nurse
        |--------------------------------------------------------------------------
        */
        if (isset($roles['nurse'])) {
            $roles['nurse']->sync([
                'patient.read',
                'vital_sign.create','vital_sign.read','vital_sign.update',
                'fluid_balance.create','fluid_balance.read','fluid_balance.update',
                'observation.create','observation.read','observation.update',
                'nursing_note.create','nursing_note.read','nursing_note.update',
                'drug_chart.read','drug_chart.update',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Midwife
        |--------------------------------------------------------------------------
        */
        if (isset($roles['midwife'])) {
            $roles['midwife']->sync([
                'patient.read',
                'visit.create','visit.read',
                'patient_history.read',
                'prescription.create','prescription.read','prescription.update',
                'drug_chart.create','drug_chart.read','drug_chart.update','drug_chart.delete',
                'admission.create','admission.read','admission.update',
                'discharge.create','discharge.read','discharge.update',
                'observation.create','observation.read','observation.update',
                'nursing_note.create','nursing_note.read','nursing_note.update',
                'antenatal_care.create','antenatal_care.read','antenatal_care.update','antenatal_care.delete',
                'labour.create','labour.read','labour.update','labour.delete',
                'labour_progress.create','labour_progress.read','labour_progress.update','labour_progress.delete',
                'delivery.create','delivery.read','delivery.update','delivery.delete',
                'newborn.create','newborn.read','newborn.update','newborn.delete',
                'newborn_examination.create','newborn_examination.read','newborn_examination.update','newborn_examination.delete',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Doctor
        |--------------------------------------------------------------------------
        */
        if (isset($roles['doctor'])) {
            $roles['doctor']->sync([
                'patient.read',
                'visit.read',
                'prescription.create','prescription.read','prescription.update',
                'investigation_request.create','investigation_request.read',
                'patient_history.read',
                'admission.create','admission.read',
                'discharge.create','discharge.read',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Pharmacist
        |--------------------------------------------------------------------------
        */
        if (isset($roles['pharmacist'])) {
            $roles['pharmacist']->sync([
                'pharmacy_sale.create','pharmacy_sale.read',
                'dispense.create','dispense.read',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Lab Scientist
        |--------------------------------------------------------------------------
        */
        if (isset($roles['lab_scientist'])) {
            $roles['lab_scientist']->sync([
                'investigation_request.read',
                'investigation_result.create',
                'investigation_result.read',
                'investigation_result.update',
                'department_report.read',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Lab Technician
        |--------------------------------------------------------------------------
        */
        if (isset($roles['lab_technician'])) {
            $roles['lab_technician']->sync([
                'investigation_request.read',
                'investigation_result.read',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Radiologist
        |--------------------------------------------------------------------------
        */
        if (isset($roles['radiologist'])) {
            $roles['radiologist']->sync([
                'investigation_request.read',
                'investigation_result.create',
                'investigation_result.read',
                'investigation_result.update',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Radiographer
        |--------------------------------------------------------------------------
        */
        if (isset($roles['radiographer'])) {
            $roles['radiographer']->sync([
                'investigation_request.read',
                'investigation_result.read',
            ]);
        }

        if (isset($roles['head_of_department'])) {
            $roles['head_of_department']->sync([
                'consumable.create',
                'consumable.read',
                'consumable.update',
                'consumable.delete',
                
                'consumable_stock.create',
                'consumable_stock.read',
                'consumable_stock.update',
                'consumable_stock.delete',

                'investigation.create',
                'investigation.read',
                'investigation.update',
                'investigation.delete',
                
                'department_report.generate',
                'department_report.read',
                'department_report.print',
                'department_report.share',
                'department_report.download',

                'temporary_permission.assign',
                'temporary_permission.revoke',

                'user.read',
                'medicine.create',
                'medicine.read',
                'medicine.update',
                'medicine.delete',

                'medicine_stock.create',
                'medicine_stock.read',
                'medicine_stock.update',
                'medicine_stock.delete',

                'expiry_alert.read',
            ]);
        }
    }
}
