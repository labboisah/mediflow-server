<?php

namespace App\Http\Middleware;

use App\Services\SystemBranding;
use Closure;
use Illuminate\Http\Request;

class ApplySystemBranding
{
    public function handle(Request $request, Closure $next)
    {
        app(SystemBranding::class)->apply();
        return $next($request);
    }
}
