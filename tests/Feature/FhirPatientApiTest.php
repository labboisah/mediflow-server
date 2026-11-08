<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureCentralLicense;
use App\Http\Middleware\EnsureInstallationModules;
use App\Http\Middleware\IsolatePartnerPortal;
use App\Http\Middleware\ProtectSpecialistRecords;
use App\Models\DiagnosticReport;
use App\Models\DiagnosticReportRevision;
use App\Models\Investigation;
use App\Models\InvestigationRequest;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicineType;
use App\Models\Patient;
use App\Models\PatientDemographic;
use App\Models\PatientVisit;
use App\Models\Permission;
use App\Models\PharmacyDispense;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\Route;
use App\Models\Route as MedicationRoute;
use App\Models\Service;
use App\Models\ServiceRequest as LocalServiceRequest;
use App\Models\User;
use App\Models\VitalSign;
use App\Services\FhirResourceIdentityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FhirPatientApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'fhir.enabled' => true,
            'fhir.base_url' => 'https://mediflow.example/api/fhir/R4',
            'fhir.patient_identifier_system' => 'https://mediflow.example/identifiers/hospital-number',
            'audit.queue' => false,
        ]);
        $this->withoutMiddleware([
            EnsureCentralLicense::class,
            EnsureInstallationModules::class,
            ProtectSpecialistRecords::class,
            IsolatePartnerPortal::class,
        ]);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::create('role_permission', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
        });
        Schema::create('temporary_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->string('hospital_number')->unique();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('patient_demographics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('lga')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('patient_visits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->dateTime('visit_date');
            $table->string('visit_type')->nullable();
            $table->text('reason_for_visit')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('admissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_visit_id');
            $table->unsignedBigInteger('bed_id')->nullable();
            $table->timestamp('date')->nullable();
            $table->string('time')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('vital_signs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_visit_id');
            $table->decimal('body_temperature', 5, 2)->nullable();
            $table->integer('blood_pressure_systolic')->nullable();
            $table->integer('blood_pressure_diastolic')->nullable();
            $table->integer('heart_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->decimal('oxygen_saturation', 5, 2)->nullable();
            $table->decimal('blood_glucose', 8, 2)->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->decimal('height', 6, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->dateTime('recorded_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('investigations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('investigation_type_id')->nullable();
            $table->text('name');
            $table->string('price')->nullable();
            $table->string('code')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('service_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_visit_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->unsignedBigInteger('walkin_id')->nullable();
            $table->text('clinical_diagnoses')->nullable();
            $table->date('requested_at');
            $table->date('completed_at')->nullable();
            $table->string('status')->default('Pending');
            $table->string('payment_status')->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('investigation_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_visit_id')->nullable();
            $table->unsignedBigInteger('investigation_id')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->unsignedBigInteger('walkin_id')->nullable();
            $table->text('clinical_diagnoses')->nullable();
            $table->date('requested_at');
            $table->date('completed_at')->nullable();
            $table->text('specimen')->nullable();
            $table->string('lab_no')->nullable();
            $table->string('status')->default('Pending');
            $table->string('payment_status')->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('diagnostic_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('investigation_request_id')->unique();
            $table->text('clinical_note')->nullable();
            $table->longText('findings')->nullable();
            $table->longText('impression')->nullable();
            $table->string('status')->default('performed');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->dateTime('performed_at')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->dateTime('reported_at')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
        });
        Schema::create('diagnostic_report_revisions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('diagnostic_report_id');
            $table->unsignedInteger('version');
            $table->string('action', 24);
            $table->text('clinical_note')->nullable();
            $table->longText('findings')->nullable();
            $table->longText('impression')->nullable();
            $table->string('status')->default('performed');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->dateTime('changed_at');
            $table->timestamps();
        });
        Schema::create('medicine_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('medicines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('medicine_type_id')->nullable();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->string('strength')->nullable();
            $table->string('form')->nullable();
            $table->string('manufacturer')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('routes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_visit_id');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('prescribe_by');
            $table->text('treatment_diagnosis')->nullable();
            $table->timestamps();
        });
        Schema::create('prescription_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('prescription_id');
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('medicine_id');
            $table->string('dosage');
            $table->string('period');
            $table->string('duration');
            $table->string('medication_status')->default('started');
            $table->dateTime('medication_status_changed_at')->nullable();
            $table->unsignedBigInteger('medication_status_changed_by')->nullable();
            $table->timestamps();
        });
        Schema::create('medicine_batches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('medicine_id');
            $table->string('batch_number');
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->integer('quantity_received')->default(0);
            $table->integer('quantity_remaining')->default(0);
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
        Schema::create('pharmacy_dispenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('medicine_batch_id');
            $table->unsignedBigInteger('prescription_item_id')->nullable();
            $table->string('type');
            $table->integer('quantity');
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('before')->nullable();
            $table->text('after')->nullable();
            $table->text('meta')->nullable();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_01_000001_create_fhir_resource_identities_table.php'))->up();
    }

    public function test_capability_statement_only_advertises_implemented_read_surfaces(): void
    {
        $this->getJson('/api/fhir/R4/metadata')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/fhir+json; charset=utf-8')
            ->assertJsonPath('resourceType', 'CapabilityStatement')
            ->assertJsonPath('fhirVersion', '4.0.1')
            ->assertJsonPath('rest.0.resource.0.type', 'Patient')
            ->assertJsonPath('rest.0.resource.0.interaction.0.code', 'read')
            ->assertJsonPath('rest.0.resource.0.interaction.1.code', 'search-type')
            ->assertJsonPath('rest.0.resource.1.type', 'Encounter')
            ->assertJsonPath('rest.0.resource.2.type', 'Observation')
            ->assertJsonPath('rest.0.resource.3.type', 'ServiceRequest')
            ->assertJsonPath('rest.0.resource.4.type', 'DiagnosticReport')
            ->assertJsonPath('rest.0.resource.5.type', 'Medication')
            ->assertJsonPath('rest.0.resource.6.type', 'MedicationRequest')
            ->assertJsonPath('rest.0.resource.7.type', 'MedicationDispense');
    }

    public function test_patient_read_requires_a_scoped_token_and_existing_patient_permission(): void
    {
        $this->getJson('/api/fhir/R4/Patient')
            ->assertUnauthorized()
            ->assertJsonPath('resourceType', 'OperationOutcome');

        $user = $this->userWithPatientPermission();
        $token = $user->createToken('missing-fhir-scope', [])->plainTextToken;
        $this->withToken($token)->getJson('/api/fhir/R4/Patient')
            ->assertForbidden()
            ->assertJsonPath('resourceType', 'OperationOutcome');

        $unprivilegedUser = User::withoutEvents(fn () => User::query()->create([
            'name' => 'Unprivileged Integration',
            'email' => 'unprivileged@example.test',
            'password' => 'test-password',
            'active' => true,
        ]));
        $scopedToken = $unprivilegedUser->createToken('missing-patient-permission', ['fhir.patient.read'])->plainTextToken;
        $this->withToken($scopedToken)->getJson('/api/fhir/R4/Patient')
            ->assertForbidden()
            ->assertJsonPath('resourceType', 'OperationOutcome');
    }

    public function test_patient_search_returns_mapped_fhir_resources_and_stable_logical_ids(): void
    {
        $this->userWithPatientPermission();
        $patient = $this->patient('HF-001', [
            'first_name' => 'Amina',
            'last_name' => 'Bello',
            'gender' => 'Female',
            'date_of_birth' => '1990-04-03',
            'phone_number' => '+234800000001',
            'email' => 'amina@example.test',
            'address' => '12 Example Road',
            'lga' => 'Wamakko',
        ]);
        $token = User::query()->first()->createToken('partner-fhir', ['fhir.patient.read'])->plainTextToken;

        $first = $this->withToken($token)->getJson('/api/fhir/R4/Patient?identifier=HF-001')
            ->assertOk()
            ->assertJsonPath('resourceType', 'Bundle')
            ->assertJsonPath('type', 'searchset')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('entry.0.resource.resourceType', 'Patient')
            ->assertJsonPath('entry.0.resource.identifier.0.system', config('fhir.patient_identifier_system'))
            ->assertJsonPath('entry.0.resource.identifier.0.value', 'HF-001')
            ->assertJsonPath('entry.0.resource.name.0.family', 'Bello')
            ->assertJsonPath('entry.0.resource.name.0.given.0', 'Amina')
            ->assertJsonPath('entry.0.resource.gender', 'female')
            ->assertJsonPath('entry.0.resource.birthDate', '1990-04-03');

        $logicalId = $first->json('entry.0.resource.id');
        $this->assertMatchesRegularExpression('/^[A-Fa-f0-9-]{36}$/', $logicalId);
        $this->assertSame(
            $logicalId,
            $this->withToken($token)->getJson('/api/fhir/R4/Patient?identifier=HF-001')->json('entry.0.resource.id'),
        );
        $this->withToken($token)->getJson('/api/fhir/R4/Patient/'.$logicalId)
            ->assertOk()
            ->assertJsonPath('id', $logicalId)
            ->assertJsonPath('identifier.0.value', 'HF-001');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'fhir.patient.search',
            'actor_id' => User::query()->first()->id,
        ]);
    }

    public function test_patient_search_is_bounded_and_reports_invalid_or_unsupported_queries_as_fhir_errors(): void
    {
        $user = $this->userWithPatientPermission();
        $token = $user->createToken('partner-fhir', ['fhir.patient.read'])->plainTextToken;

        $this->withToken($token)->getJson('/api/fhir/R4/Patient?_count=101')
            ->assertBadRequest()
            ->assertJsonPath('resourceType', 'OperationOutcome');
        $this->withToken($token)->getJson('/api/fhir/R4/Patient?unsupported=value')
            ->assertBadRequest()
            ->assertJsonPath('issue.0.code', 'not-supported');
    }

    public function test_patient_search_paginates_and_uses_the_configured_server_url_for_links(): void
    {
        $user = $this->userWithPatientPermission();
        $this->patient('HF-001', ['first_name' => 'Amina', 'last_name' => 'Bello']);
        $this->patient('HF-002', ['first_name' => 'Bala', 'last_name' => 'Garba']);
        $token = $user->createToken('partner-fhir', ['fhir.patient.read'])->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/fhir/R4/Patient?_count=1&page=1')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonCount(1, 'entry')
            ->assertJsonPath('link.1.relation', 'next');

        $this->assertStringStartsWith('https://mediflow.example/api/fhir/R4/Patient?', $response->json('link.1.url'));
        $this->assertStringContainsString('page=2', $response->json('link.1.url'));
        $this->assertSame(
            'HF-002',
            $this->withToken($token)->getJson('/api/fhir/R4/Patient?_count=1&page=2')->json('entry.0.resource.identifier.0.value'),
        );
    }

    public function test_disabled_fhir_api_and_unmapped_patient_ids_return_operation_outcomes(): void
    {
        config(['fhir.enabled' => false]);
        $this->getJson('/api/fhir/R4/metadata')
            ->assertNotFound()
            ->assertJsonPath('resourceType', 'OperationOutcome');

        config(['fhir.enabled' => true]);
        $user = $this->userWithPatientPermission();
        $token = $user->createToken('partner-fhir', ['fhir.patient.read'])->plainTextToken;
        $this->withToken($token)->getJson('/api/fhir/R4/Patient/00000000-0000-4000-8000-000000000000')
            ->assertNotFound()
            ->assertJsonPath('resourceType', 'OperationOutcome');
    }

    public function test_encounter_is_read_and_search_mapped_with_patient_reference_and_inpatient_class(): void
    {
        $user = $this->userWithPermission('visit.read', 'encounter-integration@example.test');
        $patient = $this->patient('HF-003', ['first_name' => 'Chika', 'last_name' => 'Umar']);
        $visit = PatientVisit::withoutEvents(fn () => PatientVisit::query()->create([
            'patient_id' => $patient->id,
            'visit_date' => '2026-10-31 09:30:00',
            'visit_type' => 'Consultation',
            'reason_for_visit' => 'Review',
            'status' => 'Admitted',
            'created_by' => $user->id,
        ]));
        DB::table('admissions')->insert([
            'patient_visit_id' => $visit->id,
            'status' => 'registered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patientId = app(FhirResourceIdentityService::class)->idForPatient($patient);
        $token = $user->createToken('encounter-partner', ['fhir.encounter.read'])->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/fhir/R4/Encounter?patient=Patient/'.$patientId.'&status=in-progress')
            ->assertOk()
            ->assertJsonPath('resourceType', 'Bundle')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('entry.0.resource.resourceType', 'Encounter')
            ->assertJsonPath('entry.0.resource.status', 'in-progress')
            ->assertJsonPath('entry.0.resource.class.code', 'IMP')
            ->assertJsonPath('entry.0.resource.subject.reference', 'Patient/'.$patientId)
            ->assertJsonPath('entry.0.resource.type.0.text', 'Consultation');

        $encounterId = $response->json('entry.0.resource.id');
        $this->withToken($token)->getJson('/api/fhir/R4/Encounter/'.$encounterId)
            ->assertOk()
            ->assertJsonPath('id', $encounterId);
    }

    public function test_vital_sign_observation_uses_confirmed_units_and_standard_component_codes(): void
    {
        $user = $this->userWithPermission('vital_sign.read', 'observation-integration@example.test');
        $patient = $this->patient('HF-004', ['first_name' => 'Dara', 'last_name' => 'Ibrahim']);
        $visit = PatientVisit::withoutEvents(fn () => PatientVisit::query()->create([
            'patient_id' => $patient->id,
            'visit_date' => '2026-10-31 10:00:00',
            'visit_type' => 'Consultation',
            'status' => 'Active',
            'created_by' => $user->id,
        ]));
        $vitalSign = VitalSign::withoutEvents(fn () => VitalSign::query()->create([
            'patient_visit_id' => $visit->id,
            'body_temperature' => 36.7,
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'heart_rate' => 72,
            'respiratory_rate' => 16,
            'oxygen_saturation' => 98,
            'blood_glucose' => 5.4,
            'weight' => 65.2,
            'height' => 170,
            'recorded_by' => $user->id,
            'recorded_date' => '2026-10-31 10:10:00',
        ]));
        $patientId = app(FhirResourceIdentityService::class)->idForPatient($patient);
        $token = $user->createToken('observation-partner', ['fhir.observation.read'])->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/fhir/R4/Observation?subject=Patient/'.$patientId.'&date=2026-10-31')
            ->assertOk()
            ->assertJsonPath('resourceType', 'Bundle')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('entry.0.resource.resourceType', 'Observation')
            ->assertJsonPath('entry.0.resource.status', 'unknown')
            ->assertJsonPath('entry.0.resource.subject.reference', 'Patient/'.$patientId)
            ->assertJsonPath('entry.0.resource.component.0.code.coding.0.code', '8310-5')
            ->assertJsonPath('entry.0.resource.component.0.valueQuantity.value', 36.7)
            ->assertJsonPath('entry.0.resource.component.0.valueQuantity.code', 'Cel')
            ->assertJsonPath('entry.0.resource.component.1.valueQuantity.code', 'mm[Hg]');

        $observationId = $response->json('entry.0.resource.id');
        $this->withToken($token)->getJson('/api/fhir/R4/Observation/'.$observationId)
            ->assertOk()
            ->assertJsonPath('id', $observationId);
        $this->assertSame(
            0,
            collect($response->json('entry.0.resource.component'))->where('code.coding.0.code', '2345-7')->count(),
            'Glucose is omitted because the source model does not define its measurement unit.',
        );
    }

    public function test_service_request_search_combines_both_order_models_with_distinct_logical_ids(): void
    {
        $user = $this->userWithPermission(['investigation_request.read', 'service_request.read'], 'service-request-integration@example.test');
        $patient = $this->patient('HF-005', ['first_name' => 'Efe', 'last_name' => 'Okafor']);
        $visit = PatientVisit::withoutEvents(fn () => PatientVisit::query()->create([
            'patient_id' => $patient->id,
            'visit_date' => '2026-10-31 11:00:00',
            'visit_type' => 'Consultation',
            'status' => 'Active',
            'created_by' => $user->id,
        ]));
        $service = Service::query()->create(['name' => 'Physiotherapy']);
        $investigation = Investigation::query()->create([
            'name' => 'Chest X-Ray',
            'code' => 'XR-CHEST',
        ]);
        $localServiceRequest = LocalServiceRequest::query()->create([
            'patient_visit_id' => $visit->id,
            'service_id' => $service->id,
            'clinical_diagnoses' => 'Mobility review',
            'requested_at' => '2026-10-31',
            'status' => 'Pending',
        ]);
        $investigationRequest = InvestigationRequest::withoutEvents(fn () => InvestigationRequest::query()->create([
            'patient_visit_id' => $visit->id,
            'investigation_id' => $investigation->id,
            'clinical_diagnoses' => 'Cough',
            'requested_at' => '2026-10-31',
            'status' => 'Pending',
        ]));
        $identities = app(FhirResourceIdentityService::class);
        $patientId = $identities->idForPatient($patient);
        $serviceRequestId = $identities->idForServiceRequest($localServiceRequest);
        $investigationRequestId = $identities->idForServiceRequest($investigationRequest);
        $this->assertNotSame($serviceRequestId, $investigationRequestId);
        $token = $user->createToken('service-request-partner', ['fhir.servicerequest.read'])->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/fhir/R4/ServiceRequest?patient=Patient/'.$patientId.'&authored=2026-10-31&status=active')
            ->assertOk()
            ->assertJsonPath('resourceType', 'Bundle')
            ->assertJsonPath('total', 2)
            ->assertJsonCount(2, 'entry')
            ->assertJsonPath('entry.0.resource.intent', 'order')
            ->assertJsonPath('entry.0.resource.status', 'active')
            ->assertJsonPath('entry.0.resource.subject.reference', 'Patient/'.$patientId);

        $codes = collect($response->json('entry'))->pluck('resource.code.text')->all();
        $this->assertEqualsCanonicalizing(['Physiotherapy', 'Chest X-Ray'], $codes);
        $this->withToken($token)->getJson('/api/fhir/R4/ServiceRequest/'.$investigationRequestId)
            ->assertOk()
            ->assertJsonPath('id', $investigationRequestId)
            ->assertJsonPath('code.text', 'Chest X-Ray')
            ->assertJsonPath('reasonCode.0.text', 'Cough');

    }

    public function test_service_request_requires_permissions_for_both_local_order_sources(): void
    {
        $unprivilegedUser = $this->userWithPermission('investigation_request.read', 'service-request-unprivileged@example.test');
        $unprivilegedToken = $unprivilegedUser->createToken('service-request-missing-permission', ['fhir.servicerequest.read'])->plainTextToken;
        $this->withToken($unprivilegedToken)->getJson('/api/fhir/R4/ServiceRequest')
            ->assertForbidden()
            ->assertJsonPath('resourceType', 'OperationOutcome');
    }

    public function test_diagnostic_report_maps_verified_report_status_and_links_to_its_order(): void
    {
        $user = $this->userWithPermission('investigation_result.read', 'diagnostic-report-integration@example.test');
        $patient = $this->patient('HF-006', ['first_name' => 'Femi', 'last_name' => 'Ade']);
        $visit = PatientVisit::withoutEvents(fn () => PatientVisit::query()->create([
            'patient_id' => $patient->id,
            'visit_date' => '2026-10-31 12:00:00',
            'visit_type' => 'Consultation',
            'status' => 'Active',
            'created_by' => $user->id,
        ]));
        $investigation = Investigation::query()->create(['name' => 'Abdominal Ultrasound']);
        $investigationRequest = InvestigationRequest::withoutEvents(fn () => InvestigationRequest::query()->create([
            'patient_visit_id' => $visit->id,
            'investigation_id' => $investigation->id,
            'clinical_diagnoses' => 'Abdominal pain',
            'requested_at' => '2026-10-31',
            'status' => 'Completed',
        ]));
        $report = DiagnosticReport::query()->create([
            'investigation_request_id' => $investigationRequest->id,
            'clinical_note' => 'Clinical context.',
            'findings' => 'No acute abnormality.',
            'impression' => 'No acute findings.',
            'status' => 'reported',
            'performed_at' => '2026-10-31 12:15:00',
            'reported_at' => '2026-10-31 12:30:00',
            'version' => 2,
        ]);
        DiagnosticReportRevision::query()->create([
            'diagnostic_report_id' => $report->id,
            'version' => 2,
            'action' => 'amended',
            'status' => 'reported',
            'changed_at' => '2026-10-31 12:30:00',
        ]);
        $identities = app(FhirResourceIdentityService::class);
        $patientId = $identities->idForPatient($patient);
        $orderId = $identities->idForServiceRequest($investigationRequest);
        $reportId = $identities->idForDiagnosticReport($report);
        $token = $user->createToken('diagnostic-report-partner', ['fhir.diagnosticreport.read'])->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/fhir/R4/DiagnosticReport?patient=Patient/'.$patientId.'&date=2026-10-31&status=amended')
            ->assertOk()
            ->assertJsonPath('resourceType', 'Bundle')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('entry.0.resource.resourceType', 'DiagnosticReport')
            ->assertJsonPath('entry.0.resource.status', 'amended')
            ->assertJsonPath('entry.0.resource.code.text', 'Abdominal Ultrasound')
            ->assertJsonPath('entry.0.resource.subject.reference', 'Patient/'.$patientId)
            ->assertJsonPath('entry.0.resource.basedOn.0.reference', 'ServiceRequest/'.$orderId)
            ->assertJsonPath('entry.0.resource.conclusion', 'No acute findings.');

        $this->assertSame(
            "Clinical note: Clinical context.\n\nFindings: No acute abnormality.\n\nImpression: No acute findings.",
            base64_decode($response->json('entry.0.resource.presentedForm.0.data'), true),
        );
        $this->withToken($token)->getJson('/api/fhir/R4/DiagnosticReport/'.$reportId)
            ->assertOk()
            ->assertJsonPath('id', $reportId)
            ->assertJsonPath('status', 'amended');
    }

    public function test_generated_resource_bundle_validates_against_r4_when_the_official_cli_is_configured(): void
    {
        $validatorJar = getenv('FHIR_VALIDATOR_JAR');
        if (! $validatorJar || ! is_file($validatorJar)) {
            $this->markTestSkipped('Set FHIR_VALIDATOR_JAR to the pinned HL7 validator_cli.jar to run R4 conformance validation.');
        }

        $permissions = [
            'patient.read',
            'visit.read',
            'vital_sign.read',
            'investigation_request.read',
            'service_request.read',
            'investigation_result.read',
            'medicine.read',
            'prescription.read',
            'dispense.read',
        ];
        $user = $this->userWithPermission($permissions, 'fhir-validator@example.test');
        $patient = $this->patient('HF-007', ['first_name' => 'Synthetic', 'last_name' => 'Patient']);
        $visit = PatientVisit::withoutEvents(fn () => PatientVisit::query()->create([
            'patient_id' => $patient->id,
            'visit_date' => '2026-10-31 13:00:00',
            'visit_type' => 'Consultation',
            'status' => 'Active',
            'created_by' => $user->id,
        ]));
        $vitalSign = VitalSign::withoutEvents(fn () => VitalSign::query()->create([
            'patient_visit_id' => $visit->id,
            'body_temperature' => 36.8,
            'recorded_date' => '2026-10-31 13:10:00',
        ]));
        $investigation = Investigation::query()->create(['name' => 'Synthetic Imaging Study']);
        $investigationRequest = InvestigationRequest::withoutEvents(fn () => InvestigationRequest::query()->create([
            'patient_visit_id' => $visit->id,
            'investigation_id' => $investigation->id,
            'clinical_diagnoses' => 'Synthetic clinical indication',
            'requested_at' => '2026-10-31',
            'status' => 'Completed',
        ]));
        $report = DiagnosticReport::query()->create([
            'investigation_request_id' => $investigationRequest->id,
            'findings' => 'Synthetic finding.',
            'impression' => 'Synthetic impression.',
            'status' => 'reported',
            'performed_at' => '2026-10-31 13:15:00',
            'reported_at' => '2026-10-31 13:30:00',
            'version' => 1,
        ]);
        DiagnosticReportRevision::query()->create([
            'diagnostic_report_id' => $report->id,
            'version' => 1,
            'action' => 'reported',
            'status' => 'reported',
            'changed_at' => '2026-10-31 13:30:00',
        ]);
        $medicineType = MedicineType::query()->create(['name' => 'Capsule']);
        $medicine = Medicine::query()->create([
            'medicine_type_id' => $medicineType->id,
            'name' => 'Synthetic medicine',
            'generic_name' => 'Synthetic ingredient',
            'strength' => '250 mg',
        ]);
        $route = MedicationRoute::query()->create(['name' => 'Oral']);
        $prescription = Prescription::withoutEvents(fn () => Prescription::query()->create([
            'patient_visit_id' => $visit->id,
            'status' => 'paid',
            'prescribe_by' => $user->id,
            'treatment_diagnosis' => 'Synthetic indication',
            'created_at' => '2026-10-31 13:05:00',
            'updated_at' => '2026-10-31 13:05:00',
        ]));
        $prescriptionItem = PrescriptionItem::query()->create([
            'prescription_id' => $prescription->id,
            'route_id' => $route->id,
            'medicine_id' => $medicine->id,
            'dosage' => '1 capsule',
            'period' => 'once daily',
            'duration' => '3 days',
            'medication_status' => 'started',
        ]);
        $batch = MedicineBatch::query()->create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'SYN-002',
            'purchase_price' => 1,
            'selling_price' => 2,
            'quantity_received' => 10,
            'quantity_remaining' => 9,
            'expiry_date' => '2027-10-31',
        ]);
        $dispense = PharmacyDispense::query()->create([
            'medicine_batch_id' => $batch->id,
            'prescription_item_id' => $prescriptionItem->id,
            'type' => 'dispense',
            'quantity' => 1,
            'reference' => 'SYN-RECEIPT-002',
            'created_by' => $user->id,
            'created_at' => '2026-10-31 13:30:00',
            'updated_at' => '2026-10-31 13:30:00',
        ]);

        $identities = app(FhirResourceIdentityService::class);
        $token = $user->createToken('validator-fhir', [
            'fhir.patient.read',
            'fhir.encounter.read',
            'fhir.observation.read',
            'fhir.servicerequest.read',
            'fhir.diagnosticreport.read',
            'fhir.medication.read',
            'fhir.medicationrequest.read',
            'fhir.medicationdispense.read',
        ])->plainTextToken;
        $resources = [
            $this->withToken($token)->getJson('/api/fhir/R4/Patient/'.$identities->idForPatient($patient))->assertOk()->json(),
            $this->withToken($token)
                ->getJson('/api/fhir/R4/Encounter/'.$identities->idForEncounter($visit))->assertOk()->json(),
            $this->withToken($token)
                ->getJson('/api/fhir/R4/Observation/'.$identities->idForObservation($vitalSign))->assertOk()->json(),
            $this->withToken($token)
                ->getJson('/api/fhir/R4/ServiceRequest/'.$identities->idForServiceRequest($investigationRequest))->assertOk()->json(),
            $this->withToken($token)
                ->getJson('/api/fhir/R4/DiagnosticReport/'.$identities->idForDiagnosticReport($report))->assertOk()->json(),
            $this->withToken($token)
                ->getJson('/api/fhir/R4/Medication/'.$identities->idForMedication($medicine))->assertOk()->json(),
            $this->withToken($token)
                ->getJson('/api/fhir/R4/MedicationRequest/'.$identities->idForMedicationRequest($prescriptionItem))->assertOk()->json(),
            $this->withToken($token)
                ->getJson('/api/fhir/R4/MedicationDispense/'.$identities->idForMedicationDispense($dispense))->assertOk()->json(),
        ];
        $bundle = [
            'resourceType' => 'Bundle',
            'type' => 'collection',
            'entry' => array_map(fn (array $resource): array => [
                'fullUrl' => rtrim((string) config('fhir.base_url'), '/').'/'.$resource['resourceType'].'/'.$resource['id'],
                'resource' => $resource,
            ], $resources),
        ];

        $this->validateWithOfficialR4Cli($validatorJar, $bundle);
    }

    public function test_medication_resources_link_catalogue_orders_and_actual_dispense_events(): void
    {
        $permissions = ['medicine.read', 'prescription.read', 'dispense.read'];
        $user = $this->userWithPermission($permissions, 'medication-integration@example.test');
        $patient = $this->patient('HF-008', ['first_name' => 'Synthetic', 'last_name' => 'Medication']);
        $visit = PatientVisit::withoutEvents(fn () => PatientVisit::query()->create([
            'patient_id' => $patient->id,
            'visit_date' => '2026-10-31 14:00:00',
            'visit_type' => 'Consultation',
            'status' => 'Active',
            'created_by' => $user->id,
        ]));
        $medicineType = MedicineType::query()->create(['name' => 'Tablet']);
        $medicine = Medicine::query()->create([
            'medicine_type_id' => $medicineType->id,
            'name' => 'Example 500 mg',
            'generic_name' => 'Example ingredient',
            'strength' => '500 mg',
            'form' => 'Tablet',
            'manufacturer' => 'Example manufacturer',
            'description' => 'Synthetic catalogue entry',
        ]);
        $route = Route::query()->create(['name' => 'Oral']);
        $prescription = Prescription::withoutEvents(fn () => Prescription::query()->create([
            'patient_visit_id' => $visit->id,
            'status' => 'paid',
            'prescribe_by' => $user->id,
            'treatment_diagnosis' => 'Synthetic indication',
            'created_at' => '2026-10-31 14:05:00',
            'updated_at' => '2026-10-31 14:05:00',
        ]));
        $item = PrescriptionItem::query()->create([
            'prescription_id' => $prescription->id,
            'route_id' => $route->id,
            'medicine_id' => $medicine->id,
            'dosage' => '1 tablet',
            'period' => 'twice daily',
            'duration' => '5 days',
            'medication_status' => 'started',
        ]);
        $batch = MedicineBatch::query()->create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'SYN-001',
            'purchase_price' => 1,
            'selling_price' => 2,
            'quantity_received' => 10,
            'quantity_remaining' => 8,
            'expiry_date' => '2027-10-31',
        ]);
        $dispense = PharmacyDispense::query()->create([
            'medicine_batch_id' => $batch->id,
            'prescription_item_id' => $item->id,
            'type' => 'dispense',
            'quantity' => 2,
            'reference' => 'SYN-RECEIPT-001',
            'created_by' => $user->id,
            'created_at' => '2026-10-31 14:30:00',
            'updated_at' => '2026-10-31 14:30:00',
        ]);
        PharmacyDispense::query()->create([
            'medicine_batch_id' => $batch->id,
            'prescription_item_id' => null,
            'type' => 'return',
            'quantity' => 1,
            'reference' => 'SYN-RETURN-001',
            'created_by' => $user->id,
        ]);

        $identities = app(FhirResourceIdentityService::class);
        $patientId = $identities->idForPatient($patient);
        $encounterId = $identities->idForEncounter($visit);
        $medicationId = $identities->idForMedication($medicine);
        $requestId = $identities->idForMedicationRequest($item);
        $dispenseId = $identities->idForMedicationDispense($dispense);
        $token = $user->createToken('medication-partner', [
            'fhir.medication.read',
            'fhir.medicationrequest.read',
            'fhir.medicationdispense.read',
        ])->plainTextToken;

        $this->withToken($token)->getJson('/api/fhir/R4/Medication/'.$medicationId)
            ->assertOk()
            ->assertJsonPath('resourceType', 'Medication')
            ->assertJsonPath('code.text', 'Example 500 mg | Generic: Example ingredient | Strength: 500 mg')
            ->assertJsonPath('form.text', 'Tablet');

        $requestResponse = $this->withToken($token)->getJson('/api/fhir/R4/MedicationRequest?patient=Patient/'.$patientId.'&encounter=Encounter/'.$encounterId.'&authoredon=2026-10-31&status=active')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('entry.0.resource.status', 'active')
            ->assertJsonPath('entry.0.resource.intent', 'order')
            ->assertJsonPath('entry.0.resource.medicationReference.reference', 'Medication/'.$medicationId)
            ->assertJsonPath('entry.0.resource.subject.reference', 'Patient/'.$patientId)
            ->assertJsonPath('entry.0.resource.encounter.reference', 'Encounter/'.$encounterId)
            ->assertJsonPath('entry.0.resource.reasonCode.0.text', 'Synthetic indication')
            ->assertJsonPath('entry.0.resource.dosageInstruction.0.text', 'Dose: 1 tablet; Period: twice daily; Duration: 5 days; Route: Oral');

        $this->withToken($token)->getJson('/api/fhir/R4/MedicationRequest/'.$requestId)
            ->assertOk()
            ->assertJsonPath('id', $requestId);

        $dispenseResponse = $this->withToken($token)->getJson('/api/fhir/R4/MedicationDispense?patient=Patient/'.$patientId.'&prescription=MedicationRequest/'.$requestId.'&whenhandedover=2026-10-31&status=completed')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('entry.0.resource.status', 'completed')
            ->assertJsonPath('entry.0.resource.medicationReference.reference', 'Medication/'.$medicationId)
            ->assertJsonPath('entry.0.resource.subject.reference', 'Patient/'.$patientId)
            ->assertJsonPath('entry.0.resource.context.reference', 'Encounter/'.$encounterId)
            ->assertJsonPath('entry.0.resource.authorizingPrescription.0.reference', 'MedicationRequest/'.$requestId)
            ->assertJsonPath('entry.0.resource.whenHandedOver', '2026-10-31T14:30:00+00:00');
        $this->assertArrayNotHasKey('quantity', $dispenseResponse->json('entry.0.resource'));

        $this->withToken($token)->getJson('/api/fhir/R4/MedicationDispense/'.$dispenseId)
            ->assertOk()
            ->assertJsonPath('id', $dispenseId);
    }

    private function userWithPatientPermission(): User
    {
        return $this->userWithPermission('patient.read', 'fhir-partner@example.test');
    }

    private function userWithPermission(string|array $permissionNames, string $email): User
    {
        $user = User::withoutEvents(fn () => User::query()->create([
            'name' => 'FHIR Partner Account',
            'email' => $email,
            'password' => 'test-password',
            'active' => true,
        ]));
        $role = Role::query()->create(['name' => 'fhir-integration-'.substr(sha1($email), 0, 10)]);
        $user->roles()->attach($role);
        foreach ((array) $permissionNames as $permissionName) {
            $permission = Permission::query()->create([
                'name' => $permissionName,
                'display_name' => 'Read '.$permissionName,
            ]);
            $role->permissions()->attach($permission);
        }

        return $user;
    }

    private function validateWithOfficialR4Cli(string $validatorJar, array $bundle): void
    {
        $file = tempnam(sys_get_temp_dir(), 'mediflow-fhir-');
        $this->assertNotFalse($file);
        file_put_contents($file, json_encode($bundle, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        try {
            $process = proc_open(
                ['java', '-jar', $validatorJar, '-version', '4.0.1', $file],
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
                base_path(),
            );
            $this->assertIsResource($process, 'The official HL7 FHIR validator process could not be started.');
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            $this->assertSame(0, $exitCode, "The generated synthetic resource bundle failed R4 validation:\n".$output);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function patient(string $hospitalNumber, array $demographic): Patient
    {
        $patient = Patient::withoutEvents(fn () => Patient::query()->create([
            'hospital_number' => $hospitalNumber,
        ]));

        PatientDemographic::query()->create(array_merge([
            'patient_id' => $patient->id,
        ], $demographic));

        return $patient;
    }
}
