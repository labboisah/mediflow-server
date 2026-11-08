<?php

namespace App\Http\Middleware;

use App\Services\InstallationCatalog;
use App\Services\ProductEntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $entitlements = app(ProductEntitlementService::class);
        $allowedRoles = $entitlements->strictEnforcementEnabled()
            ? array_values(array_filter(
                $roles,
                fn (string $role) => app(InstallationCatalog::class)->roleAllowed($role),
            ))
            : $roles;

        if ($request->user()->hasAnyRole($allowedRoles)) {
            return $next($request);
        }

        return response()->view('errors.403', [], 403);
    }
}
