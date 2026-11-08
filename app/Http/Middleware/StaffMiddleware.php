<?php

namespace App\Http\Middleware;

use Closure;

class StaffMiddleware
{
    public function handle($request, Closure $next)
    {
        if (auth()->user()->role !== 'staff') {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
