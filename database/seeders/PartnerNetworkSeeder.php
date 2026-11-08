<?php

namespace Database\Seeders;

use App\Models\CollaborationPartner;
use App\Models\Module;
use App\Models\PartnerType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PartnerNetworkSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (['diagnostic_center' => 'Diagnostic Center', 'hospital' => 'Hospital', 'specialist' => 'Specialist'] as $code => $name) {
            PartnerType::firstOrCreate(['code' => $code], ['name' => $name, 'requirements' => ['Identity verified', 'Professional or facility credentials verified', 'Services and locations verified']]);
        }
        CollaborationPartner::firstOrCreate(['is_local' => true], ['uuid' => (string) Str::uuid(), 'name' => 'Local installation', 'type' => 'organization', 'is_active' => false]);
        $module = Module::updateOrCreate(['name' => 'partner_network_workspace'], ['label' => 'Partner Network', 'route' => 'network.index', 'icon' => 'bi-diagram-3', 'group' => 'clinical', 'sidebar_group' => 'clinical', 'license_module' => 'partner_network', 'sidebar_patterns' => ['network.*'], 'sort_order' => 46, 'is_active' => true, 'is_sidebar_visible' => true]);
        $roles = ['network_coordinator' => ['partner.read', 'partner.register', 'partner.edit', 'partner.invite', 'partner_service.manage'],
            'network_verifier' => ['partner.read', 'partner.verify', 'partner_requirement.manage'],
            'network_approver' => ['partner.read', 'partner.approve'],
            'network_manager' => ['partner.read', 'partnership.manage', 'partner.suspend', 'partner_service.manage', 'partner_report.read'],
            'network_clinician' => ['partner.read', 'partner_collaboration.create', 'partner_collaboration.read', 'partner_collaboration.manage', 'partner_result.receive']];
        foreach ($roles as $name => $permissions) {
            $role = Role::firstOrCreate(['name' => $name], ['display_name' => str($name)->replace('_', ' ')->title(), 'description' => 'Care Network scoped operations']);
            foreach ($permissions as $permission) {
                $p = Permission::firstOrCreate(['name' => $permission], ['display_name' => str($permission)->replace(['_', '.'], ' ')->title(), 'module' => 'Partner Network', 'module_id' => $module->id, 'action' => str($permission)->afterLast('.')]);
                $role->permissions()->syncWithoutDetaching([$p->id]);
            }$role->modules()->syncWithoutDetaching([$module->id]);
        }
    }
}
