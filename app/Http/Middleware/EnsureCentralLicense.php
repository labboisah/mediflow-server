<?php

namespace App\Http\Middleware;

use App\Services\InstallationOnboarding;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use KernelBridge\LicensingClient\Exceptions\KernelBridgeApiException;
use KernelBridge\LicensingClient\Exceptions\KernelBridgeUnavailableException;
use KernelBridge\LicensingClient\Services\LicenseCacheService;
use KernelBridge\LicensingClient\Services\LicenseVerificationService;

class EnsureCentralLicense
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('central_licensing.enabled')
            || $request->is('license', 'license/*', 'login', 'logout', 'forgot-password', 'reset-password/*', 'up', 'installation/setup', 'branding/*')) {
            return $next($request);
        }
        $cache = app(LicenseCacheService::class);
        $verificationFailure = null;
        try {
            app(LicenseVerificationService::class)->verify();
        } catch (KernelBridgeUnavailableException $exception) {
            // The cache service only allows signed, device-bound, unexpired offline grace.
            $verificationFailure = 'temporary_licensing_service_outage';
        } catch (KernelBridgeApiException $exception) {
            $verificationFailure = $exception->errorCode;
        }
        if (! $cache->hasUsableLicense()) {
            $reason = $cache->activationRequirementReason() === 'offline_grace_expired'
                ? 'stale_snapshot'
                : ($verificationFailure === 'temporary_licensing_service_outage' ? $verificationFailure : 'license_unavailable');
            Log::warning('mediflow.licensing.request_denied', [
                'reason' => $reason,
                'verification_failure' => $verificationFailure,
            ]);
            $message = match ($reason) {
                'stale_snapshot' => 'The cached license snapshot is stale. Reconnect and refresh the installation license.',
                'temporary_licensing_service_outage' => 'The licensing service is temporarily unavailable and no fresh cached license can be used.',
                default => 'Activate this computer through the installation administrator.',
            };
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => $reason, 'message' => $message],
                ], 402);
            }

            return redirect()->route('kernelbridge.license.show');
        }

        if (! app(InstallationOnboarding::class)->completed()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(409, 'Complete installation setup before using the system.');
            }

            return redirect()->route('installation.setup');
        }

        return $next($request);
    }
}
