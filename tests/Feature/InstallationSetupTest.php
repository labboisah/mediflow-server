<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstallationModules;
use App\Livewire\Admin\InstallationSetup;
use App\Models\ClientLicense;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\InstallationAdministrator;
use App\Services\LicenseService;
use App\Services\SystemBranding;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InstallationSetupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array',
            'central_licensing.enabled' => false,
        ]);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_installation_admin')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            foreach (['before', 'after', 'meta', 'ip', 'user_agent'] as $column) {
                $table->text($column)->nullable();
            }
            $table->timestamps();
        });
        (require database_path('migrations/2026_09_28_000001_create_system_settings_table.php'))->up();
        (require database_path('migrations/2026_09_30_000002_link_installation_administrator.php'))->up();
        (require database_path('migrations/2026_09_30_000003_add_welcome_appearance_to_system_settings.php'))->up();
        DB::table('roles')->insert(['id' => 1, 'name' => 'administrator']);
        (require database_path('migrations/2026_08_27_000002_create_client_licenses_table.php'))->up();
        (require database_path('migrations/2026_08_27_000003_create_client_enabled_modules_table.php'))->up();
        (require database_path('migrations/2026_09_29_000001_add_selected_packages_to_client_licenses.php'))->up();
    }

    private function admin(bool $installation = true): User
    {
        return User::withoutEvents(function () use ($installation) {
            $user = new User(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password']);
            $user->is_installation_admin = $installation;
            $user->save();

            return $user;
        });
    }

    private function license(array $attributes = []): ClientLicense
    {
        return ClientLicense::create(array_merge(['client_name' => 'Test', 'plan' => 'pharmacy', 'is_active' => true], $attributes));
    }

    public function test_ordinary_administrator_cannot_open_setup(): void
    {
        $user = $this->admin(false);
        $user->roles()->attach(1);
        $this->actingAs($user)->get('/admin/installation')->assertForbidden();
        Livewire::actingAs($user)->test(InstallationSetup::class)->assertForbidden();
    }

    public function test_super_admin_can_save_a_package_and_explicit_module_selection(): void
    {
        Livewire::actingAs($this->admin())->test(InstallationSetup::class)
            ->set('clientName', 'Test Pharmacy')->set('plan', 'pharmacy')
            ->set('modules', ['core', 'access_control', 'pharmacy', 'billing'])
            ->call('save')->assertHasNoErrors()->assertSee('Installation saved');
        $service = app(LicenseService::class);
        $this->assertTrue($service->moduleEnabled('pharmacy'));
        $this->assertFalse($service->moduleEnabled('finance'));
        $this->assertFalse($service->moduleEnabled('maternity'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'installation.configured']);
        $this->assertDatabaseHas('client_enabled_modules', ['module_name' => 'finance', 'is_enabled' => false]);
    }

    public function test_dependencies_and_unknown_modules_are_rejected(): void
    {
        Livewire::actingAs($this->admin())->test(InstallationSetup::class)
            ->set('modules', ['core', 'access_control', 'doctor'])->call('save')->assertHasErrors('modules')
            ->set('modules', ['core', 'unknown'])->call('save')->assertHasErrors('modules.1');
        $this->assertDatabaseCount('client_licenses', 0);
    }

    public function test_branch_management_requires_enterprise(): void
    {
        Livewire::actingAs($this->admin())->test(InstallationSetup::class)
            ->set('plan', 'hospital')->set('modules', ['core', 'access_control', 'branch_management'])
            ->call('save')->assertHasErrors('modules');
    }

    public function test_kernelbridge_managed_installation_rejects_plan_and_package_edits(): void
    {
        config(['central_licensing.enabled' => true]);
        $this->expectException(HttpException::class);

        (new InstallationSetup)->updatedPlan();
    }

    public function test_kernelbridge_managed_installation_rejects_package_selection_edits(): void
    {
        config(['central_licensing.enabled' => true]);
        $this->expectException(HttpException::class);

        (new InstallationSetup)->updatedSelectedPackages();
    }

    public function test_expired_inactive_and_future_licenses_never_restore_hospital_access(): void
    {
        $license = $this->license(['plan' => 'hospital', 'expires_at' => now()->subDay()]);
        $service = app(LicenseService::class);
        $this->assertFalse($service->moduleEnabled('pharmacy'));
        $license->update(['expires_at' => null, 'is_active' => false]);
        $this->assertFalse($service->moduleEnabled('pharmacy'));
        $license->update(['is_active' => true, 'starts_at' => now()->addDay()]);
        $this->assertFalse($service->moduleEnabled('pharmacy'));
        $this->assertTrue($service->moduleEnabled('core'));
    }

    public function test_explicit_all_disabled_does_not_restore_plan(): void
    {
        $this->license()->enabledModules()->create(['module_name' => 'pharmacy', 'is_enabled' => false]);
        $this->assertFalse(app(LicenseService::class)->moduleEnabled('pharmacy'));
    }

    public function test_latest_inactive_license_does_not_reactivate_an_older_license(): void
    {
        $this->license(['plan' => 'enterprise_hospital']);
        $this->license(['is_active' => false]);
        $this->assertFalse(app(LicenseService::class)->moduleEnabled('maternity'));
    }

    public function test_direct_get_and_post_are_blocked_even_for_super_admin(): void
    {
        $this->license();
        Route::match(['GET', 'POST'], '/installation-test-lab', fn () => 'allowed')
            ->middleware(EnsureInstallationModules::class)->name('lab.installation-test');
        $this->actingAs($this->admin())->get('/installation-test-lab')->assertForbidden();
        $this->post('/installation-test-lab')->assertForbidden();
    }

    public function test_routes_and_livewire_updates_have_package_middleware(): void
    {
        foreach (['admin.installation', 'lab.requests.index', 'pharmacy.medicines.index', 'patient.prescription.create'] as $name) {
            $this->assertContains(EnsureInstallationModules::class, Route::getRoutes()->getByName($name)->gatherMiddleware());
        }
        $this->assertContains(EnsureInstallationModules::class, app(PersistentMiddleware::class)->getPersistentMiddleware());
    }

    public function test_revoked_super_admin_cannot_save_an_open_screen(): void
    {
        $user = $this->admin();
        $screen = Livewire::actingAs($user)->test(InstallationSetup::class);
        $user->is_installation_admin = false;
        $user->saveQuietly();
        $screen->call('save')->assertForbidden();
        $this->assertDatabaseCount('client_licenses', 0);
    }

    public function test_report_datasets_respect_package(): void
    {
        $license = $this->license();
        $service = app(LicenseService::class);
        $this->assertFalse($service->reportTableEnabled('deliveries'));
        $this->assertFalse($service->reportTableEnabled('vital_signs'));
        $license->update(['plan' => 'maternity_clinic']);
        $this->assertTrue($service->reportTableEnabled('deliveries'));
        $this->assertFalse($service->reportTableEnabled('investigation_requests'));
    }

    public function test_branding_saves_separately_and_escapes_public_text(): void
    {
        $this->license();
        Livewire::actingAs($this->admin())->test(InstallationSetup::class)
            ->set('brandName', 'Test Pharmacy')->set('welcomeHeading', '<script>alert(1)</script>')
            ->set('welcomeStatement', 'Welcome to our team')->set('welcomeTemplate', 'pharmacy')
            ->call('saveBranding')->assertHasNoErrors()->assertSee('System branding saved');
        $this->assertDatabaseHas('system_settings', ['id' => 1, 'brand_name' => 'Test Pharmacy']);
        $this->assertSame('pharmacy', app(LicenseService::class)->currentPlan());
        $this->assertDatabaseHas('audit_logs', ['action' => 'system.branding_updated']);
        auth()->logout();
        $this->get('/login')->assertOk()->assertSee('Login to Test Pharmacy');
        $this->get('/')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_branding_logo_upload_delivery_replacement_and_removal(): void
    {
        Storage::fake('local');
        $screen = Livewire::actingAs($this->admin())->test(InstallationSetup::class)
            ->set('logo', UploadedFile::fake()->image('logo.png', 120, 120))
            ->call('saveBranding')->assertHasNoErrors();
        $old = SystemSetting::find(1)->logo_path;
        Storage::disk('local')->assertExists($old);
        $this->get('/branding/logo')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $screen->set('logo', UploadedFile::fake()->image('new.png', 120, 120))
            ->call('saveBranding')->assertHasNoErrors();
        Storage::disk('local')->assertMissing($old);
        $screen->set('removeLogo', true)->call('saveBranding')->assertHasNoErrors();
        $this->assertNull(SystemSetting::find(1)->logo_path);
        $this->get('/branding/logo')->assertNotFound();
    }

    public function test_invalid_branding_template_and_logo_are_rejected(): void
    {
        Livewire::actingAs($this->admin())->test(InstallationSetup::class)
            ->set('welcomeTemplate', '../../private')->call('saveBranding')->assertHasErrors('welcomeTemplate')
            ->set('logo', UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'))
            ->assertHasErrors('logo');
        $this->assertDatabaseCount('system_settings', 0);
    }

    public function test_automatic_template_tracks_package_and_all_six_render(): void
    {
        $license = $this->license();
        $branding = app(SystemBranding::class);
        foreach (array_keys(config('welcome_templates')) as $key) {
            $license->update(['plan' => $key]);
            $this->assertSame($key, $branding->template()['key']);
            SystemSetting::updateOrCreate(['id' => 1], ['brand_name' => 'Test Brand', 'welcome_template' => 'auto']);
            $this->get('/')->assertOk()->assertSee('data-template="'.$key.'"', false);
        }
        $this->assertSame('pharmacy', $branding->template('pharmacy')['key']);
    }

    public function test_seven_presets_and_shared_routes_are_consistent(): void
    {
        $service = app(LicenseService::class);
        $this->assertCount(7, config('mediflow_modules.plans'));
        foreach (array_keys(config('mediflow_modules.plans')) as $plan) {
            $modules = $service->planModules($plan);
            foreach ($modules as $module) {
                foreach (config("mediflow_modules.dependencies.$module", []) as $dependency) {
                    $this->assertContains($dependency, $modules, "$plan: $module requires $dependency");
                }
            }
        }
        $this->assertNotContains('branch_management', $service->planModules('hospital'));
        $this->assertContains('branch_management', $service->planModules('enterprise_hospital'));
        $this->license(['plan' => 'diagnostic_center']);
        $this->assertTrue($service->routeEnabled('patient.investigation.create'));
        $this->assertFalse($service->routeEnabled('patient.prescription.create'));
        $this->assertFalse($service->routeEnabled('patient.drugchart.record'));
        $this->assertFalse($service->routeEnabled(null, 'api/v1/sync/records'));
    }

    public function test_specialist_companions_preserve_explicit_module_choices(): void
    {
        Livewire::actingAs($this->admin())->test(InstallationSetup::class)->set('plan', 'specialist')
            ->set('selectedPackages', ['specialist', 'pharmacy'])->call('save')->assertHasNoErrors();
        $license = app(LicenseService::class);
        $this->assertSame(['specialist', 'pharmacy'], $license->selectedPackages());
        $this->assertTrue($license->moduleEnabled('specialist'));
        $this->assertTrue($license->moduleEnabled('pharmacy'));
        $this->assertFalse($license->routeEnabled('patient.admission.create'));
        $this->assertTrue($license->routeEnabled('specialist.index'));
    }

    public function test_enterprise_companion_keeps_primary_branding(): void
    {
        Livewire::actingAs($this->admin())->test(InstallationSetup::class)->set('plan', 'specialist')
            ->set('selectedPackages', ['specialist', 'enterprise_hospital'])->call('save')->assertHasNoErrors();
        $this->assertSame('specialist', app(LicenseService::class)->currentPlan());
        $this->assertTrue(app(LicenseService::class)->moduleEnabled('branch_management'));
    }

    public function test_explicit_enterprise_does_not_silently_enable_specialist(): void
    {
        $license = $this->license(['plan' => 'enterprise_hospital']);
        $license->enabledModules()->create(['module_name' => 'core', 'is_enabled' => true]);
        $this->assertFalse(app(LicenseService::class)->moduleEnabled('specialist'));
    }

    public function test_explicit_registration_and_password_preservation(): void
    {
        $screen = Livewire::actingAs($this->admin())->test(InstallationSetup::class)
            ->set('brandName', 'Care Network')->call('saveBranding')->assertHasNoErrors();
        $this->assertDatabaseCount('users', 1);
        $screen->set('adminName', 'Practice Administrator')->set('adminEmail', 'owner@example.test')
            ->set('adminPassword', 'chosen-password')->set('adminPassword_confirmation', 'chosen-password')
            ->call('saveAdministrator')->assertHasNoErrors()->assertSee('Administrator registered.')
            ->assertSet('adminPassword', '')->assertSet('adminPassword_confirmation', '');
        $admin = User::where('email', 'owner@example.test')->firstOrFail();
        $this->assertTrue($admin->hasRole('administrator'));
        $this->assertFalse($admin->is_installation_admin);
        $this->assertTrue(Hash::check('chosen-password', $admin->password));
        $screen->set('brandName', 'Another Brand')->call('saveBranding')->assertHasNoErrors();
        $this->assertSame('owner@example.test', $admin->fresh()->email);
        $screen->set('adminEmail', 'updated@example.test')->call('saveAdministrator')->assertHasNoErrors();
        $this->assertTrue(Hash::check('chosen-password', $admin->fresh()->password));
        $this->assertSame('updated@example.test', $admin->fresh()->email);
        $screen->set('adminPassword', 'replacement-password')->set('adminPassword_confirmation', 'replacement-password')->call('saveAdministrator')->assertHasNoErrors()->assertSet('adminPassword', '');
        $this->assertTrue(Hash::check('replacement-password', $admin->fresh()->password));
        $this->assertDatabaseCount('users', 2);
    }

    public function test_registration_validates_password_confirmation_and_email_collision(): void
    {
        $owner = $this->admin();
        SystemSetting::create(['id' => 1, 'brand_name' => 'Care Network']);
        $screen = Livewire::actingAs($owner)->test(InstallationSetup::class)
            ->set('adminName', 'Administrator')->set('adminEmail', $owner->email)
            ->set('adminPassword', 'chosen-password')->set('adminPassword_confirmation', 'different-password')
            ->call('saveAdministrator')->assertHasErrors(['adminEmail', 'adminPassword'])->assertSet('adminPassword', '');
        $this->assertDatabaseCount('users', 1);
        $screen->set('adminEmail', 'new@example.test')->set('adminPassword', 'admin')->set('adminPassword_confirmation', 'admin')
            ->call('saveAdministrator')->assertHasErrors('adminPassword');
    }

    public function test_branding_no_longer_requires_domain_characters_or_creates_users(): void
    {
        Livewire::actingAs($this->admin())->test(InstallationSetup::class)->set('brandName', '!!!')->call('saveBranding')->assertHasNoErrors();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_requires_saved_branding(): void
    {
        Livewire::actingAs($this->admin())->test(InstallationSetup::class)
            ->set('adminName', 'Administrator')->set('adminEmail', 'new@example.test')
            ->set('adminPassword', 'chosen-password')->set('adminPassword_confirmation', 'chosen-password')
            ->call('saveAdministrator')->assertHasErrors('adminEmail');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_non_super_admin_cannot_register_an_administrator_through_service(): void
    {
        $this->actingAs($this->admin(false));
        $this->expectException(HttpException::class);
        app(InstallationAdministrator::class)->save(['adminName' => 'Unauthorized', 'adminEmail' => 'new@example.test', 'adminPassword' => 'chosen-password', 'adminPassword_confirmation' => 'chosen-password']);
    }

    public function test_welcome_artwork_and_colors_can_be_saved_replaced_and_removed(): void
    {
        Storage::fake('local');
        $component = Livewire::actingAs($this->admin())->test(InstallationSetup::class)
            ->set('brandName', 'Artwork Clinic')
            ->set('welcomeColors.accent', '#123456')->set('welcomeColors.background', '#fafafa')
            ->set('artwork.hero', UploadedFile::fake()->image('hero.png', 100, 100))
            ->set('artwork.background', UploadedFile::fake()->image('background.jpg', 100, 100))
            ->set('artwork.supporting', UploadedFile::fake()->image('support.png', 100, 100))
            ->set('artworkAlt.hero', 'Our care team')->call('saveBranding')->assertHasNoErrors();
        $appearance = SystemSetting::find(1)->welcome_appearance;
        $old = $appearance['images']['hero']['path'];
        Storage::disk('local')->assertExists($old);
        $this->assertSame('#123456', $appearance['colors']['accent']);
        $this->get('/')->assertOk()->assertSee('Our care team')->assertSee('--brand-accent: #123456', false);
        $this->get(app(SystemBranding::class)->imageUrl('hero'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/branding/image/unknown')->assertNotFound();
        $component->set('artwork.hero', UploadedFile::fake()->image('replacement.jpg'))
            ->call('saveBranding')->assertHasNoErrors();
        Storage::disk('local')->assertMissing($old);
        $replacement = SystemSetting::find(1)->welcome_appearance['images']['hero']['path'];
        $component->set('removeArtwork.hero', true)->set('welcomeColors.accent', '')
            ->call('saveBranding')->assertHasNoErrors();
        Storage::disk('local')->assertMissing($replacement);
        $this->get('/branding/image/hero')->assertNotFound();
        $this->assertNull(app(SystemBranding::class)->imageUrl('hero'));
        $this->assertSame('', SystemSetting::find(1)->welcome_appearance['colors']['accent']);
    }

    public function test_invalid_appearance_input_is_rejected_and_preview_colors_are_safe(): void
    {
        Storage::fake('local');
        Livewire::actingAs($this->admin())->test(InstallationSetup::class)
            ->set('welcomeColors.accent', 'red;display:none')->call('saveBranding')
            ->assertHasErrors(['welcomeColors.accent'])
            ->set('welcomeColors.accent', '#123456')
            ->set('artwork.hero', UploadedFile::fake()->create('script.svg', 10, 'image/svg+xml'))
            ->call('saveBranding')->assertHasErrors(['artwork.hero']);
        $template = app(SystemBranding::class)->template('pharmacy');
        $this->assertSame($template['accent'], app(SystemBranding::class)->colors($template, ['accent' => 'red;display:none'])['accent']);
        $this->assertNull(SystemSetting::find(1));
    }
}
