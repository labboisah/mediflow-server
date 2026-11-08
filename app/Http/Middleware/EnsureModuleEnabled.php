<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        $license = app(LicenseService::class);

        if (! $user || ! $license->userHasModuleAccess($user, $module)) {
            return response()->view('errors.403', [], 403);
        }

        return $next($request);
    }
}
