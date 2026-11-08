<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class AccountantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create accountant role
        $accountantRole = Role::firstOrCreate(['name' => 'accountant']);

        // Create permissions for accountant
        $permissions = [
            'create_bill' => 'Create bills/invoices',
            'view_bill' => 'View bills/invoices',
            'edit_bill' => 'Edit bills',
            'delete_bill' => 'Delete bills',
            'record_payment' => 'Record payments',
            'view_payment' => 'View payment history',
            'manage_insurance_billing' => 'Manage insurance/NHIS billing',
            'generate_financial_report' => 'Generate financial reports',
            'view_payment_method' => 'View payment methods',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Assign permissions to accountant role
        $accountantRole->permissions()->sync(
            Permission::whereIn('name', array_keys($permissions))->pluck('id')
        );

        
    }
}
