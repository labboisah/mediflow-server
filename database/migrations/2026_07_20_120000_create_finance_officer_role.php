<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $roleId = DB::table('roles')->where('name', 'finance_officer')->value('id');

        if ($roleId) {
            DB::table('roles')->where('id', $roleId)->update([
                'display_name' => 'Finance Officer',
                'description' => 'Mini admin access for finance reporting, expenses, and revenue records',
                'updated_at' => $now,
            ]);
        } else {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'finance_officer',
                'display_name' => 'Finance Officer',
                'description' => 'Mini admin access for finance reporting, expenses, and revenue records',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissions = [
            ['module' => 'Billing', 'name' => 'bill.read', 'display_name' => 'View Bills', 'description' => 'View bills'],
            ['module' => 'Billing', 'name' => 'payment.read', 'display_name' => 'View Payments', 'description' => 'View payments'],
            ['module' => 'Finance', 'name' => 'expense.create', 'display_name' => 'Create Expense', 'description' => 'Record expense'],
            ['module' => 'Finance', 'name' => 'expense.read', 'display_name' => 'View Expenses', 'description' => 'View expenses'],
            ['module' => 'Finance', 'name' => 'expense.update', 'display_name' => 'Update Expense', 'description' => 'Edit expense'],
            ['module' => 'Finance', 'name' => 'expense.delete', 'display_name' => 'Delete Expense', 'description' => 'Delete expense'],
            ['module' => 'Finance', 'name' => 'revenue.create', 'display_name' => 'Create Revenue', 'description' => 'Record revenue'],
            ['module' => 'Finance', 'name' => 'revenue.read', 'display_name' => 'View Revenues', 'description' => 'View revenues'],
            ['module' => 'Finance', 'name' => 'revenue.update', 'display_name' => 'Update Revenue', 'description' => 'Edit revenue'],
            ['module' => 'Finance', 'name' => 'revenue.delete', 'display_name' => 'Delete Revenue', 'description' => 'Delete revenue'],
            ['module' => 'Administration', 'name' => 'report.read', 'display_name' => 'View Reports', 'description' => 'View reports'],
            ['module' => 'Administration', 'name' => 'report.download', 'display_name' => 'Download Reports', 'description' => 'Download reports'],
        ];

        foreach ($permissions as $permission) {
            $permissionId = DB::table('permissions')->where('name', $permission['name'])->value('id');

            if ($permissionId) {
                DB::table('permissions')->where('id', $permissionId)->update($permission + ['updated_at' => $now]);
            } else {
                $permissionId = DB::table('permissions')->insertGetId($permission + [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }


            DB::table('role_permission')->updateOrInsert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'finance_officer')->value('id');

        if ($roleId) {
            DB::table('role_permission')->where('role_id', $roleId)->delete();
            DB::table('role_user')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
