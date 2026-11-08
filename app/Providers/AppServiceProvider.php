<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Route;
use App\Observers\AuditModelObserver;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\TemporaryPermission;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $slowQueryThreshold = (int) config('performance.database_query_threshold_ms', 0);

        if ($slowQueryThreshold > 0) {
            DB::listen(static function (QueryExecuted $query) use ($slowQueryThreshold): void {
                if ($query->time < $slowQueryThreshold) {
                    return;
                }

                Log::warning('Database query exceeded the configured duration threshold.', [
                    'connection' => $query->connectionName,
                    'duration_ms' => $query->time,
                ]);
            });
        }

        \Livewire\Livewire::addPersistentMiddleware([
            \App\Http\Middleware\CheckRole::class,
            \App\Http\Middleware\EnsurePharmacyManager::class,
            \App\Http\Middleware\EnsureInstallationModules::class,
            \App\Http\Middleware\ProtectSpecialistRecords::class,
            \App\Http\Middleware\IsolatePartnerPortal::class,
            \App\Http\Middleware\EnsureInstallationAdmin::class,
        ]);

        // Register audit observer for core models that use the Auditable trait
        User::observe(AuditModelObserver::class);
        Role::observe(AuditModelObserver::class);
        Permission::observe(AuditModelObserver::class);
        TemporaryPermission::observe(AuditModelObserver::class);
    }
}
