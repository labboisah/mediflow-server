# Licensing Review And Improvement Plan

**Review date:** 2026-10-09  
**Scope:** MediFlow client application, the installed KernelBridge Laravel licensing client, and the documented integration. The KernelBridge subscription/billing server implementation is not in this repository, so billing rules, payment processing, and server-side downgrade policy could not be independently reviewed.

## Executive Summary

The current client has meaningful protections against casual license-cache copying and alteration: cached entitlements are HMAC-signed, bound to a machine fingerprint, checked against a fingerprint of sensitive licensing configuration, and limited by license expiry and a 72-hour offline grace period. Activation, verification, and deactivation are also connected to KernelBridge.

The most important weakness is the client-mode switch. Central enforcement and the central `LicenseService` binding depend on environment configuration. If a deployed client can change `APP_MODE` to `standalone` and leave or set `MEDIFLOW_CENTRAL_LICENSING` to false, the central middleware exits early and local plan logic is used instead. The local setup path is not a cryptographically signed license system. This makes the deployment toggle a practical bypass against environment alteration.

Renewal warnings are calculated by the package but are not surfaced in MediFlow's interface. Upgrades and downgrades are designed to happen centrally and can be applied by refreshing entitlements, but background propagation depends on the scheduler and queue worker being operational.

No PHP application delivered to a customer-controlled computer can be made tamper-proof against an administrator who can alter the application code, runtime, database, or machine identity. Improvements can close accidental and low-effort bypasses, strengthen central validation, and make renewal/reactivation dependable; they cannot provide remote hardware attestation on a fully customer-controlled host.

## Findings

### 1. Client environment changes can disable central enforcement

`config/central_licensing.php` enables central mode when `APP_MODE=client` or `MEDIFLOW_CENTRAL_LICENSING=true`. `CentralLicensingServiceProvider` binds the central implementation only while that setting is enabled, and `EnsureCentralLicense` returns without checking the license when it is disabled. The standalone license service can then use a local license or the default plan. The local installation setup saves plan, module, activation, and expiry settings without a signed remote entitlement.

This is the highest-priority gap for the stated environment-alteration threat. It is a configuration-level bypass, not a cryptographic failure in the signed cache.

Relevant files: [central_licensing.php](../config/central_licensing.php), [CentralLicensingServiceProvider.php](../app/Providers/CentralLicensingServiceProvider.php), [EnsureCentralLicense.php](../app/Http/Middleware/EnsureCentralLicense.php), [LicenseService.php](../app/Services/LicenseService.php), [InstallationSetup.php](../app/Livewire/Admin/InstallationSetup.php).

### 2. Local cache protection is good, with host-owner limits

The installed client verifies cache signature, device fingerprint, configuration fingerprint, subscription status, expiry, and offline grace. The package detects changes to the API URL, product code, API token, application key, and cache signing key. It uses the operating system's machine identity for host binding and fails closed if that identity cannot be read.

The cache signing key falls back to `APP_KEY`, which is stored on the client host. This is appropriate for detecting accidental or unsophisticated cache edits, but a host administrator who can read or replace application secrets can also alter code or configuration. Machine identity is not remote hardware attestation; cloned or administrator-controlled operating systems remain a residual risk.

Relevant files: [LicenseCacheService.php](../vendor/kernelbridge/licensing-client-laravel/src/Services/LicenseCacheService.php), [DeviceIdentity.php](../vendor/kernelbridge/licensing-client-laravel/src/Services/DeviceIdentity.php), [kernelbridge-licensing.php](../config/kernelbridge-licensing.php).

### 3. Renewal notice calculation is not user-facing

The package calculates notices at approximately three, two, and one month before expiry. Its own middleware can expose notice headers, but MediFlow uses `EnsureCentralLicense`, which does not call the notice helper. No consumer of those headers or renewal notices was found. The installation package panel displays package features but not subscription expiry or renewal status.

Access is eventually blocked on expiry, but an installation administrator may not get useful advance warning in MediFlow.

