# Universal KernelBridge Product Entitlement Adapter Implementation Guide

## Purpose

This guide is a reusable implementation standard for any product integrating with KernelBridge. It covers the product-side adapter and application functionality required to consume signed plan entitlements, enforce them at runtime, and constrain permission assignment where the product has roles or permissions.

The guide is framework- and language-neutral. Examples use PHP/Laravel-style names only where useful; adapt the services, middleware, persistence, route, job, and test patterns to the target product. Do not copy MediFlow-specific capability names, role assumptions, configuration paths, or implementation details into another product without product-owner review.

The target is additive and backward-compatible:

- KernelBridge owns license lifecycle and the effective plan grants for Direct installations.
- The product's trusted platform owns each customer's effective plan grants for Platform Account installations and synchronizes them to KernelBridge for every eligible linked license/account.
- The grant source for Hybrid installations is the source explicitly bound to that installation.
- The product continues to own its users, roles, permissions, temporary permissions, organization/module access, tenant boundaries, and resource authorization.
- A plan grant never creates a user, role, permission, or module-user assignment.

Do not treat this guide as authorization to turn on structured snapshots or strict entitlement enforcement globally. Each phase has a checkpoint and must pass before moving to the next.

## Product adaptation and important boundaries

Before implementation, identify the target product's architecture and record its current state. Do not assume every product is MediFlow, uses Laravel, has RBAC, has a module layer, or installs the same SDK version.

The implementation agent must determine and document:

- The product's language/framework, server-side and client-side components, persistence model, SDK/package version, and supported installation/deployment types.
- Which platform/backend and client application surfaces exist, which licensing modes each supports, and which separately owned deliverables/checklists are required for each.
- Existing license lifecycle and activation/verification flows, legacy feature/module checks, offline behavior, and whether signed structured snapshots are supported and enabled.
- The product's entitlement/capability definitions and their current source of truth.
- Whether the product uses roles, permissions, temporary/direct grants, per-user module access, tenant boundaries, or another authorization model. If it has no RBAC, omit the permission-mapping/assignment work but still enforce capability checks at protected operations.
- Every authorization surface: web/API routes, UI actions, service/domain methods, background jobs, scheduled tasks, command handlers, integrations, and data/resource policies.
- The product's licensing mode and authoritative grant source for each installation. Direct mappings, Platform Account updates, and Hybrid source binding are distinct mechanisms; a catalog alone does not grant any customer access.
- Whether KernelBridge has a current catalog and plan/source grants for the product, and whether the structured snapshot flag is enabled in the relevant environment.

If code, data, SDK behavior, or existing product policy conflicts with the trust/source model below, stop at Checkpoint 0 and report the conflict. Do not silently adapt authority rules or migrate existing behavior.

## Required integration surfaces: product platform and client application

When a product has both a customer/product platform and one or more client applications, the implementation must cover **both sides**. A platform publisher alone does not enforce access inside client applications; a client SDK alone cannot author or synchronize Platform Account grants. Treat them as separate deliverables joined by the KernelBridge contract.

| Surface | Required responsibilities |
|---|---|
| Product platform / account backend | Own customer identity, account subscription/plan selection, billing/business rules, and the canonical account-level effective grants for Platform Account mode. Publish/update the product catalog through trusted server credentials, fan out account grant changes to every eligible KernelBridge license/account-link, track revisions/idempotency and reconciliation status, and provide safe operational visibility for rejected or pending updates. Never expose publishing credentials to a browser/client. |
| Client application / product runtime | Use a compatible KernelBridge SDK/adapter in each supported runtime to activate/link, verify signatures and source binding, cache/refresh the signed snapshot, and enforce capabilities at protected operations. Apply local user/role/tenant/resource checks separately. The client must not choose its plan or accept an unsigned grant payload from the product UI/platform session as entitlement authority. |
| KernelBridge | Remain authoritative for license lifecycle, approved source binding, accepted grant validation/storage, and signing/delivery of effective installation snapshots. Direct plan mappings are managed in KernelBridge; Platform Account grants are accepted from the product platform; Hybrid uses the source bound to each installation. |

For products that do not have a separate account platform or do not use Platform Account licensing, omit the corresponding publisher/account-sync work; do not invent a platform component or change licensing mode to fit this guide. A product platform may still own other business functions, but only the configured grant authority may author entitlements for an installation.

Create separate implementation/test checklists for the platform publisher and each client application/runtime. The platform checklist verifies correct account-to-license fan-out, grant update/revocation, retries, and reconciliation. The client checklist verifies snapshot verification and cache lifecycle, runtime-specific enforcement, role/permission intersection where applicable, and fail-closed behavior. Do not declare the product integration complete until every applicable surface passes its checkpoint.

