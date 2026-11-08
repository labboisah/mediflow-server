<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\PatientDemographic;
use App\Models\PatientVisit;
use App\Models\PaymentMethod;
use App\Models\PharmacyService;
use App\Models\Role;
use App\Models\Service;
use App\Models\SpecialistConsultation;
use App\Models\SpecialistDocument;
use App\Models\SpecialistProfile;
use App\Models\SpecialistService;
use App\Models\Specialty;
use App\Models\User;
use App\Services\SidebarService;
use App\Services\SpecialistAccess;
use App\Services\SpecialistSyncBoundary;
use App\Services\SpecialistWorkflow;
use Database\Seeders\SpecialistModuleSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SpecialistWorkflowTest extends TestCase
{
    private User $specialistUser;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'sync.behavior.auto_sync_enabled' => false]);
        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('password');
            $t->unsignedBigInteger('department_id')->nullable();
            $t->boolean('is_installation_admin')->default(false);
            $t->timestamp('email_verified_at')->nullable();
            $t->softDeletes();
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
        Schema::create('medicines', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('medicine_batches', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('medicine_id');
            $t->string('batch_number');
            $t->date('expiry_date');
            $t->integer('quantity_remaining');
            $t->decimal('selling_price', 12, 2);
            $t->timestamps();
        });
        Schema::create('payment_methods', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->boolean('is_active');
            $t->timestamps();
        });
        Schema::create('stock_transactions', function (Blueprint $t) {
            $t->id();
            $t->decimal('total_amount', 15, 2);
            $t->string('type');
            $t->unsignedBigInteger('created_by');
            $t->string('reference')->nullable();
            $t->unsignedBigInteger('bill_id')->nullable();
            $t->unsignedBigInteger('payment_id')->nullable();
            $t->timestamps();
        });
        Schema::create('stock_transaction_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('transaction_id');
            $t->unsignedBigInteger('medicine_batch_id');
            $t->integer('quantity');
            $t->decimal('price', 12, 2);
            $t->decimal('subtotal', 15, 2);
            $t->timestamps();
        });
        Schema::create('pharmacy_dispenses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('medicine_batch_id');
            $t->string('type');
            $t->integer('quantity');
            $t->string('reference');
            $t->unsignedBigInteger('created_by');
            $t->timestamps();
        });
        Schema::create('bills', function (Blueprint $t) {
            $t->id();
            foreach (['walkin_id', 'department_id', 'issued_by'] as $c) {
                $t->unsignedBigInteger($c)->nullable();
            }
            $t->string('bill_number')->unique();
            $t->text('service_description');
            $t->decimal('amount', 12, 2);
            $t->decimal('due_amount', 12, 2);
            $t->string('status');
            $t->dateTime('issued_date');
            $t->dateTime('due_date');
            $t->text('notes')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->string('payment_id')->unique();
            $t->decimal('amount', 12, 2);
            foreach (['bill_id', 'paid_by', 'payment_method_id'] as $c) {
                $t->unsignedBigInteger($c);
            }
            $t->string('reference_number')->nullable();
            $t->string('status');
            $t->text('notes');
            $t->dateTime('payment_date');
            $t->softDeletes();
            $t->timestamps();
        });
        (require database_path('migrations/2026_03_09_184420_create_walkin_patients_table.php'))->up();
        (require database_path('migrations/2026_09_28_000001_add_pharmacy_services.php'))->up();
        Department::create(['name' => 'Pharmacy']);
        Department::create(['name' => 'Laboratory']);
        foreach (['pharmacist', 'pharmacy_technician', 'head_of_pharmacy', 'head_of_department', 'administrator'] as $name) {
            Role::withoutEvents(fn () => Role::create(['name' => $name]));
        }
        PaymentMethod::create(['name' => 'Cash', 'is_active' => true]);
        Medicine::create(['name' => 'Test medicine']);
        MedicineBatch::create(['medicine_id' => 1, 'batch_number' => 'B1', 'expiry_date' => today()->addYear(), 'quantity_remaining' => 10, 'selling_price' => 120]);
        PharmacyService::create(['name' => 'Wound dressing', 'price' => 500, 'is_active' => true]);
        $this->specialistSchema();
        config(['mediflow_modules.default_plan' => 'specialist']);
        $this->seed(SpecialistModuleSeeder::class);
        $this->actingAs($this->user('specialist'));
        $this->specialistUser = auth()->user();
        Specialty::create(['name' => 'General specialist', 'is_active' => true]);
        $profile = SpecialistProfile::create(['user_id' => auth()->id(), 'title' => 'Specialist', 'timezone' => 'UTC', 'location' => 'Room 1',
            'physical' => true, 'online' => true, 'is_active' => true, 'availability' => ['days' => [1, 2, 3, 4, 5, 6, 7], 'from' => '00:00', 'to' => '23:59']]);
        $profile->specialties()->attach(1);
        Service::create(['name' => 'Consultation', 'code' => 'SP1', 'price' => 500, 'category' => 'Specialist', 'department_id' => 1, 'is_active' => true]);
        SpecialistService::create(['service_id' => 1, 'specialty_id' => 1, 'duration_minutes' => 30]);
        $patient = Patient::create(['file_type_id' => 1, 'hospital_number' => 'PAT1', 'registration_date' => now()]);
        PatientDemographic::create(['patient_id' => $patient->id, 'first_name' => 'Test', 'last_name' => 'Patient', 'phone_number' => '080000000']);
        app(SpecialistAccess::class)->grant(1, auth()->id());
        $this->mock(SidebarService::class, function ($mock) {
            $mock->shouldReceive('groupsFor')->andReturn([]);
            $mock->shouldReceive('canShowActivities')->andReturn(false);
        });
    }

    private function user(string $role, int $department = 1): User
    {
        return User::withoutEvents(function () use ($role, $department) {
            $user = User::create(['name' => 'Staff', 'email' => uniqid().'@example.test', 'password' => 'password', 'department_id' => $department]);
            $user->assignRole($role);

            return $user;
        });
    }

    private function specialistSchema(): void
    {
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('display_name')->nullable();
            $t->string('description')->nullable();
            $t->string('module')->nullable();
            $t->unsignedBigInteger('module_id')->nullable();
            $t->string('action')->nullable();
            $t->timestamps();
        });
        Schema::create('role_permission', function (Blueprint $t) {
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
        });
        Schema::create('temporary_permissions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('permission_id');
            $t->timestamps();
        });
        Schema::table('roles', function (Blueprint $t) {
            $t->string('display_name')->nullable();
            $t->string('description')->nullable();
        });
        Schema::create('modules', function (Blueprint $t) {
            $t->id();
            foreach (['name', 'label', 'route', 'icon', 'group', 'license_module', 'sidebar_group'] as $c) {
                $t->string($c)->nullable();
            }$t->text('sidebar_patterns')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('is_sidebar_visible')->default(true);
            $t->timestamps();
        });
        Schema::create('module_role', function (Blueprint $t) {
            $t->unsignedBigInteger('module_id');
            $t->unsignedBigInteger('role_id');
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('action');
            $t->string('model_type')->nullable();
            $t->unsignedBigInteger('model_id')->nullable();
            foreach (['before', 'after', 'meta', 'ip', 'user_agent'] as $c) {
                $t->text($c)->nullable();
            }$t->timestamps();
        });
        Schema::create('file_types', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->decimal('price', 12, 2)->default(0);
            $t->softDeletes();
            $t->timestamps();
        });
        DB::table('file_types')->insert(['id' => 1, 'name' => 'Standard']);
        Schema::create('routes', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        DB::table('routes')->insert(['id' => 1, 'name' => 'Oral']);
        Schema::create('medicine_types', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('investigation_types', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('department_id');
            $t->timestamps();
        });
        Schema::create('investigations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->decimal('price', 12, 2);
            $t->unsignedBigInteger('investigation_type_id');
            $t->timestamps();
        });
        Schema::create('investigation_results', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('investigation_request_id');
            $t->unsignedBigInteger('parameter_id')->nullable();
            $t->string('value')->nullable();
            $t->timestamps();
        });
        Schema::create('parameters', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code');
            $t->string('category');
            $t->decimal('price', 12, 2);
            $t->unsignedBigInteger('department_id');
            $t->boolean('is_active');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('service_requests', function (Blueprint $t) {
            $t->id();
            foreach (['patient_visit_id', 'service_id', 'bill_id', 'requested_by'] as $c) {
                $t->unsignedBigInteger($c);
            }$t->string('status');
            $t->string('payment_status');
            $t->text('clinical_diagnoses')->nullable();
            $t->dateTime('requested_at');
            $t->softDeletes();
            $t->timestamps();
        });
        foreach (['bill_services' => 'service_id', 'bill_investigations' => 'investigation_id'] as $name => $fk) {
            Schema::create($name, function (Blueprint $t) use ($fk) {
                $t->id();
                $t->unsignedBigInteger('bill_id');
                $t->unsignedBigInteger($fk);
                $t->integer('quantity');
                $t->decimal('unit_price', 12, 2);
                $t->decimal('subtotal', 12, 2);
                $t->timestamps();
            });
        }
        Schema::table('bills', fn (Blueprint $t) => $t->unsignedBigInteger('patient_visit_id')->nullable());
        Schema::table('payments', function (Blueprint $t) {
            $t->unsignedBigInteger('patient_id')->nullable();
            $t->text('notes')->nullable()->change();
        });
        foreach (['2026_02_11_000001_create_patients_table', '2026_02_11_000002_create_patient_demographics_table', '2026_02_11_000004_create_patient_visits_table',
            '2026_02_11_000005_create_appointments_table', '2026_02_11_000007_create_patient_referrals_table', '2026_02_17_152628_create_prescriptions_table',
            '2026_02_18_164445_create_prescription_items_table', '2026_02_13_092950_create_investigation_requests_table'] as $migration) {
            (require database_path('migrations/'.$migration.'.php'))->up();
        }
        Schema::table('prescriptions', fn (Blueprint $t) => $t->text('treatment_diagnosis')->nullable());
        (require database_path('migrations/2026_09_29_000002_create_specialist_workspace.php'))->up();
    }

    private function booking(array $changes = []): array
    {
        return array_replace(['patient_id' => 1, 'profile_id' => 1, 'service_id' => 1, 'starts_at' => now('UTC')->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'),
            'mode' => 'physical', 'reason' => 'Review', 'token' => (string) Str::uuid()], $changes);
    }

    private function start(): SpecialistConsultation
    {
        $c = app(SpecialistWorkflow::class)->book($this->booking());
        app(SpecialistWorkflow::class)->start($c->id);

        return $c->fresh();
    }

    public function test_booking_retry_and_overlap_are_safe(): void
    {
        $data = $this->booking();
        $w = app(SpecialistWorkflow::class);
        $a = $w->book($data);
        $b = $w->book($data);
        $this->assertSame($a->id, $b->id);
        $this->assertDatabaseCount('appointments', 1);
        try {
            $w->book($this->booking());
            $this->fail('Overlap accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('workflow', $e->errors());
        }
        $this->assertDatabaseCount('specialist_consultations', 1);
    }

    public function test_start_is_idempotent_and_completion_protects_signed_records(): void
    {
        $c = $this->start();
        $w = app(SpecialistWorkflow::class);
        $w->start($c->id);
        $this->assertDatabaseCount('patient_visits', 1);
        $w->saveClinical($c->id, ['version' => 1, 'complaint' => 'Pain', 'diagnosis' => 'Assessment', 'plan' => 'Follow up', 'complete' => 1]);
        $this->assertSame('completed', $c->fresh()->status);
        try {
            $w->saveClinical($c->id, ['version' => 2, 'complaint' => 'Overwrite', 'diagnosis' => 'x', 'plan' => 'y']);
            $this->fail('Signed record overwritten');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
        $w->amend($c->id, ['reason' => 'Clarification', 'content' => 'Additional detail']);
        $this->assertDatabaseCount('specialist_amendments', 1);
    }

    public function test_other_specialists_cannot_read_or_mutate_unassigned_records(): void
    {
        $c = $this->start();
        $this->actingAs($this->user('specialist'));
        $this->get(route('specialist.consultations.show', $c))->assertNotFound();
        $this->post(route('specialist.consultations.action', [$c, 'start']))->assertNotFound();
    }

    public function test_assistant_cannot_prescribe_or_view_clinical_notes(): void
    {
        $c = $this->start();
        $assistant = $this->user('specialist_assistant');
        app(SpecialistAccess::class)->grant(1, $assistant->id);
        $this->actingAs($assistant);
        $this->get(route('specialist.consultations.show', $c))->assertForbidden();
        $this->post(route('specialist.consultations.action', [$c, 'start']))->assertForbidden();
        $this->get(route('patient.show', 1))->assertForbidden();
    }

    public function test_standalone_prescription_uses_existing_record_and_external_status(): void
    {
        $c = $this->start();
        $d = ['token' => (string) Str::uuid(), 'fulfillment' => 'external', 'diagnosis' => 'Test indication',
            'items' => [['medicine_id' => 1, 'route_id' => 1, 'dosage' => '1', 'period' => 'Daily', 'duration' => '2 days']]];
        $w = app(SpecialistWorkflow::class);
        $p = $w->prescribe($c->id, $d);
        $this->assertSame($p->id, $w->prescribe($c->id, $d)->id);
        $this->assertSame('issued_external', $p->status);
        $this->assertDatabaseCount('prescriptions', 1);
        $this->assertDatabaseCount('stock_transaction_items', 0);
        $this->get(route('specialist.print', [$c, 'prescription', $p->id]))->assertOk()->assertSee('Test medicine');
    }

    public function test_optional_pharmacy_cannot_be_bypassed(): void
    {
        $c = $this->start();
        $this->expectException(ValidationException::class);
        app(SpecialistWorkflow::class)->prescribe($c->id, ['token' => (string) Str::uuid(), 'fulfillment' => 'internal', 'diagnosis' => 'Test',
            'items' => [['medicine_id' => 1, 'route_id' => 1, 'dosage' => '1', 'period' => 'Daily', 'duration' => '2 days']]]);
    }

    public function test_external_investigation_does_not_bill_local_diagnostics(): void
    {
        $c = $this->start();
        $d = ['token' => (string) Str::uuid(), 'destination' => 'external', 'name' => 'External scan', 'clinical_diagnoses' => 'Review', 'external_destination' => 'Partner'];
        $w = app(SpecialistWorkflow::class);
        $r = $w->investigate($c->id, $d);
        $this->assertSame($r->id, $w->investigate($c->id, $d)->id);
        $this->assertNull($r->investigation_id);
        $this->assertNull($r->bill_id);
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_billing_and_partial_payment_are_idempotent_and_scoped(): void
    {
        $c = $this->start();
        $cashier = $this->user('specialist_cashier');
        app(SpecialistAccess::class)->grant(1, $cashier->id);
        $this->actingAs($cashier);
        $w = app(SpecialistWorkflow::class);
        $bill = $w->bill($c->id);
        $this->assertSame($bill->id, $w->bill($c->id)->id);
        $d = ['token' => (string) Str::uuid(), 'amount' => '200.00', 'payment_method_id' => 1];
        $p = $w->pay($c->id, $d);
        $this->assertSame($p->id, $w->pay($c->id, $d)->id);
        $this->assertSame('partial', $bill->fresh()->status);
        $this->assertSame(300.0, (float) $bill->fresh()->balance);
        $this->get(route('specialist.billing', $c))->assertOk()->assertSee('300.00');
        $this->get(route('specialist.consultations.show', $c))->assertForbidden();
        $w->pay($c->id, ['token' => (string) Str::uuid(), 'amount' => '300.00', 'payment_method_id' => 1]);
        $this->assertSame('paid', $bill->fresh()->status);
        $this->assertDatabaseCount('bills', 1);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_care_plan_versions_prevent_lost_updates(): void
    {
        $c = $this->start();
        $w = app(SpecialistWorkflow::class);
        $d = ['version' => 0, 'problem' => 'Problem', 'objectives' => 'Goal', 'instructions' => 'Actions', 'review_date' => today()->addWeek()->toDateString(), 'status' => 'active'];
        $w->carePlan($c->id, $d);
        $this->expectException(ValidationException::class);
        $w->carePlan($c->id, $d);
    }

    public function test_workspace_and_configuration_render_with_permissions(): void
    {
        $c = $this->start();
        $this->get(route('specialist.index'))->assertOk()->assertSee('Test Patient');
        $this->get(route('specialist.consultations.show', $c))->assertOk()->assertSee('Clinical record');
        $this->get(route('specialist.setup'))->assertForbidden();
        $this->actingAs($this->user('specialist_manager'));
        $this->get(route('specialist.setup'))->assertOk()->assertSee('Services and charges');
    }

    public function test_rescheduling_preserves_identity(): void
    {
        $w = app(SpecialistWorkflow::class);
        $c = $w->book($this->booking());
        $appointmentId = $c->appointment_id;
        $w->reschedule($c->id, ['starts_at' => now('UTC')->addDays(2)->setTime(12, 0)->format('Y-m-d\TH:i'), 'reason' => 'Patient request']);
        $this->assertSame($appointmentId, $c->fresh()->appointment_id);
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'specialist.appointment_rescheduled']);
    }

    public function test_internal_pharmacy_preserves_prescription_identity(): void
    {
        config(['mediflow_modules.plans.specialist.modules' => array_merge(config('mediflow_modules.plans.specialist.modules'), ['pharmacy'])]);
        $c = $this->start();
        $p = app(SpecialistWorkflow::class)->prescribe($c->id, ['token' => (string) Str::uuid(), 'fulfillment' => 'internal', 'diagnosis' => 'Test',
            'items' => [['medicine_id' => 1, 'route_id' => 1, 'dosage' => '1', 'period' => 'Daily', 'duration' => '2 days']]]);
        $this->assertSame('submitted', $p->status);
        $this->assertSame($c->patient_visit_id, $p->patient_visit_id);
        $this->assertDatabaseCount('prescriptions', 1);
        $this->assertDatabaseCount('pharmacy_dispenses', 0);
    }

    public function test_internal_diagnostic_result_is_reviewed_without_duplicate_bill(): void
    {
        config(['mediflow_modules.plans.specialist.modules' => array_merge(config('mediflow_modules.plans.specialist.modules'), ['laboratory'])]);
        DB::table('investigation_types')->insert(['id' => 1, 'name' => 'Lab', 'department_id' => 2]);
        DB::table('investigations')->insert(['id' => 1, 'name' => 'Test investigation', 'price' => 300, 'investigation_type_id' => 1]);
        $c = $this->start();
        $w = app(SpecialistWorkflow::class);
        $d = ['token' => (string) Str::uuid(), 'destination' => 'laboratory', 'investigation_id' => 1, 'name' => 'Test', 'clinical_diagnoses' => 'Review'];
        $order = $w->investigate($c->id, $d);
        $this->assertSame($order->id, $w->investigate($c->id, $d)->id);
        $this->assertDatabaseCount('bills', 1);
        $this->assertDatabaseCount('bill_investigations', 1);
        $order->update(['status' => 'Completed', 'completed_at' => now()]);
        $w->reviewResult($c->id, $order->id, 'Reviewed result');
        $this->assertNotNull($order->fresh()->reviewed_at);
    }

    public function test_external_referral_outcome_is_recorded_without_admission(): void
    {
        $c = $this->start();
        $w = app(SpecialistWorkflow::class);
        $r = $w->refer($c->id, ['token' => (string) Str::uuid(), 'destination_type' => 'external',
            'destination' => 'External Hospital', 'reason' => 'Review', 'summary' => 'Selected summary', 'urgency' => 'routine']);
        $w->externalOutcome($c->id, $r->id, ['status' => 'Accepted', 'outcome' => 'Facility acknowledged']);
        $w->externalOutcome($c->id, $r->id, ['status' => 'Completed', 'outcome' => 'Outcome received']);
        $this->assertSame('Completed', $r->fresh()->status);
        $this->assertNull($r->outcome_admission_id);
    }

    public function test_recipient_receives_scope_only_after_acceptance(): void
    {
        $c = $this->start();
        $receiver = $this->user('specialist');
        $profile = SpecialistProfile::first()->replicate();
        $profile->user_id = $receiver->id;
        $profile->save();
        $r = app(SpecialistWorkflow::class)->refer($c->id, ['token' => (string) Str::uuid(), 'destination_type' => 'specialist', 'destination' => 'Colleague',
            'destination_user_id' => $receiver->id, 'reason' => 'Second opinion', 'summary' => 'Relevant summary', 'urgency' => 'routine']);
        $this->actingAs($receiver);
        $this->assertSame(0, app(SpecialistAccess::class)->patients($receiver)->count());
        app(SpecialistWorkflow::class)->respondReferral($r->id, ['status' => 'Accepted', 'outcome' => 'Accepted for review']);
        $this->assertSame(1, app(SpecialistAccess::class)->patients($receiver)->count());
    }

    public function test_disable_blocks_writes_and_retains_scoped_archive(): void
    {
        $c = $this->start();
        config(['mediflow_modules.plans.specialist.modules' => ['core', 'access_control', 'patient_records']]);
        $this->post(route('specialist.consultations.action', [$c, 'start']))->assertForbidden();
        $this->get(route('clinical-history.specialist', 1))->assertOk()->assertSee('Read-only archive');
        $this->assertDatabaseCount('specialist_consultations', 1);
    }

    public function test_reports_and_billing_exclude_unassigned_patient(): void
    {
        $c = $this->start();
        $this->actingAs($this->user('specialist_cashier'));
        $this->get(route('specialist.billing', $c))->assertNotFound();
        $this->get(route('specialist.reports'))->assertOk()->assertDontSee('Test Patient');
    }

    public function test_private_report_upload_download_and_review(): void
    {
        Storage::fake('local');
        $c = $this->start();
        $order = app(SpecialistWorkflow::class)->investigate($c->id, ['token' => (string) Str::uuid(), 'destination' => 'external', 'name' => 'Scan', 'clinical_diagnoses' => 'Test', 'external_destination' => 'Clinic']);
        $this->post(route('specialist.documents.store', $c), ['request_id' => $order->id, 'source' => 'External Clinic', 'report_date' => today()->toDateString(),
            'document' => UploadedFile::fake()->create('report.pdf', 20, 'application/pdf')])->assertRedirect()->assertSessionHasNoErrors();
        $doc = SpecialistDocument::firstOrFail();
        $this->get(route('specialist.documents.show', $doc))->assertOk();
        app(SpecialistWorkflow::class)->reviewResult($c->id, $order->id, 'Reviewed external report');
        $this->assertNotNull($order->fresh()->reviewed_at);
        $this->actingAs($this->user('specialist'));
        $this->get(route('specialist.documents.show', $doc))->assertNotFound();
    }

    public function test_specialist_records_are_not_exported_without_complete_exchange_adapter(): void
    {
        $c = $this->start();
        $this->assertTrue(app(SpecialistSyncBoundary::class)->localOnly($c->visit));
        $this->expectException(\LogicException::class);
        $c->visit->createSyncOperation();
    }

    public function test_existing_active_visit_can_be_shared_without_duplicate_encounter(): void
    {
        $visit = PatientVisit::create(['patient_id' => 1, 'visit_date' => now(), 'visit_type' => 'Clinic', 'status' => 'Active', 'created_by' => auth()->id()]);
        $c = app(SpecialistWorkflow::class)->book($this->booking(['patient_visit_id' => $visit->id]));
        app(SpecialistWorkflow::class)->start($c->id);
        $this->assertSame($visit->id, $c->fresh()->patient_visit_id);
        $this->assertDatabaseCount('patient_visits', 1);
    }

    public function test_signed_author_and_booked_charge_survive_catalogue_edits(): void
    {
        $c = $this->start();
        $w = app(SpecialistWorkflow::class);
        $name = auth()->user()->name;
        $specialty = $c->specialty_name;
        $w->saveClinical($c->id, ['version' => 1, 'complaint' => 'Pain', 'diagnosis' => 'Snapshot diagnosis', 'plan' => 'Review', 'complete' => 1]);
        $w->amend($c->id, ['reason' => 'Clarification', 'content' => 'Signed addition']);
        auth()->user()->update(['name' => 'Renamed clinician']);
        $c->specialty->update(['name' => 'Renamed specialty']);
        Service::find($c->service_id)->update(['name' => 'Renamed service', 'price' => 9999]);
        $c->refresh();
        $this->assertSame($name, $c->specialist_name);
        $this->assertSame($name, $c->completed_name);
        $this->assertSame($name, $c->amendments->first()->author_name);
        $this->assertSame($specialty, $c->specialty_name);
        $this->assertSame((int) auth()->id(), (int) $c->completed_by);
        $this->assertNotEquals('9999.00', $c->charge);
        $this->get(route('specialist.reports'))->assertOk()->assertSee('Snapshot diagnosis')->assertSee('Referrals')->assertSee('New and returning visits');
    }

    public function test_cashier_report_hides_clinical_summaries(): void
    {
        $c = $this->start();
        app(SpecialistWorkflow::class)->saveClinical($c->id, ['version' => 1, 'complaint' => 'Pain', 'diagnosis' => 'Confidential diagnosis', 'plan' => 'Review']);
        $cashier = $this->user('specialist_cashier');
        app(SpecialistAccess::class)->grant($c->patient_id, $cashier->id);
        $this->actingAs($cashier);
        $bill = app(SpecialistWorkflow::class)->bill($c->id);
        $this->assertStringStartsWith('SBL', $bill->bill_number);
        $this->get(route('specialist.reports'))->assertOk()->assertSee('Cohort finances')->assertDontSee('Confidential diagnosis')->assertDontSee('Recorded diagnoses');
    }
}
