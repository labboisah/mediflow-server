<?php

namespace Tests\Feature;

use App\Livewire\Department\DepartmentUsers;
use App\Models\ClientLicense;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentUsersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'audit.queue' => false]);
        Schema::create('departments', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->unique(); $t->string('password');
            $t->unsignedBigInteger('department_id')->nullable(); $t->boolean('is_installation_admin')->default(false);
            $t->timestamp('email_verified_at')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('display_name')->nullable(); $t->timestamps(); });
        Schema::create('role_user', function (Blueprint $t) { $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('role_id'); });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('actor_id')->nullable(); $t->string('action'); $t->string('model_type')->nullable(); $t->unsignedBigInteger('model_id')->nullable();
            foreach (['before', 'after', 'meta', 'ip', 'user_agent'] as $column) $t->text($column)->nullable();
            $t->timestamps();
        });
        (require database_path('migrations/2026_08_27_000002_create_client_licenses_table.php'))->up();
        (require database_path('migrations/2026_08_27_000003_create_client_enabled_modules_table.php'))->up();
        (require database_path('migrations/2026_08_27_000004_create_module_user_access_table.php'))->up();
        Department::create(['name' => 'Pharmacy']);
        Department::create(['name' => 'Laboratory']);
        foreach (['head_of_department', 'head_of_pharmacy', 'head_of_laboratory', 'pharmacist', 'pharmacy_technician', 'lab_technician', 'administrator'] as $name) {
            Role::withoutEvents(fn () => Role::create(['name' => $name, 'display_name' => $name]));
        }
        ClientLicense::create(['client_name' => 'Test', 'plan' => 'pharmacy', 'is_active' => true]);
    }

    private function user(string $role, ?int $department = 1, bool $installationAdmin = false): User
    {
        return User::withoutEvents(function () use ($role, $department, $installationAdmin) {
            $user = new User(['name' => 'Test Staff', 'email' => uniqid().'@example.test', 'password' => 'test-password', 'department_id' => $department]);
            $user->is_installation_admin = $installationAdmin;
            $user->save(); $user->assignRole($role);
            return $user;
        });
    }

    public function test_named_head_can_create_department_technician_and_module_access(): void
    {
        $head = $this->user('head_of_pharmacy');
        Livewire::actingAs($head)->test(DepartmentUsers::class)->call('create')
            ->set('name', 'New Technician')->set('email', 'new@example.test')
            ->set('password', 'safe-password')->set('passwordConfirmation', 'safe-password')
            ->set('selectedRoleIds', [(string) Role::where('name', 'pharmacy_technician')->value('id')])
            ->call('save')->assertHasNoErrors()->assertSee('Department user saved');
        $staff = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertSame(1, $staff->department_id);
        $this->assertTrue($staff->hasRole('pharmacy_technician'));
        $this->assertTrue(app(LicenseService::class)->userHasModuleAccess($staff, 'pharmacy'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'department.user_created']);
        $this->assertFalse($staff->is_installation_admin);
    }

    public function test_other_department_privileged_and_self_accounts_cannot_be_edited(): void
    {
        $head = $this->user('head_of_pharmacy');
        foreach ([$this->user('pharmacy_technician', 2), $this->user('administrator'), $this->user('pharmacy_technician', 1, true), $head] as $target) {
            Livewire::actingAs($head)->test(DepartmentUsers::class)->call('edit', $target->id)->assertNotFound();
        }
    }

    public function test_forged_editing_id_is_rechecked_on_save(): void
    {
        $head = $this->user('head_of_pharmacy');
        $target = $this->user('pharmacy_technician', 2);
        Livewire::actingAs($head)->test(DepartmentUsers::class)->set('editingUserId', $target->id)
            ->set('email', 'stolen@example.test')->call('save')->assertNotFound();
        $this->assertNotSame('stolen@example.test', $target->fresh()->email);
    }

    public function test_head_cannot_assign_privileged_or_other_department_roles(): void
    {
        $head = $this->user('head_of_pharmacy');
        $staff = $this->user('pharmacy_technician');
        foreach (['administrator', 'head_of_pharmacy', 'lab_technician'] as $name) {
            Livewire::actingAs($head)->test(DepartmentUsers::class)->call('edit', $staff->id)
                ->set('selectedRoleIds', [Role::where('name', $name)->value('id')])->call('save')
                ->assertHasErrors('selectedRoleIds.0');
        }
        $this->assertTrue($staff->fresh()->hasRole('pharmacy_technician'));
    }

    public function test_named_head_must_match_department_and_have_a_department(): void
    {
        Livewire::actingAs($this->user('head_of_pharmacy', 2))->test(DepartmentUsers::class)->assertForbidden();
        Livewire::actingAs($this->user('head_of_department', null))->test(DepartmentUsers::class)->assertForbidden();
        Livewire::actingAs($this->user('pharmacy_technician'))->test(DepartmentUsers::class)->assertForbidden();
    }

    public function test_package_change_prevents_assignment_of_disabled_role(): void
    {
        $head = $this->user('head_of_pharmacy');
        $screen = Livewire::actingAs($head)->test(DepartmentUsers::class)->call('create')
            ->set('name', 'New Staff')->set('email', 'new@example.test')->set('password', 'safe-password')->set('passwordConfirmation', 'safe-password')
            ->set('selectedRoleIds', [Role::where('name', 'pharmacy_technician')->value('id')]);
        ClientLicense::first()->update(['plan' => 'general_clinic']);
        $screen->call('save')->assertHasErrors('selectedRoleIds.0');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
    }

    public function test_department_users_is_available_in_standalone_pharmacy_but_inventory_is_not(): void
    {
        $license = app(LicenseService::class);
        $this->assertTrue($license->routeEnabled('department.users.index'));
        $this->assertFalse($license->routeEnabled('department.consumables.index'));
        $this->actingAs($this->user('head_of_pharmacy'))->get('/department/users')->assertOk();
    }

    public function test_head_can_update_staff_role_without_resetting_password(): void
    {
        $head = $this->user('head_of_pharmacy');
        $staff = $this->user('pharmacy_technician');
        $hash = $staff->password;
        Livewire::actingAs($head)->test(DepartmentUsers::class)->call('edit', $staff->id)
            ->set('name', 'Updated Staff')->set('selectedRoleIds', [Role::where('name', 'pharmacist')->value('id')])
            ->call('save')->assertHasNoErrors();
        $staff->refresh();
        $this->assertSame($hash, $staff->password);
        $this->assertSame('Updated Staff', $staff->name);
        $this->assertTrue($staff->hasRole('pharmacist'));
        $this->assertFalse($staff->hasRole('pharmacy_technician'));
    }

    public function test_pharmacy_technician_does_not_gain_pharmacy_management(): void
    {
        $this->actingAs($this->user('pharmacy_technician'))->get(route('pharmacy.medicines.index'))->assertForbidden();
        $middleware = app(\Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware::class)->getPersistentMiddleware();
        $this->assertContains(\App\Http\Middleware\CheckRole::class, $middleware);
        $this->assertContains(\App\Http\Middleware\EnsurePharmacyManager::class, $middleware);
    }

    public function test_legacy_head_role_remains_supported(): void
    {
        $this->assertTrue($this->user('head_of_department')->isDepartmentHead());
    }
}