## Trust and access model

For a protected product action, all applicable gates must pass:

```text
usable and fresh KernelBridge license
AND the installation's bound source grants the required capability
AND the authenticated principal's product authorization allows the action, where applicable
AND any required organization/module membership or user assignment is active
AND tenant, department, ownership, and resource policies authorize the target
```

For permission assignment, when the product has permission-based RBAC:

```text
permission is mapped to a known capability
AND the current installation has that capability
AND the requested assignment is otherwise valid in existing RBAC
```

The plan does not grant a role or permission to users. For products with permission-based RBAC, a plan capability is an additional constraint on whether a permission can be assigned and used. Role configuration should be preserved when a plan loses a grant, but the uncovered permission must no longer authorize an action.

## Platform Account: account entitlement to client installation

For Platform Account products, the customer's product-platform account is the commercial entitlement owner. The plan is selected/activated for that account in the trusted product platform; the client app does not independently select a plan or calculate its own grant set.

The required flow is:

1. The product platform activates or changes a customer's subscription/plan under its authenticated account and billing/business rules.
2. The product platform resolves the account's **effective entitlements** from the active plan and any explicitly supported add-ons or account overrides. This is the canonical account-level state for that product. Persist its revision and the source inputs needed for audit in the product platform.
3. The trusted product platform publishes the effective grants to KernelBridge using its product-scoped server credential. The update identifies the product, customer license, approved account-link association, current catalog revision, source revision, idempotency key, and typed grants as required by the agreed API. Never send the platform credential from a browser or client app.
4. KernelBridge validates the credential scope, product, approved account link, license lifecycle, current catalog, grant keys/types, and revision/idempotency rules. It stores an accepted source grant bound to the matching license/account link. The update cannot activate, renew, or override an invalid KernelBridge license.
5. When a client installation is activated/linked to that customer account through the approved KernelBridge account-link and installation-code flow, KernelBridge binds that installation to the `product_platform` source. On activation and subsequent verification/refresh, KernelBridge resolves the accepted account grant for that binding and includes it in the KernelBridge-signed installation snapshot.
6. The client app's installed KernelBridge SDK verifies and caches that signed snapshot. Product authorization then checks the account's plan capability together with local user permissions, user/module assignment, tenant, and resource policies as applicable.

The propagation invariant is:

```text
one product-platform account's current effective entitlement set
is propagated to every eligible client license/install bound to that same
product, customer account, and approved account link
```

Client applications receive the account's plan entitlement; they do not get a separate plan merely because the application was installed. KernelBridge still evaluates each license and installation independently for validity, activation, source binding, expiry/revocation, and snapshot freshness. Do not copy grants across products, customer accounts, unapproved links, or unrelated licenses. If one customer account has multiple licenses or links, the platform must explicitly enumerate and synchronize the eligible targets; an update to one license must not be assumed to update every other license automatically.

The current KernelBridge platform-grant update contract is bound to a specific `license_uuid` and approved `account_link_uuid`; it is not an implicit account-wide broadcast. Therefore, the product platform must fan out the canonical account entitlement to each eligible license/link (or use a future explicitly approved account-scoped API). KernelBridge stores and resolves each accepted target grant against its own license/link binding.

Keep the product platform's account-level entitlement record distinct from KernelBridge's license/link-bound accepted update and from the client app's encrypted signed-snapshot cache. The product platform is the source of truth for Platform Account plan grants; KernelBridge is authoritative for license lifecycle and signed delivery; each client installation stores only its verified local snapshot. User roles and permission assignments remain in the product and are never copied into the account grant.

On plan, add-on, override, account-link, or subscription status changes, recompute the account-level effective grants and publish updates to every affected eligible license/link. Define an explicit deprovision/revocation update for removed grants. Use retry-safe idempotency and monotonically increasing source revisions at the scope enforced by the update API; when grants are fanned out to multiple license/link targets, preserve ordering independently for each target unless the protocol explicitly supports a shared account revision. Refresh affected installations and verify that new snapshots supersede old ones. A stale, failed, or partial fan-out must be visible and retried; do not report the account entitlement change as fully propagated until all required targets are reconciled.

Make account-plan changes and grant publication crash-safe. Persist the account's new effective entitlement revision and durable per-license/link delivery work in the same product-platform database transaction (for example, with a transactional outbox); publish asynchronously from that durable work rather than relying on an in-request HTTP call as the only record. Track each target's desired revision, last accepted KernelBridge revision, attempt/error state, and reconciliation status. Use bounded exponential backoff with jitter for transient failures, do not endlessly retry permanent schema/scope/account-link errors, and provide authorized alerting and safe replay for operators. A periodic reconciler should compare the platform's desired account/license grants with KernelBridge's accepted state and repair missed or partial deliveries. Never mark a target synchronized based only on enqueueing a job or receiving an unverified success response.

