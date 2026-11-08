<?php

namespace App\Http\Middleware;

use App\Services\InstallationCatalog;
use App\Services\ProductEntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleOrPermission
{
    public function handle(Request $request, Closure $next, ...$abilities): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $entitlements = app(ProductEntitlementService::class);
        $strict = $entitlements->strictEnforcementEnabled();
        foreach ($abilities as $ability) {
            if (str_starts_with($ability, 'role:')) {
                $role = substr($ability, 5);
                if ($user->hasRole($role)
                    && (! $strict || app(InstallationCatalog::class)->roleAllowed($role))) {
                    return $next($request);
                }
            }

            if (str_starts_with($ability, 'permission:')) {
                $permissionName = substr($ability, 11);
                if (! $strict && $user->hasPermission($permissionName)) {
                    return $next($request);
                }
                if ($strict && $user->getAllPermissions()->contains(
                    fn ($permission) => $permission->name === $permissionName
                        && $entitlements->permissionAllowed($permission),
                )) {
                    return $next($request);
                }
            }
        }

        return response()->view('errors.403', [], 403);
    }
}
