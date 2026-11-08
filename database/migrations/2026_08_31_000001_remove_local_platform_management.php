<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $platformPermissionNames = ['client.manage', 'license.manage', 'agent.manage', 'plan.manage'];
        $superAdminUserEmails = ['superadmin@mediflow.local', 'isahlabbo@mediflow.ng'];
        $superAdminRoleIds = Schema::hasTable('roles')
            ? DB::table('roles')->where('name', 'superadmin')->pluck('id')
            : collect();
        $platformModuleIds = Schema::hasTable('modules')
            ? DB::table('modules')->where('license_module', 'platform')->pluck('id')
            : collect();
        $platformPermissionIds = Schema::hasTable('permissions')
            ? DB::table('permissions')->whereIn('name', $platformPermissionNames)->pluck('id')
            : collect();

        if (Schema::hasTable('role_user') && $superAdminRoleIds->isNotEmpty()) {
            DB::table('role_user')->whereIn('role_id', $superAdminRoleIds)->delete();
        }

        if (Schema::hasTable('role_permission') && ($superAdminRoleIds->isNotEmpty() || $platformPermissionIds->isNotEmpty())) {
            DB::table('role_permission')
                ->when($superAdminRoleIds->isNotEmpty(), fn ($query) => $query->whereIn('role_id', $superAdminRoleIds))
                ->when($platformPermissionIds->isNotEmpty(), fn ($query) => $query->orWhereIn('permission_id', $platformPermissionIds))
                ->delete();
        }

        if (Schema::hasTable('module_role') && ($superAdminRoleIds->isNotEmpty() || $platformModuleIds->isNotEmpty())) {
            DB::table('module_role')
                ->when($superAdminRoleIds->isNotEmpty(), fn ($query) => $query->whereIn('role_id', $superAdminRoleIds))
                ->when($platformModuleIds->isNotEmpty(), fn ($query) => $query->orWhereIn('module_id', $platformModuleIds))
                ->delete();
        }

        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'deleted_at')) {
                DB::table('users')
                    ->whereIn('email', $superAdminUserEmails)
                    ->update([
                        'deleted_at' => now(),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('users')->whereIn('email', $superAdminUserEmails)->delete();
            }
        }

        $this->dropClientReferenceFromLicenses();

        if (Schema::hasTable('client_licenses')) {
            DB::table('client_licenses')
                ->where('license_key', 'MEDIFLOW-LOCAL-HOSPITAL')
                ->delete();
        }

        Schema::dropIfExists('license_plan_modules');
        Schema::dropIfExists('license_plans');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('clients');

        if (Schema::hasTable('modules')) {
            DB::table('modules')->whereIn('id', $platformModuleIds)->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('id', $platformPermissionIds)->delete();
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->whereIn('id', $superAdminRoleIds)->delete();
        }
    }

    public function down(): void
    {
        // Central platform management is intentionally not recreated locally.
    }

    private function dropClientReferenceFromLicenses(): void
    {
        if (! Schema::hasTable('client_licenses') || ! Schema::hasColumn('client_licenses', 'client_id')) {
            return;
        }

        try {
            Schema::table('client_licenses', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
            });
        } catch (\Throwable) {
            // Some installations may have the column without the old foreign key.
        }

        if (Schema::hasColumn('client_licenses', 'client_id')) {
            Schema::table('client_licenses', function (Blueprint $table) {
                $table->dropColumn('client_id');
            });
        }
    }
};