Define an explicit propagation and revocation policy with the product owner: the maximum permitted delay from plan change to server/client enforcement, how clients learn of a newer snapshot, and what protected operations do while a downgrade/revocation is pending or the client is offline with a previously valid snapshot. A connected server may be able to refresh immediately; an offline client cannot know about a remote downgrade until it reconnects or its signed snapshot reaches its bounded freshness/offline limit. Do not promise immediate remote revocation unless the runtime can enforce it. For security-critical capabilities, choose a stricter bounded policy or require online authorization; for other capabilities, document the accepted exposure window. Never extend signed expiry or offline grace to hide propagation failures.

Define deterministic grant composition before publishing: how the base plan, add-ons, time-limited overrides, suspension, cancellation, and partial payment affect each key and typed limit; how conflicting values resolve; and when an explicit denial wins. Apply composition on the trusted product platform, version and audit its inputs/result, and publish one complete effective grant set. Do not expect KernelBridge or a client SDK to infer plan/add-on precedence or merge separate partial payloads.

**Checkpoint:** A multi-installation test proves the same current account grants reach each eligible linked client installation, while another account, product, invalid license, revoked link, or unlinked installation receives no grant. A plan downgrade/removal test proves every affected installation loses the removed capability after successful update and snapshot refresh without deleting product user/role configuration.

For each supported client runtime, separately verify that it refreshes and enforces the updated signed snapshot. Successful platform-to-KernelBridge publication is not proof that existing client installations have refreshed or that application operations are protected.

### Separate product access layers

1. **Plan capability:** whether the installation is entitled to a product capability. Use a product-owned, stable key; the key below is an illustrative MediFlow example only: `mediflow.module.pharmacy`.
2. **Principal/organization access:** whether the authenticated user, service account, tenant, or organization may enter/use the capability under product policy.
3. **Action authorization:** whether the product's role, permission, ownership, or other policy allows the specific operation, where such a layer exists.

Apply the layers relevant to the target product. Do not equate role membership with plan entitlement, and do not invent a module-user layer if the product does not use one.

### Module-centered products

Many products organize their paid features and access controls around modules. Treat module access as a first-class entitlement integration pattern, not as a requirement that every product must adopt:

- Define stable capability keys for each commercially gated module. The product may also define finer-grained action capabilities when modules contain separately sellable or sensitive functions.
- Map each module route, API operation, UI action, job, and service operation to its required capability or capabilities. A module-level navigation check is useful but is not sufficient enforcement.
- Where local permissions are themselves module-scoped, derive/check their required capability from an explicit, reviewed module-to-capability map. Do not infer the commercial boundary from role names, labels, URL prefixes, or loose string matching.
- Require the plan capability in addition to the product's existing per-user module assignment and role/permission checks. A plan grant opens the module for the installation; it does not assign that module to every user or grant every action.
- If an operation spans modules, declare all required capabilities and define whether the rule is all-of or any-of. Default to all-of for operations requiring multiple licensed capabilities; any-of must be explicit and tested.
- Ensure that module-less/shared services have explicit policy. Do not classify them as free just because they lack a module identifier.
- Keep platform administration, licensing/renewal, security administration, and recovery paths in an explicit allowlist so a missing business-module grant cannot prevent restoring the installation. Do not let that allowlist provide access to business data or actions.

Products that are not module-centered should map their domain capabilities directly to stable grant keys and enforce them through their existing authorization structure.

## Runtime and deployment adaptation

Entitlement enforcement must be designed for every runtime in which the product can execute. A product may have several runtimes with different trust boundaries; the UI and client runtime must not become the licensing authority.

| Runtime/deployment | Required integration behavior |
|---|---|
| Server-rendered web application | Verify/cache the signed snapshot in the trusted server process. Enforce grants in server middleware/controllers and again in sensitive domain operations. UI visibility is supplementary only. |
| API/backend service | Authenticate the caller, then apply installation capability checks and product user/tenant/resource authorization on every protected request. Do not trust a client-supplied plan, grant, or entitlement boolean. |
| Browser SPA or other untrusted client | Do not embed platform credentials, signing secrets, or trust client-side grant state for protected data/actions. The backend remains authoritative; UI gating is only presentation. |
| Desktop/mobile/offline client | Use a runtime-compatible SDK in a trusted process boundary (for example, desktop main process rather than renderer). Verify signed snapshots, protect local credentials/cache using platform facilities, enforce bounded offline grace, and treat local checks as defense-in-depth where the customer controls the device. |
| Worker, scheduled job, CLI, or serverless task | Resolve the same installation/license context and verified entitlement policy as request handling. Fail closed or defer safely when the snapshot is unavailable or invalid; never skip checks because there is no interactive user. |
| Multi-service or multi-tenant deployment | Define which service owns the verified cache and how installation/tenant context is bound to each decision. Prevent one tenant or installation's grants from being reused for another. Use a trusted internal interface, not unsigned grant copies. |

