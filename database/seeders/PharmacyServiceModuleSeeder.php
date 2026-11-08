<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PharmacyServiceModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::updateOrCreate(['name' => 'pharmacy_services'], [
            'label' => 'Pharmacy Services', 'route' => 'pharmacy.services.index',
            'icon' => 'bi-bandaid', 'group' => 'medications', 'sidebar_group' => 'medications',
            'license_module' => 'pharmacy', 'sidebar_patterns' => ['pharmacy.services.*'],
            'sort_order' => 60, 'is_active' => true, 'is_sidebar_visible' => true,
        ]);
        $module->roles()->syncWithoutDetaching(Role::whereIn('name', ['head_of_department', 'head_of_pharmacy'])->pluck('id')->all());
    }
}
