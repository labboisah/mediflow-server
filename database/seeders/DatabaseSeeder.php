<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $this->call(RoleSeeder::class);

        // Create permissions and assign to roles
        $this->call(PermissionSeeder::class);

        // Create modules and attach them to roles
        $this->call(ModulePermissionSeeder::class);
        $this->call(TechnicianRoleSeeder::class);
        $this->call(PharmacyServiceModuleSeeder::class);
        $this->call(SpecialistModuleSeeder::class);
        $this->call(PartnerNetworkSeeder::class);

        // Create Medical Director role and scoped oversight access
        $this->call(MedicalDirectorSeeder::class);

        // Create admin user
        //$this->call(AdminUserSeeder::class);

        // Preserve current user access while module access management is introduced
        $this->call(ModuleUserAccessSeeder::class);

        // Create accountant role and permissions
        $this->call(AccountantSeeder::class);

        // Create services
        $this->call(ServiceSeeder::class);
        
        $this->call(InvestigationSeeder::class);
        
        $this->call(RouteSeeder::class);
        
        $this->call(ConsumableSeeder::class);
    }
}
