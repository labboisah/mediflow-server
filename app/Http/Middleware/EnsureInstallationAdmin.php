<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureInstallationAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->is_installation_admin, 403);

        return $next($request);
    }
}
