<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Models\Role;
use App\Models\Service;
use App\Services\InstallationCatalog;
use App\Services\LicenseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InstallationCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'central_licensing.enabled' => true]);
        $this->mock(LicenseService::class, fn ($m) => $m->shouldReceive('moduleEnabled')->andReturnUsing(fn ($module) => in_array($module, ['core', 'access_control', 'pharmacy', 'billing', 'finance', 'reports'], true)));
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
        });
        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name');
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('module')->nullable();
            $t->unsignedBigInteger('module_id')->nullable();
        });
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('category');
            $t->unsignedBigInteger('department_id')->nullable();
            $t->softDeletes();
        });
        DB::table('roles')->insert([['id' => 1, 'name' => 'pharmacist'], ['id' => 2, 'name' => 'doctor'], ['id' => 3, 'name' => 'administrator']]);
        DB::table('departments')->insert([['id' => 1, 'name' => 'Pharmacy'], ['id' => 2, 'name' => 'Laboratory'], ['id' => 3, 'name' => 'Nursing Services']]);
        DB::table('permissions')->insert([['id' => 1, 'name' => 'pharmacy.read', 'module' => 'Pharmacy'], ['id' => 2, 'name' => 'lab.read', 'module' => 'Laboratory'], ['id' => 3, 'name' => 'user.read', 'module' => 'Access Control']]);
        DB::table('services')->insert([['id' => 1, 'name' => 'Dispensing', 'category' => 'Medication', 'department_id' => 1], ['id' => 2, 'name' => 'Laboratory service', 'category' => 'Laboratory', 'department_id' => 2], ['id' => 3, 'name' => 'Hidden department charge', 'category' => 'General', 'department_id' => 2]]);
    }

    public function test_pharmacy_catalogue_filters_roles_departments_permissions_and_services(): void
    {
        $c = app(InstallationCatalog::class);
        $this->assertSame([1, 3], $c->roles()->pluck('id')->all());
        $this->assertSame([1], $c->departments()->pluck('id')->all());
        $this->assertSame([1, 3], $c->permissions()->pluck('id')->all());
        $this->assertSame([1], $c->services()->pluck('id')->all());
    }

    public function test_unlicensed_role_assignment_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(InstallationCatalog::class)->ids('roles', [2], 'roles');
    }

    public function test_unlicensed_permissions_cannot_be_assigned_to_custom_role(): void
    {
        $this->expectException(ValidationException::class);
        app(InstallationCatalog::class)->roleInput('custom_staff', [2]);
    }

    public function test_service_cannot_be_assigned_to_hidden_department(): void
    {
        $this->expectException(ValidationException::class);
        app(InstallationCatalog::class)->serviceInput(['category' => 'General', 'department_id' => 2]);
    }

    public function test_unlicensed_service_category_is_rejected_without_department(): void
    {
        $this->expectException(ValidationException::class);
        app(InstallationCatalog::class)->serviceInput(['category' => 'Laboratory']);
    }

    public function test_direct_unlicensed_service_edit_is_blocked(): void
    {
        $this->expectException(HttpException::class);
        (new ServiceController)->edit(Service::findOrFail(2));
    }

    public function test_direct_unlicensed_role_edit_is_blocked(): void
    {
        $this->expectException(HttpException::class);
        (new RoleController)->edit(Role::findOrFail(2));
    }

    public function test_non_client_catalogue_remains_unrestricted(): void
    {
        config(['central_licensing.enabled' => false]);
        $this->assertSame(3, app(InstallationCatalog::class)->services()->count());
        $this->assertSame(3, app(InstallationCatalog::class)->roles()->count());
    }

    public function test_every_seeded_permission_has_an_explicit_entitlement_module_mapping(): void
    {
        $source = file_get_contents(database_path('seeders/PermissionSeeder.php'));
        preg_match_all(
            "/\\['module'\\s*=>\\s*'([^']+)'\\s*,\\s*'name'\\s*=>\\s*'([^']+)'/",
            $source,
            $matches,
            PREG_SET_ORDER,
        );

        $this->assertNotEmpty($matches, 'The permission seeder must contain a parseable permission catalog.');
        $mappings = config('entitlement_framework.permission_module_mappings');
        $capabilities = config('entitlement_framework.module_capabilities');

        foreach ($matches as [, $category, $name]) {
            $permissionPrefix = strtolower((string) strtok($name, '.'));
            $mappedModule = $mappings[strtolower($category)][$permissionPrefix] ?? null;

            $this->assertIsString($mappedModule, "Missing entitlement mapping for {$category}:{$name}.");
            $this->assertArrayHasKey($mappedModule, $capabilities, "Unknown entitlement module for {$category}:{$name}.");
        }
    }
}