For each supported runtime, document: where verification runs, how the installation identity is selected, where credentials and cached snapshots are stored, how snapshots refresh, what offline behavior is permitted, how signing-key rotation is delivered, and how failures are surfaced. If a runtime cannot securely verify the signed contract or cannot identify the correct installation, it must obtain an authoritative decision from a trusted backend rather than inventing a local fallback.

## Installed KernelBridge package/adapter assessment

Do not assume that a package visible in a source repository is installed in the product, that a package version supports structured entitlements, or that installing it automatically wires application authorization.

Before implementation and before rollout:

- Identify the exact package(s) actually used by each runtime, including Composer/npm/other package name, resolved version, lockfile entry, source (registry, path, VCS), and installed artifact location.
- Verify that the installed version supports the required activation/verification flow, signature algorithm and key IDs, structured snapshot schema, source binding, revision/freshness checks, typed capability/limit decisions, secure cache/credential storage, and the target runtime.
- Inspect the installed public API and behavior, not just package documentation or a sibling working tree. Confirm whether each feature exists in the exact resolved version.
- Check package runtime/platform requirements, framework compatibility, transitive dependencies, autoload/export behavior, and whether it is safe for the intended process (server, desktop main process, browser, worker).
- If it is outdated or missing a required capability, upgrade or publish/install a compatible version using the product's package manager and lockfile. Review the dependency diff, migration/release notes, and rollback path; do not silently copy package source into the product.
- Confirm provider/bootstrap registration, migrations, configuration, trusted public keys, cache encryption, scheduled verification, and failure handling required by that installed package.
- Treat the SDK as the trusted snapshot transport, validation, and cache boundary where supported—not as automatic application authorization. The product still must map domain operations to capabilities and wire checks through all relevant runtime paths.
- Do not expose server/platform credentials or signing secrets in a desktop/mobile/browser artifact. A public verification key may be distributed when the SDK contract requires it; it cannot sign or publish grants.

**Checkpoint:** A recorded package/runtime compatibility matrix demonstrates that the actual installed artifacts support the planned flow. Missing features are release blockers, not assumptions to bridge with unverified JSON parsing.

## Multi-language SDK strategy

KernelBridge integrations may be built for ecosystems such as Python, Java/JVM, .NET, Go, Ruby, or others. Do not make the entitlement contract depend on Laravel, TypeScript, a specific serialization library, or one language's type system. The wire protocol and security semantics are language-neutral; each SDK is an implementation of that shared contract.

When a required language/runtime has no compatible package:

- Treat a language-specific SDK or a narrowly scoped product adapter as a separate deliverable. Do not claim support for a runtime until its implementation and release have passed conformance tests.
- First define the supported use cases and trust boundary for that SDK: server-side API/platform integration, server product adapter, desktop main process, worker, or another runtime. Avoid combining privileged platform publishing credentials with client-side installation verification.
- Publish a package for the ecosystem's normal package manager and conventions (for example, PyPI for Python or Maven Central/Gradle for JVM) with explicit supported language/runtime versions, installation instructions, dependency policy, API stability, security reporting, and release/versioning policy.
- Keep each SDK's surface focused and semantically equivalent: signed policy/snapshot verification; product, license, installation, source, schema and freshness binding; typed capability/limit decisions; safe diagnostics; and, only for trusted product-platform server use, authenticated catalog/grant publishing. Do not include an operation in a client SDK if it requires a product-platform secret.
- Use native, maintained cryptographic and secure-storage facilities where available. Do not implement cryptographic primitives manually. A public verification key may be distributed; signing private keys and product integration credentials must remain server-side.
- Define equivalent typed errors and decisions across languages, including missing/malformed/unsupported snapshots, invalid signatures, product/source mismatch, stale revisions, unusable license, missing capability, and invalid limit. Do not turn parsing or network failures into success-shaped defaults.
- Preserve the same offline and cache guarantees where the runtime supports them. If a runtime cannot protect local credentials/cache or securely verify signed payloads, document that limitation and require a trusted server decision rather than presenting local results as authoritative.

### Shared protocol and conformance tests

