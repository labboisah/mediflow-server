<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Department;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TechnicianRoleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $generalHead = Role::where('name', 'head_of_department')->first();
        foreach (Department::all() as $department) {
            $head = Role::updateOrCreate(['name' => 'head_of_'.Str::slug($department->name, '_')], [
                'display_name' => 'Head of '.Str::title($department->name),
                'description' => 'Manages staff and operations within '.$department->name,
            ]);
            if ($generalHead) {
                $head->permissions()->syncWithoutDetaching($generalHead->permissions()->pluck('permissions.id')->all());
                $head->modules()->syncWithoutDetaching($generalHead->modules()->pluck('modules.id')->all());
            }
        }
        Module::where('name', 'department_users')->update(['license_module' => 'access_control']);
        $role = Role::firstOrCreate(['name' => 'pharmacy_technician'], [
            'display_name' => 'Pharmacy Technician',
            'description' => 'Supports pharmacy sales and dispensing without pharmacy management access',
        ]);
        foreach (['pharmacy_sale.read', 'pharmacy_sale.create', 'dispense.read', 'dispense.create', 'stock_transaction.read'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], ['display_name' => str($name)->replace('.', ' ')->headline()->toString()]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $role->modules()->syncWithoutDetaching(Module::whereIn('name', ['pharmacy_transactions', 'pharmacy_prescriptions'])->pluck('id')->all());
        $lab = Role::firstOrCreate(['name' => 'lab_technician'], [
            'display_name' => 'Lab Technician',
            'description' => 'Assists in laboratory procedures and sample processing',
        ]);
        foreach (['investigation_request.read', 'investigation_result.read'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], ['display_name' => Str::headline(str_replace('.', ' ', $name))]);
            $lab->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $lab->modules()->syncWithoutDetaching(Module::where('name', 'lab_requests')->pluck('id')->all());
    }
}
