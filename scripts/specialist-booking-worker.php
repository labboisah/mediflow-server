<?php
// Worker used only by the disposable MySQL rehearsal; never accepts an application database name.
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$database=$argv[1] ?? '';
if (!preg_match('/^mediflow_specialist_check_[0-9]{14}_[a-f0-9]{6}$/',$database)) exit(3);
$connection=config('database.default');
if($database===config("database.connections.$connection.database"))exit(3);
config(["database.connections.$connection.database"=>$database,'mediflow_modules.default_plan'=>'specialist','sync.behavior.auto_sync_enabled'=>false,'audit.queue'=>false]);
\Illuminate\Support\Facades\DB::purge($connection);
auth()->setUser(\App\Models\User::findOrFail((int)$argv[2]));
$data=json_decode($argv[3],true,512,JSON_THROW_ON_ERROR);
try { app(\App\Services\SpecialistWorkflow::class)->book($data); echo 'BOOKED'; }
catch(\Illuminate\Validation\ValidationException $e) { echo 'REJECTED'; }
catch(Throwable $e) { fwrite(STDERR,$e->getMessage()); exit(2); }
