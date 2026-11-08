<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
                $route->middleware(\App\Http\Middleware\EnsureCentralLicense::class);
                $route->middleware(\App\Http\Middleware\EnsureInstallationModules::class);
                $route->middleware(\App\Http\Middleware\ProtectSpecialistRecords::class);
                $route->middleware(\App\Http\Middleware\IsolatePartnerPortal::class);
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn ($request) => $request->routeIs('partner.*') ? route('partner.login') : route('login'));
        $middleware->web(append: [\App\Http\Middleware\ApplySystemBranding::class]);
        $middleware->alias([
            'installation.admin' => \App\Http\Middleware\EnsureInstallationAdmin::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'access' => \App\Http\Middleware\CheckRoleOrPermission::class,
            'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
            'auth.sync.token' => \App\Http\Middleware\AuthenticateSyncToken::class,
            'pharmacy.manager' => \App\Http\Middleware\EnsurePharmacyManager::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