Maintain a language-neutral protocol specification and shared fixtures before implementing another SDK. Fixtures should contain test-only keys and signed payloads, expected canonical bytes/digests, valid and invalid catalogs/grants/snapshots, source-binding cases, revision/freshness boundaries, and expected stable error codes. Never put production private keys or customer data in fixtures.

Every language implementation must run the same applicable conformance cases, plus ecosystem-specific tests for secure storage, package APIs, supported runtimes, and transport behavior. At minimum verify:

- Exact-byte signature verification and key-ID handling/rotation; do not parse and reserialize JSON before verifying a signature over the original bytes.
- Duplicate JSON object key rejection where the protocol requires it, strict types, unknown-field policy, and deterministic canonicalization only where explicitly required.
- Product/license/installation/source binding, schema compatibility, monotonic revisions, issue-time checks, expiry, and bounded offline grace.
- Missing grants deny; capability and typed limit semantics match across all SDKs.
- No request can select its own authority/source or use an untrusted client credential to publish catalogs/grants.
- Equivalent valid payloads produce equivalent authorization decisions, and equivalent invalid payloads produce the agreed stable error outcome across languages.

Run the shared fixtures in CI for each SDK and publish a compatibility matrix showing package version, supported language/runtime versions, protocol schema versions, and conformance status. A new SDK is not production-ready merely because it compiles or passes language-local unit tests.

**Checkpoint:** The common protocol fixtures pass in every supported SDK, and each package's runtime and secret-handling boundaries are documented. Unsupported languages remain explicitly unsupported until this checkpoint passes.

### Administrator and recovery paths

Do not let a missing business capability lock authorized operators out of license status, activation, renewal, support diagnostics, or the controls needed to restore service. Define an explicit, audited product-administration/recovery allowlist and test it separately. This exception is not a wildcard grant for business capabilities.

## Permission-to-capability contract

For products with permissions/RBAC, add a reviewed, source-controlled mapping from each permission identifier to one or more stable capability keys. Follow the target repository's conventions for storing it; a dedicated configuration or permission-catalog component is appropriate when no existing abstraction exists. Products without permission-based RBAC should skip this mapping and apply capability checks through their existing authorization architecture.

Illustrative MediFlow-shaped example only; replace all identifiers with the target product's actual catalog and permission keys:

```php
return [
    'patient.read' => ['mediflow.module.patient_records'],
    'patient.update' => ['mediflow.module.patient_records'],
    'pharmacy.dispense' => ['mediflow.module.pharmacy'],
    'laboratory.result.publish' => ['mediflow.module.laboratory'],
];
```

Rules:

- Use the exact stable permission identifiers persisted and checked by the target product; do not make runtime authorization depend on display names, fuzzy prefixes, role names, or plan labels.
- One permission may require multiple capabilities; all required capabilities must be granted.
- Map every permission that protects a paid product operation. Include role-assigned permissions and temporary/direct permissions in the same effective authorization calculation.
- Define explicit treatment for platform, authentication, and recovery permissions. Do not silently classify unknown permission names as entitled.
- In strict mode, an unmapped permission used for a protected action or assignment is denied and emits a safe diagnostic. It must not be interpreted as a free permission.
- Validate the mapping against the permission catalog in tests/CI: missing mappings, unknown permission names, duplicate/conflicting declarations, and capability keys absent from the current product catalog must fail validation.
- If a feature needs action-level packaging finer than the current module capability, first add a new stable capability to the product catalog, publish a new catalog revision in KernelBridge, and map to it. Do not repurpose an existing key with a different meaning.
- Include a mapping version/revision in diagnostics. Changes to the checked-in mapping require review and corresponding tests; never silently change customer role data as a migration side effect.

## Implementation phases and checkpoints

### Phase 0 — Baseline and preservation checkpoint

Before editing:

- Record current branch/commit and dirty files. Do not discard or overwrite user changes.
- Review the target product's licensing, authorization, catalog, and integration documentation; entitlement/license services; role/permission or equivalent policy models; temporary/direct access logic; route/API middleware; jobs; UI actions; and sensitive domain services. MediFlow-specific references and examples are not prerequisites for other products.
- Inventory all paid modules/domain capabilities and the operations that consume them. For RBAC products, enumerate permission identifiers and their module relationships. Identify route/API, service, job, command, UI, and cross-service enforcement points for the target product's actual features.
- Produce a runtime/deployment matrix covering every supported web, API, browser, desktop, mobile, worker, offline, and multi-tenant execution path that applies to the product.
- Confirm each licensing SDK/adapter's exact installed package name, resolved version, lockfile source, runtime support, signature/snapshot capabilities, and required configuration as specified in "Installed KernelBridge package/adapter assessment". Do not assume that changes in a shared source repository are already installed or deployed.
- Capture relevant existing test results for activation, entitlement/module access, product authorization, permission assignment where applicable, and high-risk workflows.

