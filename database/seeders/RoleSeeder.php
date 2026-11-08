<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'administrator',
                'display_name' => 'Administrator',
                'description' => 'Hospital administrator with full hospital access inside activated plan modules',
            ],

            [
                'name' => 'doctor',
                'display_name' => 'Doctor',
                'description' => 'Responsible for diagnosing patients, prescribing treatment, and requesting investigations',
            ],

            [
                'name' => 'nurse',
                'display_name' => 'Nurse',
                'description' => 'Handles patient care including vital signs, observations, and nursing notes',
            ],

            [
                'name' => 'midwife',
                'display_name' => 'Midwife',
                'description' => 'Manages antenatal care, visits, medication, labour, delivery, newborn care, and postnatal care',
            ],

            [
                'name' => 'record',
                'display_name' => 'Record Officer',
                'description' => 'Manages patient registration, records, and visits',
            ],

            [
                'name' => 'accountant',
                'display_name' => 'Accountant',
                'description' => 'billing, and payment',
            ],

            [
                'name' => 'finance_officer',
                'display_name' => 'Finance Officer',
                'description' => 'Mini admin access for finance reporting, expenses, and revenue records',
            ],

            [
                'name' => 'lab_scientist',
                'display_name' => 'Lab Scientist',
                'description' => 'Conducts laboratory investigations, validates and submits results',
            ],

            [
                'name' => 'lab_technician',
                'display_name' => 'Lab Technician',
                'description' => 'Assists in laboratory procedures and sample processing',
            ],

            [
                'name' => 'radiologist',
                'display_name' => 'Radiologist',
                'description' => 'Interprets radiology results and provides diagnostic reports',
            ],

            [
                'name' => 'radiographer',
                'display_name' => 'Radiographer',
                'description' => 'Performs imaging procedures such as X-rays and scans',
            ],

            [
                'name' => 'pharmacist',
                'display_name' => 'Pharmacist',
                'description' => 'Manages medications, dispensing, pharmacy sales, and drug inventory',
            ],

            [
                'name' => 'pharmacy_technician',
                'display_name' => 'Pharmacy Technician',
                'description' => 'Supports pharmacy sales and prescription dispensing without pharmacy management access',
            ],

            [
                'name' => 'head_of_department',
                'display_name' => 'Head of Department',
                'description' => 'Manages consumables, stock, generate financial report, and general inventory',
            ],

        ];

        foreach ($roles as $roleData) {

            Role::updateOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description']
                ]
            );

        }
    }
}
