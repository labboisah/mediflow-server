<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncInitialize extends Command
{
    protected $signature = 'sync:initialize {--model= : Initialize specific model class}';
    protected $description = 'Initialize sync_uuid for existing records';

    public function handle()
    {
        $modelClass = $this->option('model');

        if ($modelClass) {
            $this->initializeModel($modelClass);
        } else {
            $this->initializeAllModels();
        }

        return 0;
    }

    private function initializeModel(string $modelClass)
    {
        if (!class_exists($modelClass)) {
            $this->error("Model class not found: {$modelClass}");
            return;
        }

        $model = new $modelClass();

        if (!method_exists($model, 'ensureSyncUuid')) {
            $this->error("Model {$modelClass} does not use Syncable trait");
            return;
        }

        $records = $modelClass::whereNull('sync_uuid')->get();

        if ($records->isEmpty()) {
            $this->info("All records already have sync_uuid for {$modelClass}");
            return;
        }

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        foreach ($records as $record) {
            $record->update(['sync_uuid' => (string) Str::uuid()]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Initialized {$records->count()} records with sync_uuid for {$modelClass}");
    }

    private function initializeAllModels()
    {
        $syncableModels = config('sync.syncable_models', []);

        foreach ($syncableModels as $modelClass) {
            if (class_exists($modelClass)) {
                $this->info("Initializing {$modelClass}...");
                $this->initializeModel($modelClass);
            }
        }

        $this->info("\nSync initialization complete!");
    }
}
