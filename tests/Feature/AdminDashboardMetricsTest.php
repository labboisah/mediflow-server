<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardMetricsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'central_licensing.enabled' => false,
        ]);

        $this->createDashboardTables();
        $this->partialMock(LicenseService::class, function ($mock): void {
            $mock->shouldReceive('currentLicense')->andReturn(null);
        });
    }

    public function test_administrator_dashboard_aggregates_metrics_without_changing_values(): void
    {
        $user = $this->seedDashboardData('administrator');
        Auth::login($user);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $data = (new Dashboard())->render()->getData();
        $queryCount = count(DB::getQueryLog());

        $this->assertSame(2, $data['accessMetrics']['users']);
        $this->assertSame(1, $data['accessMetrics']['administrators']);
        $this->assertSame(2, $data['hospitalMetrics']['patients']);
        $this->assertSame(1, $data['hospitalMetrics']['walkin_patients']);
        $this->assertSame(3, $data['hospitalMetrics']['visits']);
        $this->assertSame(1, $data['hospitalMetrics']['active_visits']);
        $this->assertSame(2, $data['hospitalMetrics']['today_visits']);
        $this->assertSame(3, $data['financeMetrics']['bills']);
        $this->assertSame(2, $data['financeMetrics']['today_bills']);
        $this->assertSame(2, $data['financeMetrics']['open_bills']);
        $this->assertSame(150.0, $data['financeMetrics']['total_billed_today']);
        $this->assertSame(3, $data['financeMetrics']['payments']);
        $this->assertSame(2, $data['financeMetrics']['payments_today']);
        $this->assertSame(75.0, $data['financeMetrics']['collected_today']);
        $this->assertSame(4, $data['setupMetrics']['beds']);
        $this->assertSame(1, $data['setupMetrics']['occupied_beds']);
        $this->assertSame(1, $data['syncMetrics']['pending']);
        $this->assertSame(1, $data['syncMetrics']['failed']);
        $this->assertSame(1, $data['syncMetrics']['synced_today']);
        $this->assertSame('Dashboard User', $data['recentActivities']->first()->actor->name);
        $this->assertLessThanOrEqual(10, $queryCount);
    }

    public function test_medical_director_dashboard_does_not_load_admin_only_metrics(): void
    {
        $user = $this->seedDashboardData('medical_director');
        Auth::login($user);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $data = (new Dashboard())->render()->getData();
        $queries = collect(DB::getQueryLog())->pluck('query')->map(fn ($query) => strtolower($query));

        $this->assertFalse($data['canViewTechnicalRecords']);
        $this->assertArrayNotHasKey('accessMetrics', $data);
        $this->assertArrayNotHasKey('financeMetrics', $data);
        $this->assertArrayNotHasKey('syncMetrics', $data);
        $this->assertFalse($queries->contains(fn ($query) => str_contains($query, 'from "bills"')));
        $this->assertFalse($queries->contains(fn ($query) => str_contains($query, 'from "payments"')));
    }

    private function seedDashboardData(string $roleName): User
    {
        $now = now();
        $today = today()->format('Y-m-d');
        $yesterday = today()->subDay()->format('Y-m-d');

        DB::table('roles')->insert([
            ['id' => 1, 'name' => $roleName],
            ['id' => 2, 'name' => 'doctor'],
        ]);
        DB::table('permissions')->insert([
            ['id' => 1, 'name' => 'patient.read'],
            ['id' => 2, 'name' => 'patient.write'],
        ]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Dashboard User', 'email' => 'dashboard@example.test', 'password' => 'test', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Second User', 'email' => 'second@example.test', 'password' => 'test', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('role_user')->insert([
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 2, 'role_id' => 2],
        ]);
        DB::table('temporary_permissions')->insert([
            ['id' => 1, 'is_active' => true, 'expires_at' => $now->copy()->addDay()],
        ]);
        DB::table('patients')->insert([['id' => 1], ['id' => 2]]);
        DB::table('walkin_patients')->insert([['id' => 1]]);
        DB::table('patient_visits')->insert([
            ['id' => 1, 'status' => 'Active', 'visit_date' => $today . ' 08:00:00'],
            ['id' => 2, 'status' => 'Closed', 'visit_date' => $today . ' 09:00:00'],
            ['id' => 3, 'status' => 'Closed', 'visit_date' => $yesterday . ' 09:00:00'],
        ]);
        DB::table('bills')->insert([
            ['id' => 1, 'status' => 'pending', 'amount' => 100, 'issued_date' => $today . ' 08:00:00'],
            ['id' => 2, 'status' => 'partial', 'amount' => 50, 'issued_date' => $today . ' 09:00:00'],
            ['id' => 3, 'status' => 'paid', 'amount' => 25, 'issued_date' => $yesterday . ' 09:00:00'],
        ]);
        DB::table('payments')->insert([
            ['id' => 1, 'status' => 'completed', 'amount' => 75, 'payment_date' => $today . ' 08:00:00'],
            ['id' => 2, 'status' => 'pending', 'amount' => 40, 'payment_date' => $today . ' 09:00:00'],
            ['id' => 3, 'status' => 'completed', 'amount' => 10, 'payment_date' => $yesterday . ' 09:00:00'],
        ]);
        DB::table('departments')->insert([['id' => 1], ['id' => 2]]);
        DB::table('services')->insert([['id' => 1], ['id' => 2]]);
        DB::table('investigations')->insert([['id' => 1], ['id' => 2]]);
        DB::table('wards')->insert([['id' => 1]]);
        DB::table('beds')->insert([
            ['id' => 1, 'status' => 'occupied'],
            ['id' => 2, 'status' => 'vacant'],
            ['id' => 3, 'status' => 'vacant'],
            ['id' => 4, 'status' => 'vacant'],
        ]);
        DB::table('sync_operations')->insert([
            ['id' => 1, 'status' => 'pending', 'synced_at' => null, 'updated_at' => $now],
            ['id' => 2, 'status' => 'failed', 'synced_at' => null, 'updated_at' => $now->copy()->subMinute()],
            ['id' => 3, 'status' => 'synced', 'synced_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'status' => 'synced', 'synced_at' => $now->copy()->subDay(), 'updated_at' => $now->copy()->subDay()],
        ]);
        DB::table('audit_logs')->insert([
            'id' => 1,
            'actor_id' => 1,
            'action' => 'patient.updated',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return User::findOrFail(1);
    }

    private function createDashboardTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('role_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('temporary_permissions', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_active');
            $table->dateTime('expires_at')->nullable();
        });
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('walkin_patients', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('patient_visits', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->dateTime('visit_date');
            $table->softDeletes();
        });
        Schema::create('bills', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->decimal('amount', 12, 2);
            $table->dateTime('issued_date');
            $table->softDeletes();
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->decimal('amount', 12, 2);
            $table->dateTime('payment_date');
            $table->softDeletes();
        });
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('investigations', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('wards', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('beds', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
        });
        Schema::create('sync_operations', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->timestamps();
        });
    }
}
