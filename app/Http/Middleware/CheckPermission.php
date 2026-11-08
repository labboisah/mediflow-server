<?php

namespace App\Http\Middleware;

use App\Services\ProductEntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $user = $request->user();
        $entitlements = app(ProductEntitlementService::class);
        $hasPermission = $entitlements->strictEnforcementEnabled()
            ? $user->getAllPermissions()->contains(
                fn ($permission) => in_array($permission->name, $permissions, true)
                    && $entitlements->permissionAllowed($permission),
            )
            : $user->hasAnyPermission($permissions);

        if ($hasPermission) {
            return $next($request);
        }

        return response()->view('errors.403', [], 403);
    }
}