**Checkpoint:** Existing licensing, product authorization, supported runtimes, and installed package capabilities are documented; no unrelated behavior is changed. Stop if current data or behavior conflicts with the trust/source model above.

For products with both a platform and client app, the Phase 0 inventory must identify both repositories/deployables, their owners, runtime/package versions, and the API/data handoff between them. Keep changes in each system scoped and independently testable; do not assume one shared SDK update completes both sides.

### Phase 1 — Complete capability and permission inventory

- Compare current catalog definitions with the actual paid module/domain capability and operation inventory.
- For module-centered products, define an explicit module-to-capability map and map every protected operation to its required capability set. For RBAC products, map each protected permission to one or more capabilities; identify operations needing finer-grained capabilities.
- Review capability and permission mappings with the product owner. Permission names alone are not proof that two actions have the same commercial meaning.
- Define the explicit administration/recovery allowlist, including how administrators can inspect license status and restore access after an expired or downgraded plan.
- Keep current module grants and role permissions separate; do not send user/role data to KernelBridge.

**Checkpoint:** Every paid capability/operation (and every protected permission where RBAC exists) has an approved mapping or is explicitly blocked from commercial operation; administration/recovery exceptions are listed and tested.

### Phase 2 — Add typed entitlement decisions without changing enforcement

- Add or extend one product-side entitlement adapter/service as the sole reader of verified, cached structured grants. `ProductEntitlementService` is a MediFlow example name, not a required class name.
- Provide typed methods such as `capabilityDecision($key)` and `permissionDecision($permissionName)` with stable outcomes: granted, missing grant, unmapped permission, missing/malformed/unsupported framework, unusable license, stale snapshot, and configuration error.
- Preserve existing module/feature decision APIs and their callers while introducing the new structured APIs.
- Require the licensing SDK to verify KernelBridge's exact signed bytes and source binding before any structured grant is returned. Product code must not parse an unverified API response as authority.
- Do not infer access from plan display names or the package/module hierarchy. Runtime decisions use stable grant keys. Package/module hierarchy organizes catalog authoring; it does not implicitly grant child capabilities.
- Add a feature/configuration gate for strict structured checks, defaulting off. In shadow mode, log differences without changing the existing decision.
- In strict mode, missing structured claims must deny protected structured checks; do not fall back to legacy module keys for those checks. Legacy behavior remains available only through the explicitly disabled/off rollout path.
- Log safe diagnostics only: permission/capability key, decision reason, grant source, catalog/source/entitlement revision, snapshot age, and mapping revision. Never log the full snapshot, credentials, activation codes, or health data.

**Checkpoint:** Unit tests prove signature-verified structured decisions and fail-closed outcomes; default/off behavior remains unchanged.

### Phase 3 — Implement and validate permission mapping

- Add the explicit permission-to-capability mapping and mapping completeness validator.
- Add a central policy/service for checking whether each permission is covered by the current verified plan.
- Handle permissions with multiple required capabilities as logical AND.
- Apply the same policy to role permissions, direct permission assignment if supported, and temporary permissions. Temporary permissions must not bypass the plan gate.
- Preserve current permission records and role pivots. A denied assignment must return a clear validation/authorization error before any write.
- Make role permission updates transactional: validate all requested permission IDs/names and entitlement mappings first, then apply the complete change or none.
- Keep unmapped entries visible to administrators only as safely blocked/configuration errors; do not silently omit them from submitted changes.
- Do not automatically delete, detach, or rewrite assigned role permissions when plan grants change.

**Checkpoint:** For RBAC products, tests show an ungranted permission cannot be added through any assignment path, a granted permission can be assigned subject to existing authorization policy, and rejected bulk updates leave all permission/assignment state unchanged.

### Phase 4 — Enforce the entitlement/RBAC intersection

- Integrate plan capability checks into existing authorization boundaries without replacing role/permission checks.
- Audit middleware that currently grants access on role OR permission. A matching role must not bypass a missing plan capability.
- Audit the product's effective-authorization helpers and consumers, including temporary/direct grants, navigation, controllers, UI actions, API rules, console commands, queued jobs, and domain services. Names such as `User::hasPermission()` and frameworks such as Livewire are examples, not required abstractions.
- Preserve intended authorization semantics (any-of vs all-of roles/permissions) while adding the plan gate. Do not globally redefine a low-level `hasPermission()` helper without checking all callers.
- Guard sensitive service methods as well as routes; route middleware is not sufficient for jobs, direct calls, APIs, or alternate entry points.
- Ensure administrators cannot use role assignment or direct permissions to bypass plan restrictions.
- Continue enforcing per-user module access, tenant membership, department/resource ownership, and data authorization independently.
- Keep permission assignment state when a grant is removed. Runtime access becomes denied because the capability gate fails; restoring the grant can make the existing role setup effective again.

