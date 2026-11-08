<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PatientRegisterController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PatientRegisterPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $this->createRegisterTables();
        $this->seedPatients();
    }

    public function test_register_summary_counts_filtered_records_in_the_database(): void
    {
        $response = app(PatientRegisterController::class)->index(Request::create('/admin/patient-register', 'GET', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]));
        $data = $response->getData();

        $this->assertSame([
            'total' => 2,
            'registered' => 1,
            'walk_in' => 1,
            'male' => 1,
            'female' => 1,
            'other' => 0,
        ], $data['summary']);
        $this->assertSame(2, $data['patients']->total());
        $this->assertSame(['MED-002', 'MED-001'], $data['patients']->pluck('hospital_number')->all());
    }

    public function test_csv_stream_preserves_columns_order_and_filtered_rows(): void
    {
        $extraPatients = [];
        $extraDemographics = [];
        foreach (range(4, 504) as $patientId) {
            $extraPatients[] = [
                'id' => $patientId,
                'file_type_id' => 1,
                'hospital_number' => 'MED-' . str_pad((string) $patientId, 3, '0', STR_PAD_LEFT),
                'registration_date' => '2026-11-04 10:00:00',
                'is_walkIn' => false,
            ];
            $extraDemographics[] = [
                'id' => $patientId,
                'patient_id' => $patientId,
                'first_name' => 'Extra',
                'last_name' => (string) $patientId,
                'gender' => 'Other',
                'age' => 20,
                'phone_number' => '555-' . str_pad((string) $patientId, 4, '0', STR_PAD_LEFT),
            ];
        }
        foreach (array_chunk($extraPatients, 200) as $batch) {
            DB::table('patients')->insert($batch);
        }
        foreach (array_chunk($extraDemographics, 200) as $batch) {
            DB::table('patient_demographics')->insert($batch);
        }

        $response = app(PatientRegisterController::class)->csv(Request::create('/admin/patient-register/csv', 'GET', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]));

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('attachment; filename=admin-patient-register-2026-11-01-to-2026-11-30.csv', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('"Hospital Number","Patient Name",Gender,Age,"Phone Number","File Type","Patient Type",Visits,"Registration Date"', $csv);
        $this->assertStringContainsString('MED-002,"Bob Jones",Male,43,555-0102,General,Walk-in,1,"2026-11-03 10:00:00"', $csv);
        $this->assertStringContainsString('MED-001,"Alice Smith",Female,32,555-0101,General,Registered,27,"2026-11-02 10:00:00"', $csv);
        $this->assertStringNotContainsString('MED-003', $csv);
        $this->assertCount(504, preg_split('/\r\n|\r|\n/', trim($csv)));
    }

    public function test_register_summary_respects_gender_search_and_patient_type_filters(): void
    {
        $response = app(PatientRegisterController::class)->index(Request::create('/admin/patient-register', 'GET', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'search' => 'Bob',
            'gender' => 'Male',
            'patient_type' => 'walk_in',
        ]));

        $this->assertSame([
            'total' => 1,
            'registered' => 0,
            'walk_in' => 1,
            'male' => 1,
            'female' => 0,
            'other' => 0,
        ], $response->getData()['summary']);
    }

    public function test_patient_summary_statistics_cover_full_history_while_displaying_bounded_pages(): void
    {
        $patient = DB::table('patients')->where('hospital_number', 'MED-001')->first();

        $response = app(PatientRegisterController::class)->summary(
            \App\Models\Patient::findOrFail($patient->id)
        );
        $data = $response->getData();

        $this->assertSame(27, $data['stats']['visits']);
        $this->assertSame(2, $data['stats']['admissions']);
        $this->assertSame(2, $data['stats']['discharges']);
        $this->assertSame(2, $data['stats']['bills']);
        $this->assertSame(250.0, $data['stats']['billed']);
        $this->assertSame(175.0, $data['stats']['due']);
        $this->assertSame(1, $data['stats']['payments']);
        $this->assertSame(75.0, $data['stats']['paid']);
        $this->assertSame(25, $data['visits']->count());
        $this->assertSame(27, $data['visits']->total());
        $this->assertSame(25, $data['payments']->perPage());
        $this->assertSame(2, $data['bills']->count());
        $this->assertLessThanOrEqual(5, $data['bills']->count());
    }

    private function seedPatients(): void
    {
        $now = now();
        DB::table('file_types')->insert(['id' => 1, 'name' => 'General']);
        DB::table('patients')->insert([
            ['id' => 1, 'file_type_id' => 1, 'hospital_number' => 'MED-001', 'registration_date' => '2026-11-02 10:00:00', 'is_walkIn' => false],
            ['id' => 2, 'file_type_id' => 1, 'hospital_number' => 'MED-002', 'registration_date' => '2026-11-03 10:00:00', 'is_walkIn' => true],
            ['id' => 3, 'file_type_id' => 1, 'hospital_number' => 'MED-003', 'registration_date' => '2026-10-15 10:00:00', 'is_walkIn' => false],
        ]);
        DB::table('patient_demographics')->insert([
            ['id' => 1, 'patient_id' => 1, 'first_name' => 'Alice', 'last_name' => 'Smith', 'gender' => 'Female', 'age' => 32, 'phone_number' => '555-0101'],
            ['id' => 2, 'patient_id' => 2, 'first_name' => 'Bob', 'last_name' => 'Jones', 'gender' => 'Male', 'age' => 43, 'phone_number' => '555-0102'],
            ['id' => 3, 'patient_id' => 3, 'first_name' => 'Carol', 'last_name' => 'Jones', 'gender' => 'Other', 'age' => 28, 'phone_number' => '555-0103'],
        ]);
        foreach (range(1, 27) as $visitId) {
            DB::table('patient_visits')->insert([
                'id' => $visitId,
                'patient_id' => 1,
                'status' => 'Closed',
                'visit_date' => $now->copy()->subDays($visitId)->toDateTimeString(),
                'visit_type' => 'Consultation',
                'created_by' => null,
                'created_at' => $now->copy()->subDays($visitId),
                'updated_at' => $now->copy()->subDays($visitId),
            ]);
        }
        DB::table('patient_visits')->insert([
            ['id' => 28, 'patient_id' => 2, 'status' => 'Active', 'visit_date' => $now, 'visit_type' => 'Consultation'],
        ]);
        DB::table('bills')->insert([
            ['id' => 1, 'patient_visit_id' => 1, 'bill_number' => 'BILL-001', 'status' => 'pending', 'amount' => 100, 'due_amount' => 75, 'issued_date' => $now],
            ['id' => 2, 'patient_visit_id' => 2, 'bill_number' => 'BILL-002', 'status' => 'paid', 'amount' => 150, 'due_amount' => 100, 'issued_date' => $now],
        ]);
        DB::table('payments')->insert([
            ['id' => 1, 'bill_id' => 1, 'payment_id' => 'PAY-001', 'status' => 'completed', 'amount' => 75, 'payment_date' => $now],
        ]);
        DB::table('admissions')->insert([
            ['id' => 1, 'patient_visit_id' => 1, 'bed_id' => 1, 'date' => $now, 'time' => '10:00', 'admitted_by' => null, 'status' => 'discharged'],
        ]);
        DB::table('discharges')->insert([
            ['id' => 1, 'admission_id' => 1, 'discharge_by' => null, 'date' => $now, 'reason' => 'Routine'],
        ]);
        DB::table('patient_admissions')->insert([
            ['id' => 1, 'patient_id' => 1, 'admission_date' => $now, 'discharge_date' => $now, 'status' => 'discharged'],
        ]);
    }

    private function createRegisterTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('file_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('file_type_id')->nullable();
            $table->string('hospital_number');
            $table->dateTime('registration_date');
            $table->boolean('is_walkIn')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('patient_demographics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->integer('age')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('next_of_kin', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('name')->nullable();
            $table->string('telephone')->nullable();
            $table->softDeletes();
        });
        Schema::create('patient_visits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('status');
            $table->dateTime('visit_date');
            $table->string('visit_type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('bills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_visit_id');
            $table->string('bill_number')->nullable();
            $table->string('status')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->dateTime('issued_date')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bill_id');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('status');
            $table->decimal('amount', 12, 2);
            $table->dateTime('payment_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('admissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_visit_id');
            $table->unsignedBigInteger('bed_id')->nullable();
            $table->dateTime('date')->nullable();
            $table->string('time')->nullable();
            $table->unsignedBigInteger('admitted_by')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('discharges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admission_id');
            $table->unsignedBigInteger('discharge_by')->nullable();
            $table->dateTime('date')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
        Schema::create('patient_admissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->dateTime('admission_date')->nullable();
            $table->dateTime('discharge_date')->nullable();
            $table->string('department')->nullable();
            $table->string('bed_number')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('admitted_by')->nullable();
            $table->unsignedBigInteger('discharged_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('beds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ward_id')->nullable();
            $table->string('bed_no')->nullable();
        });
        Schema::create('wards', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
        });
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
        });
        Schema::create('service_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_visit_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('investigations', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
        });
        Schema::create('investigation_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_visit_id');
            $table->unsignedBigInteger('investigation_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('visit_activities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_visit_id');
            $table->string('activity')->nullable();
            $table->timestamps();
        });
        Schema::create('vital_signs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_visit_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
