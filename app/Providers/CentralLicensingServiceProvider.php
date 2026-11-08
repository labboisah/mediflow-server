<?php

namespace App\Providers;

use App\Http\Middleware\EnsureCentralLicense;
use App\Services\CentralLicenseService;
use App\Services\LicenseService;
use App\Services\LicensingConnection;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CentralLicensingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (config('central_licensing.enabled')) {
            $this->app->bind(LicenseService::class, CentralLicenseService::class);
        }
    }

    public function boot(): void
    {
        if (! $this->app->runningUnitTests()) {
            app(LicensingConnection::class)->load();
        }
        Livewire::addPersistentMiddleware([EnsureCentralLicense::class]);
    }
}