**Checkpoint:** Every identified paid operation has server-side enforcement; a role match, temporary permission, direct permission pivot, or UI state cannot bypass the plan gate.

### Phase 5 — Entitlement-aware administration UX

- Use the same server-side policy to present available permissions during role create/edit and temporary permission workflows.
- Disable or clearly mark permissions unavailable under the current plan; explain that the feature is not included rather than suggesting a role is misconfigured.
- Validate again on submit; never trust the form or hide-only control.
- Show safe entitlement status and revisions to authorized administrators, without exposing signed claims, tokens, or the full grant payload.
- Preserve existing role assignments when a plan changes and show which assignments are currently inactive due to missing plan grants.
- Keep license activation, renewal, support, and recovery actions reachable even if the license is expired or a business capability is not included.

**Checkpoint:** UI and server validation agree, inaccessible permissions cannot be submitted, and existing role configuration is not destroyed by a downgrade.

### Phase 6 — Test matrix and regression coverage

Add focused tests for at least:

| Plan grant | User permission/role | Snapshot | Expected |
|---|---|---|---|
| Granted | Required product authorization present (if applicable) | Valid/current | Allow, subject to organization/module and resource checks |
| Granted | Product authorization absent | Valid/current | Deny |
| Missing | Product authorization present | Valid/current | Deny |
| Missing | Temporary/direct authorization present, if supported | Valid/current | Deny |
| Missing | Matching role present, if using RBAC | Valid/current | Deny |
| Any | Any | Missing, malformed, unsupported, or invalid signature in strict mode | Deny |
| Any | Any | Expired/unusable license or stale snapshot | Deny protected business action |
| Required capability absent from mapping | Permission present | Strict mode | Deny and report mapping error |
| Required capability | Assignment submitted | Capability missing | Reject atomically; preserve current role pivots |
| Restored capability | Existing permission still assigned | Valid/current | Existing RBAC grants work again without data recreation |
| Platform Account plan updated | Multiple eligible installations linked to the same product account | Update accepted and snapshots refreshed | All eligible clients receive the same effective account grants |
| Platform Account account/license/link mismatch | Any | Any | Reject update or deny snapshot; never cross-apply grants |
| Platform Account plan downgraded or revoked | Linked installations | Update accepted and snapshots refreshed | Removed grants are denied on every affected client; user/role configuration remains intact |
| Platform Account publish failure / partial fan-out | One or more linked license targets | Transient or permanent update failure | Desired revision remains pending/visible; retry/reconciliation repairs transient gaps; permanent errors alert operators; target is not marked synchronized |
| Platform Account downgrade while client is offline | Client holds older valid signed snapshot | Offline within/after configured freshness window | Apply the documented bounded stale-grant policy; never extend offline grace or claim immediate revocation |
| Plan plus add-on/override conflict | Conflicting source inputs | Effective grant computation | Deterministic documented precedence and typed result; composition is performed by the product platform |
| Admin/recovery action | Authorized recovery administrator | Business grant missing/expired | Allow only the explicit recovery operation |

Also test that existing authorization and organization/module-access behavior is unchanged when strict structured enforcement is off.

**Checkpoint:** Focused and related full regression suites pass; tests include a route/API path and a direct service/job path for sensitive operations.

### Phase 7 — Shadow rollout

- Confirm KernelBridge has a current catalog and correct plan mappings for every pilot plan.
- For Platform Account plans, confirm the product platform persists the canonical account-level effective grants and reliably fans them out to every eligible license/account-link target. Test a customer account with multiple linked installations; do not simulate this source by adding Direct mappings.
- Leave structured snapshot inclusion disabled initially; first validate mapping completeness and resolver output in KernelBridge's shadow evaluation.
- Enable product-side shadow comparison for a small pilot cohort. Compare current legacy decisions with structured decisions without enforcing the latter.
- Review missing mappings, grant diffs, source binding, revision behavior, snapshot freshness, and denial diagnostics with product owners.
- Do not log patient or customer data in shadow diagnostics.

**Checkpoint:** Product owner approves the capability/permission mapping and observed shadow results. No unexplained differences remain.

### Phase 8 — Controlled strict enforcement

