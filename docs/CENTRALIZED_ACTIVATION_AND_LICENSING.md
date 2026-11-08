# Centralized Activation And Licensing

## Decision

MediFlow no longer manages superadmin, agents, clients, plans, subscription activation, or license activation inside the local hospital installation.

Those responsibilities belong to the centralized MediFlow platform called kernelbridge.

## Local Hospital System Responsibilities

The local hospital system should keep only:

- Hospital administrator and staff users
- Hospital roles and permissions
- Local module enforcement based on received activation data
- Hospital operational data
- Optional sync/activation records pushed from the central platform

## Central Platform Responsibilities

The central platform should manage:

- Agents and partners
- Client profiles
- Subscription plans
- Activation invoices
- Activation payments
- License keys
- Enabled modules per client
- Enterprise branch entitlements

## Local Tables Kept

The local system still keeps license enforcement tables that may be populated by activation/sync:

```text
client_licenses
client_enabled_modules
module_user_access
```

## Removed Local Platform Screens

These screens were removed from the local hospital app:

- Client Management
- Agent Management
- Plans Management
- License Management
- Superadmin dashboard

## Removed Local Role

The local app no longer seeds:

```text
superadmin
```

The highest local role is:

```text
administrator
```

## MediFlow licensing SDK integration pilot

The local MediFlow pilot renders the SDK-owned KernelBridge-branded activation screen and uses its installation-code flow. It accepts a one-time code, the SDK exchanges it without a product API credential, and KernelBridge returns an installation-bound bearer credential plus an Ed25519-signed entitlement snapshot. The product server enforces each module at the existing route and domain-service checks; user roles, permissions, and tenant/resource authorization remain independent.

The MediFlow catalog in `config/entitlement_framework.php` defines stable `mediflow.module.*` capability keys and exports the shared catalog structure through `ProductEntitlementService::catalog()`. When the verified snapshot contains `entitlement_framework`, those grants are authoritative and missing keys are denied; MediFlow never falls back to legacy flat module entitlements for that snapshot. Older snapshots without the extension continue using the existing flat module keys. Keep `ENTITLEMENT_FRAMEWORK_SNAPSHOT_ENABLED` disabled until the matching catalog is published and Direct plan mappings are populated in KernelBridge. Plans and package display labels are not runtime authorization keys.

Installation administrators can view safe source/revision, last verification, snapshot age, and offline-window diagnostics on Installation Setup. API denials use stable codes such as `missing_capability`, `license_unavailable`, `stale_snapshot`, and `temporary_licensing_service_outage`; diagnostics do not include credentials or signed payloads.

The current MediFlow installation has no canonical branch-usage counter or transactional branch-creation boundary, so `branch_management` is a capability only; this pilot does not invent or enforce a numeric branch limit. Define and test a product-owned atomic usage check before publishing any limit grant. Platform-account broker/outbox delivery is also out of scope for this direct installation pilot.

The working checkout temporarily resolves `kernelbridge/licensing-client-laravel` from the adjacent `../kernelbridge-license/packages/laravel` path. This is only for local integration testing; it is not a deployable package source. Replace it with a tested immutable package release before any deployment.

The tests use generated test signing keys and a fake KernelBridge response, so they do not prove a real account activation. The local MediFlow environment now trusts the active KernelBridge Ed25519 public key through `KERNELBRIDGE_ENTITLEMENT_PUBLIC_KEYS`, indexed by key ID; this key was checked against the configured KernelBridge signer. Provision the matching trusted public key for every deployment before activation and update the map before any signing-key rotation. Unknown keys fail closed.

The new installation-credential migration is pending on the configured MediFlow database. It has not been applied. Do not apply it to a live/customer installation until a non-production backup, upgrade test, and rollback procedure are approved. Keep the legacy activation route available during migration.

See the SDK repository's [improvement plan](../../kernelbridge-license/docs/SDK_IMPROVEMENT_PLAN.md) for the pilot results and remaining release gates.

For the staged shadow rollout, monitoring signals, key-rotation and outage response, and rollback checklist, see KernelBridge's [Entitlement Framework Pilot Runbook](../../kernelbridge/docs/ENTITLEMENT_FRAMEWORK_PILOT_RUNBOOK.md). The current MediFlow integration is not approved for structured-snapshot rollout: publish matching Direct plan mappings and complete the product-side atomic usage-limit boundary before enabling that flag.
