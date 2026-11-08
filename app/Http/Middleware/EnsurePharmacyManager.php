<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePharmacyManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $user = $request->user();
        $departmentName = strtolower((string) $user->department?->name);

        $isPharmacyHead = $user->hasRole('head_of_department')
            && str_contains($departmentName, 'pharmacy');

        if ($user->hasRole('pharmacist') || $isPharmacyHead) {
            return $next($request);
        }

        return response()->view('errors.403', [], 403);
    }
}
