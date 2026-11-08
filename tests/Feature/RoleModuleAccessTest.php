<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\ModuleUserAccess;
use App\Models\Role;
use App\Models\User;
use App\Services\LicenseService;
use App\Services\RoleModuleAccess;
use App\Services\SidebarService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleModuleAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'central_licensing.enabled' => false,
        ]);
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('password');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('action');
            $t->string('model_type')->nullable();
            $t->unsignedBigInteger('model_id')->nullable();
            foreach (['before', 'after', 'meta', 'ip', 'user_agent'] as $column) {
                $t->text($column)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('role_user', function (Blueprint $t) {
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('role_id');
        });
        Schema::create('modules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('label')->nullable();
            $t->string('icon')->nullable();
            $t->string('route');
            $t->string('group')->nullable();
            $t->string('sidebar_group')->nullable();
            $t->json('sidebar_patterns')->nullable();
            $t->string('license_module');
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('is_sidebar_visible')->default(true);
            $t->timestamps();
        });
        Schema::create('module_role', function (Blueprint $t) {
            $t->unsignedBigInteger('module_id');
            $t->unsignedBigInteger('role_id');
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('module_id')->nullable();
        });
        (require database_path('migrations/2026_08_27_000004_create_module_user_access_table.php'))->up();
        $this->partialMock(LicenseService::class, function ($mock) {
            $mock->shouldReceive('moduleEnabled')->andReturnUsing(fn ($module) => $module === 'pharmacy');
            $mock->shouldReceive('routeEnabled')->andReturn(true);
        });
    }

    private function technician(): User
    {
        $role = Role::create(['name' => 'pharmacy_technician']);
        $module = Module::create(['name' => 'pharmacy_transactions', 'route' => 'pharmacy.transactions.index', 'license_module' => 'pharmacy', 'is_active' => true]);
        $module->roles()->attach($role);
        $user = User::create(['name' => 'Technician', 'email' => 'tech@example.test', 'password' => 'password']);
        $user->syncRoles([$role->id]);

        return $user;
    }

    public function test_role_grant_restores_technician_workspace_without_admin_access(): void
    {
        $user = $this->technician();
        $license = app(LicenseService::class);
        $module = Module::with('roles')->firstOrFail()->setRelation('permissions', collect());
        $this->assertFalse(app(SidebarService::class)->canShowItem($user, $module));
        app(RoleModuleAccess::class)->grantMissing($user);
        $this->assertTrue($license->userHasModuleAccess($user, 'pharmacy'));
        $this->assertTrue(app(SidebarService::class)->canShowItem($user, $module));
        $this->assertFalse($user->hasRole('administrator'));
        $this->assertFalse($license->userHasModuleAccess($user, 'finance'));
        app(RoleModuleAccess::class)->grantMissing($user);
        $this->assertSame(1, $user->moduleAccess()->count());
    }

    public function test_a_plan_module_grant_does_not_grant_user_role_access(): void
    {
        $user = $this->technician();

        $this->assertTrue(app(LicenseService::class)->moduleEnabled('pharmacy'));
        $this->assertFalse(app(LicenseService::class)->userHasModuleAccess($user, 'pharmacy'));
        $this->assertSame(0, $user->moduleAccess()->count());
    }

    public function test_technician_does_not_see_manager_only_pharmacy_links(): void
    {
        $user = $this->technician();
        app(RoleModuleAccess::class)->grantMissing($user);
        foreach (['pharmacy_medicines', 'pharmacy_stock', 'pharmacy_stock_reconciliation', 'pharmacy_batches', 'pharmacy_expiry_alerts', 'pharmacy_finance_report'] as $name) {
            $module = new Module(['name' => $name, 'route' => 'pharmacy.finance.report', 'license_module' => 'pharmacy']);
            $this->assertFalse(app(SidebarService::class)->canShowItem($user, $module));
        }
    }

    public function test_sidebar_shows_one_item_per_label_when_user_has_multiple_role_destinations(): void
    {
        $user = User::create(['name' => 'Finance User', 'email' => 'finance@example.test', 'password' => 'password']);
        foreach ([
            ['accountant_bills', 'Bills', 'accountant.bills.index', 'billing', 110],
            ['finance_bills', 'Bills', 'finance.bills.index', 'billing', 111],
            ['accountant_payments', 'Payments', 'accountant.payments.index', 'payments_finance', 130],
            ['finance_payments', 'Payments', 'finance.payments.index', 'payments_finance', 131],
        ] as [$name, $label, $route, $group, $sortOrder]) {
            Module::create([
                'name' => $name,
                'label' => $label,
                'route' => $route,
                'license_module' => 'billing',
                'sidebar_group' => $group,
                'sort_order' => $sortOrder,
            ]);
        }

        $sidebar = $this->partialMock(SidebarService::class, function ($mock): void {
            $mock->shouldReceive('canShowItem')->andReturn(true);
        });

        $groups = collect($sidebar->groupsFor($user))->keyBy('key');

        $this->assertSame(['accountant.bills.index'], $groups['billing']['items']->pluck('route')->all());
        $this->assertSame(['accountant.payments.index'], $groups['payments_finance']['items']->pluck('route')->all());
    }

    public function test_explicit_revocation_is_not_reactivated(): void
    {
        $user = $this->technician();
        ModuleUserAccess::create(['user_id' => $user->id, 'license_module' => 'pharmacy', 'is_active' => false]);
        app(RoleModuleAccess::class)->grantMissing($user);
        $this->assertFalse(app(LicenseService::class)->userHasModuleAccess($user, 'pharmacy'));
    }

    public function test_expired_or_future_grants_are_not_extended(): void
    {
        $user = $this->technician();
        $access = ModuleUserAccess::create(['user_id' => $user->id, 'license_module' => 'pharmacy', 'is_active' => true, 'expires_at' => now()->subDay()]);
        app(RoleModuleAccess::class)->grantMissing($user);
        $this->assertFalse(app(LicenseService::class)->userHasModuleAccess($user, 'pharmacy'));
        $access->update(['expires_at' => null, 'starts_at' => now()->addDay()]);
        app(RoleModuleAccess::class)->grantMissing($user);
        $this->assertFalse(app(LicenseService::class)->userHasModuleAccess($user, 'pharmacy'));
    }

    public function test_unlicensed_and_unrelated_modules_are_not_granted(): void
    {
        $user = $this->technician();
        $module = Module::create(['name' => 'lab', 'route' => 'lab.index', 'license_module' => 'laboratory', 'is_active' => true]);
        $module->roles()->attach($user->roles()->first());
        Module::create(['name' => 'unrelated', 'route' => 'test.index', 'license_module' => 'reports', 'is_active' => true]);
        app(RoleModuleAccess::class)->grantMissing($user);
        $this->assertSame(['pharmacy'], $user->moduleAccess()->pluck('license_module')->all());
    }
}
