<?php

namespace App\Http\Controllers;

use App\Services\InstallationOnboarding;
use App\Services\LicensingConnection;
use Illuminate\Http\Request;
use KernelBridge\LicensingClient\Exceptions\KernelBridgeApiException;
use KernelBridge\LicensingClient\Exceptions\KernelBridgeUnavailableException;
use KernelBridge\LicensingClient\Services\DeploymentProfileException;
use KernelBridge\LicensingClient\Services\LicenseActivationService;
use KernelBridge\LicensingClient\Services\LicenseCacheService;
use KernelBridge\LicensingClient\Services\LicenseVerificationService;
use KernelBridge\LicensingClient\Services\LicensingPolicyService;

class InstallationActivationController extends Controller
{
    public function show(
        Request $request,
        LicenseCacheService $cache,
        InstallationOnboarding $onboarding,
        LicensingPolicyService $policyService,
    ) {
        if ($cache->hasUsableLicense() && ($onboarding->completed() || $onboarding->hasAdministrator() || $onboarding->hasGrant($request))) {
            return redirect($onboarding->completed() ? '/' : route('installation.setup'));
        }

        $licensingPolicy = null;
        $licensingPolicyError = null;
        if (! $cache->hasUsableLicense()) {
            try {
                $licensingPolicy = $policyService->current();
            } catch (KernelBridgeApiException|KernelBridgeUnavailableException|DeploymentProfileException $exception) {
                $licensingPolicyError = $exception->getMessage();
            }
        }

        return view('kernelbridge-licensing::activation', [
            'state' => $cache->state(),
            'hasUsableLicense' => $cache->hasUsableLicense(),
            'activationReason' => $cache->activationRequirementReason(),
            'activationMessage' => $cache->activationRequirementMessage(),
            'licenseStatus' => $cache->status(),
            'productCode' => config('kernelbridge-licensing.product_code'),
            'legacyActivationEnabled' => (bool) config('kernelbridge-licensing.legacy_activation_enabled', false),
            'licensingPolicy' => $licensingPolicy,
            'licensingPolicyError' => $licensingPolicyError,
            'platformActivationUrl' => $this->platformActivationUrl(),
            'needsLogin' => $onboarding->hasAdministrator() && ! $request->user()?->is_installation_admin,
        ]);
    }

    private function platformActivationUrl(): ?string
    {
        $url = config('kernelbridge-licensing.portal_urls.platform_activation');
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false
            || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return null;
        }

        return $url;
    }

    public function activate(Request $request, LicenseActivationService $activation, LicenseCacheService $cache, InstallationOnboarding $onboarding)
    {
        if ($onboarding->hasAdministrator()) {
            if (! $request->user()) {
                return redirect()->guest(route('login'));
            }
            abort_unless($request->user()->is_installation_admin, 403);
        }
        $data = $request->validate([
            'activation_code' => ['required_without:license_key', 'string', 'max:255'],
            'license_key' => ['required_without:activation_code', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'server_url' => ['sometimes', 'required', 'url:http,https', 'max:2048'],
        ]);
        $connection = app(LicensingConnection::class);
        $serverUrl = $connection->select($data['server_url'] ?? null);
        try {
            if (filled($data['activation_code'] ?? null)) {
                $activation->activateWithCode($data['activation_code'], $data['device_name'] ?? config('app.name'));
            } else {
                $activation->activate($data['license_key'], $data['device_name'] ?? config('app.name'));
            }
            if (! $cache->hasUsableLicense()) {
                return back()->withErrors([
                    (filled($data['activation_code'] ?? null) ? 'activation_code' : 'license_key') => 'The subscription is not active. Settle its invoice and try again.',
                ]);
            }
        } catch (KernelBridgeUnavailableException $exception) {
            return back()->withInput($request->only('server_url', 'device_name'))->withErrors([
                (filled($data['activation_code'] ?? null) ? 'activation_code' : 'license_key') => 'The licensing service at '.$serverUrl.' could not complete the request. Check the address and that KernelBridge is running. The server may also be returning an internal error.',
            ]);
        } catch (KernelBridgeApiException $exception) {
            return back()->withErrors([
                (filled($data['activation_code'] ?? null) ? 'activation_code' : 'license_key') => $exception->getMessage(),
            ]);
        } catch (\LogicException $exception) {
            return back()->withErrors([
                (filled($data['activation_code'] ?? null) ? 'activation_code' : 'license_key') => $exception->getMessage() ?: 'The installation licensing connection is not configured. Ask the deployment administrator to verify the KernelBridge API URL and trusted entitlement signing keys.',
            ]);
        }
        $connection->save($serverUrl);
        $onboarding->grant($request);

        return redirect($onboarding->completed() ? '/' : route('installation.setup'));
    }

    public function refresh(Request $request, LicenseVerificationService $verification)
    {
        abort_unless($request->user()?->is_installation_admin, 403);
        try {
            $state = $verification->verify(true);
        } catch (KernelBridgeApiException|KernelBridgeUnavailableException $exception) {
            return back()->withErrors(['license_key' => $exception->getMessage()]);
        }

        return redirect()->route('admin.installation')->with('kernelbridge_license_status', 'Subscription checked. Last verified: '.$state->last_successful_verification_at?->toDateTimeString().'. Cached access is retained during a temporary connection outage.');
    }

    public function deactivate(Request $request, LicenseVerificationService $verification)
    {
        abort_unless($request->user()?->is_installation_admin, 403);
        $verification->deactivate();

        return redirect()->route('kernelbridge.license.show');
    }
}
