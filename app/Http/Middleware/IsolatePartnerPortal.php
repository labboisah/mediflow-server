<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsolatePartnerPortal
{
    public function handle(Request $r, Closure $next)
    {
        $name = $r->route()?->getName() ?? '';
        if (Auth::guard('web')->user()?->is_partner_only) {
            abort_unless(str_starts_with($name, 'partner.') || $name === 'logout', 403, 'This account is restricted to the Partner Portal.');
        }
        $response = $next($r);
        if (str_starts_with($name, 'partner.') || str_starts_with($name, 'network.')) {
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->set('Referrer-Policy', 'no-referrer');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }
}
