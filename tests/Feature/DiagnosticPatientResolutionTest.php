<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\DiagnosticBillContact;
use App\Models\DiagnosticImportReference;
use App\Models\Patient;
use App\Models\PatientDemographic;
use App\Models\WalkinPatient;
use App\Services\Diagnostics\ResolveDiagnosticPatient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DiagnosticPatientResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'sync.behavior.auto_sync_enabled' => false]);
        DB::purge('sqlite');
        foreach (['diagnostic_bill_contacts', 'diagnostic_import_references', 'patient_demographics', 'walkin_patients', 'patients', 'bills', 'patient_visits'] as $table) Schema::dropIfExists($table);

        Schema::create('patients', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('file_type_id')->nullable(); $table->string('hospital_number')->unique();
            $table->string('payment_id')->nullable(); $table->dateTime('registration_date')->nullable(); $table->boolean('is_walkIn')->default(false);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('patient_demographics', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('patient_id'); $table->string('first_name'); $table->string('last_name');
            $table->string('phone_number')->unique(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('walkin_patients', function (Blueprint $table) {
            $table->id(); $table->string('name')->nullable(); $table->string('address')->nullable(); $table->string('phone_number')->nullable(); $table->timestamps();
        });
        Schema::create('patient_visits', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('patient_id'); $table->dateTime('visit_date'); $table->string('visit_type');
            $table->string('flag')->default('Normal'); $table->text('reason_for_visit')->nullable(); $table->string('status')->default('Active');
            $table->unsignedBigInteger('created_by'); $table->timestamps(); $table->softDeletes();
        });        Schema::create('bills', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('walkin_id')->nullable(); $table->unsignedBigInteger('patient_visit_id')->nullable();
            $table->string('bill_number')->unique(); $table->string('service_description'); $table->decimal('amount', 12, 2);
            $table->string('status'); $table->unsignedBigInteger('issued_by'); $table->dateTime('issued_date');
            $table->timestamps(); $table->softDeletes();
        });        Schema::create('diagnostic_bill_contacts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('bill_id')->unique(); $table->string('source_system');
            $table->string('source_patient_id')->nullable(); $table->string('patient_phone')->nullable(); $table->string('hospital_number')->nullable(); $table->timestamps();
        });        Schema::create('diagnostic_import_references', function (Blueprint $table) {
            $table->id(); $table->string('source_system'); $table->string('source_table'); $table->string('source_id');
            $table->string('target_type'); $table->unsignedBigInteger('target_id'); $table->json('metadata')->nullable(); $table->timestamps();
            $table->unique(['source_system', 'source_table', 'source_id']);
        });
    }

    public function test_reuses_existing_patient_by_hospital_number_and_keeps_source_mapping_idempotent(): void
    {
        $patient = Patient::create(['hospital_number' => 'HF-100', 'registration_date' => now()]);
        PatientDemographic::create(['patient_id' => $patient->id, 'first_name' => 'Ada', 'last_name' => 'Test', 'phone_number' => '08011112222']);
        $resolver = app(ResolveDiagnosticPatient::class);

        $resolved = $resolver->resolve(['patient_id' => '42', 'hospital_number' => 'HF-100', 'phone_number' => '08011112222']);
        $repeated = $resolver->resolve(['patient_id' => '42', 'name' => 'Changed Source Name']);

        $this->assertInstanceOf(Patient::class, $resolved);
        $this->assertSame($patient->id, $repeated->id);
        $this->assertSame(1, DiagnosticImportReference::count());
    }

    public function test_creates_separate_walkins_when_source_patient_ids_share_a_phone(): void
    {
        $resolver = app(ResolveDiagnosticPatient::class);
        $one = $resolver->resolve(['patient_id' => 'source-A', 'name' => 'Person A', 'phone_number' => '08033334444']);
        $two = $resolver->resolve(['patient_id' => 'source-B', 'name' => 'Person B', 'phone_number' => '08033334444']);

        $this->assertInstanceOf(WalkinPatient::class, $one);
        $this->assertInstanceOf(WalkinPatient::class, $two);
        $this->assertNotSame($one->id, $two->id);
        $this->assertSame(2, WalkinPatient::count());
    }

    public function test_attaches_source_patient_identity_and_contact_data_to_diagnostic_bill(): void
    {
        $bill = Bill::create(['bill_number' => 'DIAG-1', 'service_description' => 'Diagnostic', 'amount' => 100, 'status' => 'paid', 'issued_by' => 1, 'issued_date' => now()]);
        $identity = ['patient_id' => 'patient-44', 'phone_number' => '+2348012345678', 'hospital_number' => 'MRN-44'];
        $resolver = app(ResolveDiagnosticPatient::class);
        $walkin = WalkinPatient::create(['name' => 'Walk-in A', 'phone_number' => $identity['phone_number']]);
        $resolver->attachResolvedIdentityToBill($bill, $walkin, $identity, 1);

        $contact = DiagnosticBillContact::where('bill_id', $bill->id)->firstOrFail();
        $this->assertSame('patient-44', $contact->source_patient_id);
        $this->assertSame('+2348012345678', $contact->patient_phone);
        $this->assertSame('MRN-44', $contact->hospital_number);
        $this->assertSame($walkin->id, $bill->fresh()->walkin_id);
    }
    public function test_links_existing_patient_bill_to_a_new_visit(): void
    {
        $patient = Patient::create(['hospital_number' => 'HF-300', 'registration_date' => now()]);
        $bill = Bill::create(['bill_number' => 'DIAG-2', 'service_description' => 'Diagnostic', 'amount' => 200, 'status' => 'paid', 'issued_by' => 1, 'issued_date' => now()]);

        app(ResolveDiagnosticPatient::class)->attachResolvedIdentityToBill($bill, $patient, ['patient_id' => 'source-300', 'hospital_number' => 'HF-300'], 1, now());

        $this->assertNotNull($bill->fresh()->patient_visit_id);
        $this->assertSame($patient->id, $bill->fresh()->patientVisit->patient_id);
        $this->assertNull($bill->fresh()->walkin_id);
    }
    public function test_sends_conflicting_hospital_and_phone_matches_for_review(): void
    {
        $hospitalPatient = Patient::create(['hospital_number' => 'HF-200', 'registration_date' => now()]);
        PatientDemographic::create(['patient_id' => $hospitalPatient->id, 'first_name' => 'One', 'last_name' => 'Person', 'phone_number' => '08055550001']);
        $phonePatient = Patient::create(['hospital_number' => 'HF-201', 'registration_date' => now()]);
        PatientDemographic::create(['patient_id' => $phonePatient->id, 'first_name' => 'Two', 'last_name' => 'Person', 'phone_number' => '08055550002']);

        $this->expectException(ValidationException::class);
        app(ResolveDiagnosticPatient::class)->resolve(['patient_id' => '99', 'hospital_number' => 'HF-200', 'phone_number' => '08055550002']);
    }
}





