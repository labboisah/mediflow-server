<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSyncToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $configuredToken = config('sync.token');

        if (!$configuredToken) {
            return response()->json([
                'success' => false,
                'message' => 'Sync not configured',
            ], 500);
        }

        if (!$token || $token !== $configuredToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
