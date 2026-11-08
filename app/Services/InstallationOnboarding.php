<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use KernelBridge\LicensingClient\Services\LicenseCacheService;

class InstallationOnboarding
{
    public function __construct(private readonly LicenseCacheService $cache) {}

    public function completed(): bool
    {
        return Schema::hasTable('installation_onboarding')
            && DB::table('installation_onboarding')->where('id', 1)->value('completed_at') !== null;
    }

    public function hasAdministrator(): bool
    {
        return User::withTrashed()->where('is_installation_admin', true)->exists();
    }

    public function grant(Request $request): void
    {
        $request->session()->regenerate();
        $state = $this->cache->state();
        $request->session()->put('installation_activation', [
            'proof' => hash('sha256', $state->license_identifier.'|'.$state->activation_identifier),
            'expires_at' => now()->addMinutes(30)->getTimestamp(),
        ]);
    }

    public function authorize(Request $request): void
    {
        abort_unless($this->cache->hasUsableLicense(), 402, 'A valid activation is required before installation setup.');
        if ($this->hasAdministrator()) {
            if (! $request->user()) {
                throw new AuthenticationException;
            }
            abort_unless($request->user()->is_installation_admin, 403);

            return;
        }
        abort_unless($this->hasGrant($request), 403, 'Activate in this browser before creating the first administrator.');
    }

    public function hasGrant(Request $request): bool
    {
        $grant = $request->session()->get('installation_activation', []);
        $state = $this->cache->state();
        $expected = hash('sha256', $state->license_identifier.'|'.$state->activation_identifier);

        return ($grant['expires_at'] ?? 0) > now()->getTimestamp()
            && hash_equals($expected, (string) ($grant['proof'] ?? ''));
    }
}
