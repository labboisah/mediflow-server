<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use App\Services\PackageFinanceAccess;
use Closure;
use Illuminate\Http\Request;

class EnsureInstallationModules
{
    public function handle(Request $request, Closure $next)
    {
        $finance = app(PackageFinanceAccess::class);
        if ($finance->matches($request->route()?->getName())) {
            abort_unless($finance->allows($request->user()), 403, 'Only the package administrator can manage expenses and revenue.');
        }
        // Resolve the original route again on Livewire updates through persistent middleware.
        $license = app(LicenseService::class);
        if (! $license->routeEnabled($request->route()?->getName(), $request->path())) {
            $message = 'This feature is not included in this installation.';
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'missing_capability', 'message' => $message],
                ], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
