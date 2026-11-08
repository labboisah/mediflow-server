<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Services\PartnerNetwork\Workflow;
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Kernel::class)->bootstrap();
$database=$argv[1]??'';$token=$argv[2]??'';
$connection=config('database.default');
if(!preg_match('/^mediflow_specialist_check_[0-9]{14}_[a-f0-9]{6}$/',$database) || $database===config("database.connections.$connection.database")){fwrite(STDERR,'Only a disposable rehearsal database is allowed.');exit(2);}
config(["database.connections.$connection.database"=>$database,'mediflow_modules.default_plan'=>'specialist','sync.behavior.auto_sync_enabled'=>false,'audit.queue'=>false]);DB::purge($connection);
try {Model::withoutEvents(fn()=>app(Workflow::class)->redeem($token,['name'=>'Concurrent member','email'=>'concurrent@example.invalid','password'=>'synthetic-long-password']));echo 'REDEEMED';}
catch(\Illuminate\Validation\ValidationException $e){echo 'REJECTED';}
catch(Throwable $e){fwrite(STDERR,get_class($e).': '.$e->getMessage());exit(2);}
