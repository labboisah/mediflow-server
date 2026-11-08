<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecialistModuleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $base = ['specialist_appointment.read', 'specialist_appointment.manage', 'specialist_patient.manage'];
        $clinical = ['specialist_consultation.read', 'specialist_consultation.update', 'specialist_consultation.complete', 'specialist_consultation.amend',
            'specialist_prescription.issue', 'specialist_investigation.request', 'specialist_result.read', 'specialist_result.review',
            'specialist_referral.create', 'specialist_referral.accept', 'specialist_care_plan.manage', 'specialist_report.read'];
        $finance = ['specialist_billing.read', 'specialist_billing.create', 'specialist_payment.record', 'specialist_report.read', 'specialist_report.revenue'];
        $roles = ['specialist' => array_merge($base, $clinical), 'specialist_assistant' => $base,
            'specialist_manager' => ['specialist_appointment.read', 'specialist.settings.manage', 'specialist_patient.assign'],
            'specialist_receiver' => ['specialist_appointment.read', 'specialist_consultation.read', 'specialist_referral.accept', 'specialist_result.read'],
            'specialist_cashier' => array_merge(['specialist_appointment.read'], $finance)];
        $module = Module::updateOrCreate(['name' => 'specialist_workspace'], ['label' => 'Specialist Care', 'route' => 'specialist.index', 'icon' => 'bi-person-heart',
            'group' => 'clinical', 'sidebar_group' => 'clinical', 'license_module' => 'specialist', 'sidebar_patterns' => ['specialist.*'], 'sort_order' => 45, 'is_active' => true, 'is_sidebar_visible' => true]);
        foreach ($roles as $name => $permissions) {
            $role = Role::firstOrCreate(['name' => $name], ['display_name' => str($name)->replace('_', ' ')->title(), 'description' => 'Scoped Specialist workspace access']);
            foreach ($permissions as $name) {
                $permission = Permission::firstOrCreate(['name' => $name], ['display_name' => str($name)->replace(['_', '.'], ' ')->title(), 'module' => 'Specialist', 'module_id' => $module->id, 'action' => str($name)->afterLast('.')]);
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            }
            $role->modules()->syncWithoutDetaching([$module->id]);
        }
    }
}
