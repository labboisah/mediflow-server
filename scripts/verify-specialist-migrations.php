<?php

use App\Models\Bill;
use App\Models\Department;
use App\Models\FileType;
use App\Models\ModuleUserAccess;
use App\Models\Patient;
use App\Models\PatientDemographic;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\SpecialistService;
use App\Models\Specialty;
use App\Models\User;
use App\Services\SpecialistAccess;
use App\Services\SpecialistWorkflow;
use Database\Seeders\SpecialistModuleSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;


// Creates and removes a uniquely named, schema-only test database. No application data is copied.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::connection()->getDriverName() !== 'mysql') {
    throw new RuntimeException('MySQL connection required.');
}
$connection = config('database.default');
$original = config("database.connections.$connection.database");
$test = 'mediflow_specialist_check_'.date('YmdHis').'_'.bin2hex(random_bytes(3));
if (! preg_match('/^mediflow_specialist_check_[0-9]{14}_[a-f0-9]{6}$/', $test) || $test === $original) {
    throw new RuntimeException('Unsafe test database name.');
}
$pdo = DB::connection()->getPdo();
$tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
$ddl = [];
foreach ($tables as $row) {
    $name = str_replace('`', '``', $row[0]);
    $ddl[] = $pdo->query("SHOW CREATE TABLE `$name`")->fetch(PDO::FETCH_NUM)[1];
}
$pdo->exec("CREATE DATABASE `$test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
try {
    config(["database.connections.$connection.database" => $test, 'sync.behavior.auto_sync_enabled' => false, 'audit.queue' => false]);
    DB::purge($connection);
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    foreach ($ddl as $sql) {
        DB::statement($sql);
    }
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    $migrations = [
        require __DIR__.'/../database/migrations/2026_09_29_000001_add_selected_packages_to_client_licenses.php',
        require __DIR__.'/../database/migrations/2026_09_29_000002_create_specialist_workspace.php',
        require __DIR__.'/../database/migrations/2026_09_30_000001_create_partner_network.php',
    ];
    if (Schema::hasTable('partnerships')) { $migrations[2]->down(); }
    if (Schema::hasTable('specialist_consultations')) {
        $migrations[1]->down();
    }
    if (Schema::hasColumn('client_licenses', 'selected_packages')) {
        $migrations[0]->down();
    }
    foreach ($migrations as $migration) {
        $migration->up();
    }
    foreach (array_reverse($migrations) as $migration) {
        $migration->down();
    }
    foreach ($migrations as $migration) {
        $migration->up();
    }
    if (! Schema::hasTable('specialist_consultations') || ! Schema::hasColumn('client_licenses', 'selected_packages')) {
        throw new RuntimeException('Schema check failed.');
    }
    config(['mediflow_modules.default_plan' => 'specialist']);
    Model::withoutEvents(fn () => (new SpecialistModuleSeeder)->run());
    $department = Department::create(['name' => 'Specialist']);
    $user = User::withoutEvents(function () use ($department) {
        return User::create(['name' => 'Rehearsal Clinician', 'email' => 'specialist-rehearsal@example.invalid',
            'password' => bin2hex(random_bytes(20)), 'department_id' => $department->id]);
    });
    $user->assignRole('specialist');
    $user->assignRole('specialist_cashier');
    auth()->setUser($user);
    ModuleUserAccess::create(['user_id' => $user->id, 'license_module' => 'specialist', 'is_active' => true, 'granted_by' => $user->id]);
    $specialty = Specialty::create(['name' => 'Rehearsal Specialty', 'is_active' => true]);
    $profile = SpecialistProfile::create(['user_id' => $user->id, 'title' => 'Specialist', 'timezone' => 'UTC', 'location' => 'Test room', 'physical' => true, 'online' => true,
        'availability' => ['days' => [1, 2, 3, 4, 5, 6, 7], 'from' => '00:00', 'to' => '23:59']]);
    $profile->specialties()->attach($specialty->id);
    $file = FileType::create(['name' => 'Rehearsal file', 'price' => 0]);
    $patient = Patient::create(['hospital_number' => 'SYNTHETIC-REHEARSAL', 'file_type_id' => $file->id, 'registration_date' => now()]);
    PatientDemographic::create(['patient_id' => $patient->id, 'first_name' => 'Synthetic', 'last_name' => 'Patient', 'phone_number' => 'test-only']);
    app(SpecialistAccess::class)->grant($patient->id, $user->id);
    $service = Service::create(['name' => 'Rehearsal consultation', 'code' => 'TEST-SP', 'category' => 'Specialist', 'price' => 500, 'department_id' => $department->id, 'is_active' => true]);
    SpecialistService::create(['service_id' => $service->id, 'specialty_id' => $specialty->id, 'duration_minutes' => 30]);
    $method = PaymentMethod::create(['name' => 'Test cash', 'is_active' => true]);
    $workflow = app(SpecialistWorkflow::class);
    $booking = ['patient_id' => $patient->id, 'profile_id' => $profile->id, 'service_id' => $service->id, 'starts_at' => now('UTC')->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'),
        'mode' => 'physical', 'reason' => 'Synthetic verification', 'token' => (string) Str::uuid()];
    $c = $workflow->book($booking);
    $again = $workflow->book($booking);
    if ($c->id !== $again->id) {
        throw new RuntimeException('Duplicate booking.');
    }
    $workflow->start($c->id);
    $workflow->start($c->id);
    $workflow->saveClinical($c->id, ['version' => 1, 'complaint' => 'Synthetic complaint', 'diagnosis' => 'Synthetic finding', 'plan' => 'Synthetic plan', 'complete' => 1]);
    $bill = $workflow->bill($c->id);
    $workflow->bill($c->id);
    $payment = ['token' => (string) Str::uuid(), 'amount' => '500.00', 'payment_method_id' => $method->id];
    $workflow->pay($c->id, $payment);
    $workflow->pay($c->id, $payment);
    if (Bill::count() !== 1 || Payment::count() !== 1 || $bill->fresh()->status !== 'paid') {
        throw new RuntimeException('Billing smoke check failed.');
    }

    Model::withoutEvents(fn () => (new \Database\Seeders\PartnerNetworkSeeder)->run());
    $user->assignRole('network_coordinator');
    auth()->setUser($user->fresh());
    ModuleUserAccess::create(['user_id'=>$user->id,'license_module'=>'partner_network','is_active'=>true,'granted_by'=>$user->id]);
    $network=app(\App\Services\PartnerNetwork\Workflow::class);
    $partnership=$network->register(['name'=>'Synthetic Partner','type_id'=>\App\Models\PartnerType::first()->id,'purpose'=>'Schema rehearsal only','contact'=>'test@example.invalid']);
    $invite=$network->invite($partnership->id,['email'=>'partner@example.invalid','role'=>'administrator']);
    $external=Model::withoutEvents(fn()=>$network->redeem($invite,['name'=>'Synthetic Partner Admin','email'=>'partner@example.invalid','password'=>'synthetic-long-password']));
    \Illuminate\Support\Facades\Auth::guard('partner')->setUser($external);
    $network->submit($partnership->id,['contact_person'=>'Synthetic contact','phone'=>'test','email'=>'partner@example.invalid','address'=>'Synthetic location']);
    if($partnership->fresh()->status!=='registration_submitted' || !$external->is_partner_only)throw new RuntimeException('Partner onboarding rehearsal failed.');
    echo "MySQL Partner Network rehearsal passed: registration, invitation, isolated account and onboarding submission.\n";

    $concurrentInvite=$network->invite($partnership->id,['email'=>'concurrent@example.invalid','role'=>'intake']);
    $inviteWorkers=[];
    for($i=0;$i<2;$i++){
        $pipes=[];$process=proc_open([PHP_BINARY,__DIR__.'/partner-invitation-worker.php',$test,$concurrentInvite],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,__DIR__.'/..');
        if(!is_resource($process))throw new RuntimeException('Cannot start invitation worker.');
        fclose($pipes[0]);$inviteWorkers[]=[$process,$pipes];
    }
    $redemptions=[];
    foreach($inviteWorkers as [$process,$pipes]){
        $redemptions[]=trim(stream_get_contents($pipes[1]));$error=stream_get_contents($pipes[2]);
        fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
        if($exit!==0)throw new RuntimeException('Invitation worker failed: '.$error);
    }
    sort($redemptions);
    if($redemptions!==['REDEEMED','REJECTED'])throw new RuntimeException('Concurrent invitation redemption was not serialized.');
    echo "MySQL invitation race passed: exactly one redemption accepted.\n";

    $workers=[];
    for ($index=0;$index<2;$index++) {
        $candidate=array_replace($booking,['starts_at'=>now('UTC')->addDay()->setTime(12,0)->format('Y-m-d\TH:i'),'token'=>(string) \Illuminate\Support\Str::uuid()]);
        $pipes=[];
        $process=proc_open([PHP_BINARY,__DIR__.'/specialist-booking-worker.php',$test,(string)$user->id,json_encode($candidate)],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,__DIR__.'/..');
        if(!is_resource($process))throw new RuntimeException('Cannot start concurrency worker.');
        fclose($pipes[0]);$workers[]=[$process,$pipes];
    }
    $outcomes=[];
    foreach($workers as [$process,$pipes]) {
        $outcomes[]=trim(stream_get_contents($pipes[1]));$error=stream_get_contents($pipes[2]);
        fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
        if($exit!==0)throw new RuntimeException('Booking worker failed: '.$error);
    }
    sort($outcomes);
    if($outcomes!==['BOOKED','REJECTED'])throw new RuntimeException('Concurrent bookings were not serialized: '.implode(',',$outcomes));
    echo "MySQL concurrent slot test passed: exactly one competing booking accepted.\n";
    echo "MySQL synthetic journey passed: booking retry, encounter start, signed consultation, bill and payment retries.\n";
    echo 'MySQL schema-only rehearsal passed: '.count($ddl)." existing tables; all three additive migrations up/down/up.\n";
} catch (Throwable $e) {
    $failure = $e;
} finally {
    DB::disconnect($connection);
    config(["database.connections.$connection.database" => $original]);
    DB::purge($connection);
    // Only the uniquely generated database owned by this invocation is removed.
    $pdo->exec("DROP DATABASE `$test`");
    echo "Temporary rehearsal database removed.\n";
}

if (isset($failure)) {
    fwrite(STDERR, get_class($failure).': '.$failure->getMessage().' at '.$failure->getFile().':'.$failure->getLine().PHP_EOL);
    exit(1);
}