Relevant files: [LicenseCacheService.php](../vendor/kernelbridge/licensing-client-laravel/src/Services/LicenseCacheService.php), [LicenseMiddleware.php](../vendor/kernelbridge/licensing-client-laravel/src/Http/Middleware/LicenseMiddleware.php), [EnsureCentralLicense.php](../app/Http/Middleware/EnsureCentralLicense.php), [installation-setup.blade.php](../resources/views/components/admin/installation-setup.blade.php), [packages.blade.php](../resources/views/installation/packages.blade.php).

### 4. Upgrade and downgrade flow exists, but depends on entitlement refresh

MediFlow correctly directs package and invoice changes to KernelBridge. An installation administrator can manually refresh the subscription, and the package schedules forced verification every 15 minutes. A verified response replaces the local entitlement snapshot, so upgrades and downgrades can take effect without recreating the installation.

The background job requires a functioning Laravel scheduler and queue worker. If verification cannot reach KernelBridge, the last signed entitlements can remain usable during the configured offline grace window, up to 72 hours and never beyond license expiry. This is a deliberate availability trade-off, but it also means an entitlement downgrade or revocation may not be reflected immediately during an outage.

Relevant files: [InstallationActivationController.php](../app/Http/Controllers/InstallationActivationController.php), [installation-setup.blade.php](../resources/views/components/admin/installation-setup.blade.php), [KernelBridgeLicensingServiceProvider.php](../vendor/kernelbridge/licensing-client-laravel/src/KernelBridgeLicensingServiceProvider.php), [VerifyLicenseJob.php](../vendor/kernelbridge/licensing-client-laravel/src/Jobs/VerifyLicenseJob.php).

### 5. Licensing documentation describes two different deployment models

[CENTRALIZED_ACTIVATION_AND_LICENSING.md](CENTRALIZED_ACTIVATION_AND_LICENSING.md) says plan and license activation are central responsibilities. [INSTALLATION_SETUP.md](INSTALLATION_SETUP.md) documents a locally editable plan and validity configuration and describes it as superseding the central-only approach. The code supports both behaviors based on environment settings. This ambiguity makes it easy to ship a client with the wrong enforcement mode.

## Improvement Approaches

### A. KernelBridge package and central service

1. **Make subscription state transitions explicit.** Define and document the authoritative states (`active`, `past_due`/grace, `expired`, `revoked`, `deactivated`) and the permitted transitions. Specify whether a paid renewal reactivates an expired subscription automatically or requires a new activation.
2. **Make entitlement changes revisioned and auditable.** Return a monotonically increasing configuration revision and effective timestamp with each verification. Log package, feature, expiry, and status changes centrally, and reject stale entitlement revisions on clients.
3. **Provide a supported renewal and account-management destination.** Expose a central customer portal URL or renewal action in the API response so the client can take the administrator directly to invoices, renewals, and package changes.
4. **Document device transfer and reset policy.** Specify how an administrator replaces a failed host, handles reinstalls, and resolves an activation limit. Preserve device binding for ordinary deactivation unless an authorized transfer/reset is explicitly granted.
5. **Define outage/revocation behavior.** Document the offline grace period and how it interacts with expiry, non-payment, explicit revocation, and changed entitlements. Keep client and server policy consistent.

### B. Licensing client package

1. **Fail closed on licensing mode mismatch.** A deployment designated as a licensed client must not silently switch to standalone local licensing because an environment value changed. Treat client-vs-standalone mode as a deployment/build identity, validate it at boot, and require a deliberate server-side re-provisioning operation to change it. Do not treat a secret environment variable alone as a tamper-proof boundary.
2. **Prevent local fallback after central activation.** Once a central client state exists, a disabled or invalid central configuration should block protected features and present a diagnostic/reactivation path, not bind to the editable local `LicenseService`.
3. **Expose a stable status API.** Provide one structured status object with reason, active package(s), expiry, last successful verification, offline-grace deadline, revision, and renewal notice. Use the same status for middleware responses and UI to avoid generic “activate” messages hiding expiry or configuration errors.
4. **Harden key rotation and recovery.** Prefer a dedicated signing key over silently relying on `APP_KEY`; document rotation and backup recovery. Keep host binding and signed-cache checks, while documenting that privileged host administrators can ultimately alter a PHP application they control.
5. **Test configuration-tamper paths.** Add tests proving that changing client mode, licensing API configuration, `APP_KEY`, the signing key, device identity, cache payload, and entitlement revision cannot unlock a local/default plan in a client deployment.