- Ensure trusted public signing keys and a compatible licensing SDK/adapter are provisioned in each applicable trusted runtime, based on the verified package/runtime matrix. Use the supported SDK for each language/framework or implement an equivalent verifier that validates exact signed bytes, key IDs/rotation, source binding, schema, freshness, and product/license/installation binding.
- Confirm all required Direct plans have correct KernelBridge mappings. For Platform Account plans, confirm customer-account grant changes are synchronized to every affected eligible license/account-link and use the current catalog revision.
- Enable structured snapshot inclusion only for the approved pilot after verifying KernelBridge configuration and rollback.
- Refresh/verify pilot installations and confirm the signed framework source matches the installation's authorized activation/link path.
- Turn strict enforcement on for one noncritical capability first, then increase coverage in reviewed batches.
- Monitor denials, missing snapshots, invalid signatures, source mismatches, stale revisions, mapping errors, and support tickets.
- Maintain a documented rollback that disables strict enforcement and structured snapshot attachment without deleting mappings, role data, licenses, or signed cache records.

**Checkpoint:** Pilot remains stable through renewal, downgrade, expiry/grace, account-link revocation, snapshot refresh, and rollback exercises before expanding to all installations.

## Deployment and rollback safeguards

- Never enable strict enforcement before every paid permission is mapped and every applicable plan has a validated grant mapping.
- Never interpret absence of a grant as unlimited access.
- Never combine Direct and Platform Account grants unless a separately approved contract explicitly defines such behavior; the current source model selects one bound source.
- Never treat a queued or failed Platform Account update as synchronized. Track and reconcile desired versus accepted revisions per target.
- Never claim immediate downgrade/revocation on an offline client without a mechanism that can deliver and enforce the change; define and test the maximum stale-grant window.
- Do not change product licensing mode or activation flow as part of permission mapping.
- Do not make a migration that deletes product authorization records, role/permission assignments, organization/module access, or legacy license data.
- Roll back by turning off the strict gate and/or structured snapshot flag, not by rolling back schema/data migrations that contain published immutable records.
- Keep the licensing and permission-administration recovery path available during upgrades and outages.

## Completion criteria

The product is ready for a controlled strict-entitlement pilot only when:

- Every protected permission is explicitly mapped to one or more catalog capabilities where permission-based RBAC exists; otherwise, every protected operation is explicitly mapped through the product's authorization model.
- Every paid module/domain operation is mapped to the required capability set and is enforced in every supported trusted runtime.
- The exact installed package/adapter versions and runtime compatibility are verified and recorded; each runtime's verification, cache, key rotation, refresh, and failure behavior is tested.
- Current catalog and per-plan/source grants are reviewed and published in KernelBridge.
- For Platform Account, the product platform's canonical account entitlement and its fan-out to all eligible linked licenses/installations are reconciled and tested for plan changes, downgrades, and revocation.
- Every applicable product-platform publisher and client application/runtime has passed its own integration checklist; both the grant publication path and client-side consumption/enforcement path are verified end to end.
- All permission assignment paths validate grants server-side and fail atomically.
- All protected operations require both the plan capability and the product's existing authorization checks, plus any applicable organization/module/resource checks.
- Strict mode fails closed on absent, invalid, stale, unsupported, or incorrectly sourced structured snapshots.
- Existing product authorization and licensing behavior passes regression tests with strict mode off.
- Shadow results, pilot monitoring, recovery access, and rollback are approved and exercised.

Until these criteria pass, keep current authorization behavior authoritative and do not claim that the product is strictly enforcing plan-based entitlements.

## Optional MediFlow implementation profile

The examples and current-state notes below apply to MediFlow only; they are not requirements for other products:

- MediFlow has `ProductEntitlementService`, `LicenseService`, and `InstallationCatalog` services and a Laravel licensing SDK.
- Its current catalog uses stable module keys such as `mediflow.module.pharmacy`; the catalog is initially module-level, so finer permission packaging may require adding capabilities and publishing a new catalog revision.
- Its local authorization includes roles, permissions, temporary permissions, module-user access, and resource/tenant checks.
- Permission entitlements are mapped from persisted permission module and namespace values to those stable module capabilities; unknown or conflicting mappings fail closed in strict mode.
- `MEDIFLOW_ENTITLEMENT_STRICT_ENFORCEMENT` defaults to `false`. Enable it only after KernelBridge structured snapshots are enabled and reviewed plan mappings are available; strict checks constrain role and permission helpers, middleware, and role-permission assignment without deleting existing assignments that are temporarily unlicensed.
- Its legacy module/plan logic and fallback behavior remain available while structured enforcement is introduced behind staged gates.

For MediFlow, consult the existing RBAC and module licensing documentation referenced in Phase 0. For another product, replace this profile with a short, product-specific inventory and retain the universal trust model, gates, and checkpoints above.
