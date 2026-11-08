<?php

namespace App\Livewire\Admin;

use App\Jobs\SyncRecordJob;
use App\Models\SyncOperation;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Schema;

#[Layout('layouts.modern')]
class SyncDashboard extends Component
{
    public $models = [];

    public function mount()
    {
        $this->loadModels();
    }

    public function loadModels()
    {
        $this->models = collect(config('sync.syncable_models', []))
            ->filter(fn ($class) => $this->isSyncableModel($class))
            ->map(function ($class) {
                $instance = new $class();
                $table = $instance->getTable();

                return [
                    'name' => class_basename($class),
                    'table' => $table,
                    'class' => $class,
                    'pending' => $class::where('sync_status', 'pending')->count(),
                    'failed' => $class::where('sync_status', 'failed')->count(),
                    'queued' => SyncOperation::where('model_type', $class)
                        ->where('status', 'pending')
                        ->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    public function sync($class)
    {
        if (!$this->isSyncableModel($class)) {
            session()->flash('error', 'Invalid sync model selected.');
            return;
        }

        $queued = 0;

        $class::whereIn('sync_status', ['pending', 'failed'])
            ->orderBy('id')
            ->chunkById(100, function ($records) use (&$queued) {
                foreach ($records as $record) {
                    if (app(\App\Services\SpecialistSyncBoundary::class)->localOnly($record)) continue;
                    $syncOperation = $record->createSyncOperation(
                        $record->wasRecentlyCreated ? 'create' : 'update'
                    );

                    SyncRecordJob::dispatch($syncOperation)
                        ->onQueue(config('sync.queue.name', 'sync'))
                        ->onConnection(config('sync.queue.connection', 'database'));

                    $queued++;
                }
            });

        SyncOperation::where('model_type', $class)
            ->where('status', 'failed')
            ->chunkById(100, function ($operations) use (&$queued) {
                foreach ($operations as $operation) {
                    $operation->update([
                        'status' => 'pending',
                        'attempts' => 0,
                        'error_message' => null,
                    ]);

                    SyncRecordJob::dispatch($operation)
                        ->onQueue(config('sync.queue.name', 'sync'))
                        ->onConnection(config('sync.queue.connection', 'database'));

                    $queued++;
                }
            });

        $this->loadModels();

        session()->flash('success', "{$queued} sync job(s) queued.");
    }

    private function isSyncableModel(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        $instance = new $class();

        return in_array(\App\Models\Traits\Syncable::class, class_uses_recursive($class), true)
            && Schema::hasTable($instance->getTable())
            && Schema::hasColumn($instance->getTable(), 'sync_status');
    }

    public function render()
    {
        return view('components.admin.sync', [
            'pageTitle' => 'Sync Dashboard',
            'pageSubtitle' => 'Review pending, failed, and queued sync records by model.',
        ]);
    }
}