### C. MediFlow client setup and operations

1. **Show subscription health on Installation Setup.** Display current status, primary and companion packages, enabled modules, expiry date, last verified time, offline-grace deadline, and last verification error. Keep “Refresh subscription” available to installation administrators.
2. **Add renewal reminders.** Surface the package's three/two/one-month notice on the administrator dashboard. Offer email reminders to installation administrators if an authorized notification address and mail service are configured. Warnings should not block access before expiry.
3. **Explain reactivation errors.** Use the package's specific reason (expired, revoked, device mismatch, configuration changed, offline grace ended) and provide the correct next action. Keep reactivation restricted to the installation administrator after initial setup; preserve the existing protection against public takeover of an installation with an administrator.
4. **Make plan changes transparent.** Link to the KernelBridge account/portal for upgrades, downgrades, and invoice settlement. After refresh, show a before/after package and feature summary and confirm that records are retained while disabled features become unavailable.
5. **Monitor scheduled verification.** Deployment instructions should include Laravel scheduler and queue-worker setup. Monitor failed verification jobs and alert before the offline-grace deadline. Provide a documented manual refresh fallback.
6. **Reconcile deployment documentation.** Describe central client deployments and standalone/local installations as distinct modes, state which mode is valid for production licensing, and explain that local package selection is not cryptographically signed remote licensing.

## Recommended Delivery Order

### Phase 1: Close the bypass and clarify modes

- Make client-mode enforcement fail closed when its central configuration is absent or invalid.
- Prevent centrally activated installations from falling back to local/default plans.
- Update both licensing guides and deployment configuration examples.
- Add tests for mode toggling, central-state presence, and local fallback rejection.

### Phase 2: Make renewal and reactivation operable

- Add expiry/status/verification information to Installation Setup.
- Connect the existing renewal notice helper to a visible admin warning.
- Provide actionable error states and a documented renewal/portal route.
- Verify scheduler, queue, and offline-grace monitoring in deployment instructions.

### Phase 3: Improve subscription change handling

- Include a revision and effective timestamp in server entitlement responses.
- Refresh immediately after an administrator returns from the central portal, retaining the scheduled check as fallback.
- Show a feature diff after refresh and test both upgrade and downgrade behavior, including data retention and blocked routes.

## Acceptance Checks

- A client deployment with central activation cannot gain modules by changing `APP_MODE`, toggling the optional flag, deleting cache state, or creating a local license row.
- A changed or copied license cache, altered licensing configuration, wrong machine identity, explicit revocation, and expired subscription all fail closed with an accurate recovery message.
- Renewal notices appear to installation administrators at three, two, and one month; expiry is visible on the setup screen.
- Renewal/reactivation requires an authorized installation administrator after first-run setup and cannot be claimed through the public activation route.
- A central package upgrade and downgrade are reflected after successful verification; disabled features are blocked on direct routes and existing operational records are retained.
- Scheduler or queue failure is observable before offline grace expires.

## Overall Assessment

The installed licensing client has solid cache-integrity and device-binding controls for an offline-capable PHP client. The system is not yet robust against a client deployment's environment being switched to standalone mode, and it lacks user-facing renewal status. Centralized upgrade/downgrade handling is architecturally appropriate, but dependable application requires verified entitlement refresh, visible status, and monitored scheduler/queue services. Licensing should be treated as a centrally enforced entitlement with client-side tamper detection, not as tamper-proof DRM against the administrator of the licensed computer.