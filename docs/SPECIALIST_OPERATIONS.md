# Specialist package: deployment and operations

Release date: 2026-09-29. Design contract: [Specialist Package Implementation Specification](SPECIALIST_PACKAGE_IMPLEMENTATION_SPEC.md).

## Supported release

One organization per MediFlow installation. Specialist can run standalone or alongside Pharmacy, Diagnostics, Clinic, Maternity, Hospital and Enterprise. The installation's enabled modules and each staff member's permissions/module access control available operations. An Enterprise companion does not confer unrestricted cross-branch access or implement branch isolation.

The workspace is `/specialist`; practice configuration is `/specialist/setup`. The seeded **Specialist Care** menu uses the existing sidebar registry. Installation Setup keeps its existing installation-administrator restriction and remains accessible.

## Deployment

Back up the application database and private document storage before upgrading an existing installation. Use the normal maintenance/deployment process so requests do not execute between code and schema rollout. Apply these additive migrations on the target server:

```sh
php artisan migrate --path=database/migrations/2026_09_29_000001_add_selected_packages_to_client_licenses.php --force
php artisan migrate --path=database/migrations/2026_09_29_000002_create_specialist_workspace.php --force
php artisan db:seed --class=SpecialistModuleSeeder --force
php artisan optimize:clear
php artisan view:cache
php artisan specialist:check
```

Refresh config/route caches according to the site's existing deployment policy. `specialist:check` is read-only and exits unsuccessfully when modules, specialties, an eligible active practitioner, services, patient file types or payment methods need setup. A successful check proves those prerequisites only.

The migrations backfill selected packages from the existing plan and add the Specialist graph and nullable shared references. They do not change activation, expiry, selected modules or user privileges. The focused seeder adds five roles and attaches required permissions/module navigation without detaching existing assignments. Do not run broad user or catalogue seeders as an upgrade procedure.

## Activate and configure

1. An installation administrator selects Specialist as primary or companion package. Review enabled dependencies and optional modules, then save. The primary package controls automatic welcome branding. Existing logo/name/welcome overrides are retained.
2. Provision staff through existing User Management. Assign appropriate roles and active module access to `specialist`, `patient_records`, `clinical_care`, `billing`, and `reports` where reports are required. Installation administration by itself does not assign a clinical role.
3. A Specialist manager configures specialties, then profiles linked to existing clinically authorized accounts. Record professional registration, physical location, IANA timezone, allowed modes, working days/hours and unavailable dates. Configure physical/online/hybrid practice mode and walk-in preference.
4. Create Specialist services with specialty, existing department, charge and appointment duration. These use the shared Service catalogue. Configure patient file types, payment methods and medicine catalogue/routes through existing administrative setup. The practice screen can add catalogue medicine names without purchasing stock.
5. Add approved external facilities if needed. Directory entries carry no portal access or automatic transmission authority.
6. Assign existing patients to the authorized care team/cashiers using their hospital number. New registrations initially grant access to the registering staff; booking grants access to the selected practitioner. Managers can grant/revoke individual assignments. Role membership alone does not expose every patient.
7. Run `php artisan specialist:check` and complete the facility acceptance checklist below.

Profile creation requires clinical-update permission; booking also checks that the practitioner still has clinical permission and Specialist module access. Deactivate unavailable practitioners/services rather than deleting historical records.

## Role bundles

| Role | Included access |
| --- | --- |
| `specialist_manager` | Practice configuration and patient assignment; no clinical notes or signing. |
| `specialist` | Assigned patients, booking/registration, clinical documentation/signing/amendments, prescribing, investigations/review, referrals, care plans and clinical reports. |
| `specialist_assistant` | Assigned patient administration and booking; no clinical narratives or prescribing. |
| `specialist_cashier` | Assigned-patient billing, payments, receipts and financial reports; no clinical narratives. |
| `specialist_receiver` | Assigned clinical review and referral acceptance; no prescribing or signing. Combine with existing receiving-team roles when needed. |

Accounts can hold multiple explicit roles, for example Specialist plus cashier for a sole practitioner. Receiving hospital staff retain their existing admission permissions; the receiver role does not permit admission or bed allocation. Department-head provisioning continues to follow existing department role mappings; an administrator provisions the new Specialist bundles.

Staff with additional existing clinical roles retain the access those roles already confer. Specialist-only staff are prevented from using legacy patient endpoints that lack Specialist assignment scope.

## Daily workflow

Register or assign a shared patient, choose a practitioner and eligible service, and book in the practitioner's displayed timezone. Slots are persisted in UTC alongside their local timezone. Working hours, unavailable days and overlaps are checked on the server; competing bookings serialize on the practitioner. Rescheduling retains appointment identity and agreed charge. Cancellation requires a reason. Walk-ins respect the configured schedule and preference.

Starting creates one shared PatientVisit, or uses an explicitly selected active visit for the same patient. A repeated start does not create another encounter. Registration creates identity only: it intentionally does not invoke legacy file-opening or hardcoded consultation charges. Bill the configured service through this workspace; any separate registration fee remains a facility workflow decision.

Clinical notes capture complaint, history, allergies/conditions, examination, diagnosis and plan. Saving checks the record version to prevent overwriting another clinician's edits. Completion signs the author/time and freezes the original narrative. Further corrections are reasoned, append-only amendments. Practitioner/specialty/service names and price are snapshotted; signing and amendments retain author names. Care plans retain revisions with review dates. Follow-up booking can link to the originating consultation.

Standalone prescriptions use shared medicine catalogue entries, do not require stock and print for external fulfillment. Internal fulfillment requires Pharmacy and places the original prescription in its existing queue. Internal diagnostics require the appropriate laboratory/imaging module and shared test catalogue; they create the original request and its linked shared bill. External requests have no internal test bill, can be printed, and accept private PDF/JPEG/PNG reports up to 10 MB. Upload records source and report date. Clinical review is distinct from internal result verification. Once reviewed, further commentary uses an amendment.

Internal referrals are directed to an explicitly authorized receiving staff member. Acceptance grants patient access; rejection does not. Hospital outcomes can reference a real admission for the same patient, but referral never creates an admission. External referrals are printable and outcomes are recorded manually. Notifications are shown in the workspace; no email/SMS or external dispatch is performed.

Cashiers create one consultation bill and record partial/full payments against its remaining balance. Retrying with the same request token does not duplicate a charge or payment. Specialist bills and payments remain in shared finance tables; identifiers use `SBL`/`SPY` plus ULID to avoid shared sequential-number races. Original consultation charge snapshots determine billing. Specialist-origin bills cannot be edited/deleted through generic accounting bill editing. Corrections/refunds require the site's existing controlled finance process; no new refund workflow is included.

## Reporting and history

Activity reports are bounded to a year and to assigned patients. The date range selects consultations by **booking creation date**. Counts include practitioner/specialty/service activity, new/returning visits (prior completed consultation at booking), follow-up due/completed/overdue, care-plan reviews, result review state and referral outcomes. Diagnoses appear only with clinical-read permission. Cashiers see financial amounts with no narrative.

Billed/paid/outstanding figures cover all consultation and internal-diagnostic bills linked to that cohort, with payments received at any date. They are not a payment-date cashbook. Pharmacy dispensing revenue stays in Pharmacy accounting. Use existing accounting reports for period cash reconciliation.

The Specialist history screen displays authorized shared encounters. Disabling Specialist blocks new workspace activity and retains records. Scoped read-only narrative/care-plan/amendment history remains at `/clinical-history/specialist/{patient}` with patient-record module access and clinical-read permission; shared patient history links to it for assigned users. Private downloads and operational orders still require active Specialist access. Re-enabling uses the retained graph.

## Private storage and synchronization

External reports use the private `local` storage disk, not public asset URLs. Downloads recheck assignment and permission, are audited, and set no-store/nosniff headers. Back up this disk with the database; verify writable storage and web-server/PHP upload limits for the configured 10 MB maximum. No public file sharing or automated malware scanning service is introduced.

Specialist extensions, signed notes, assignments and private reports are local to this installation. Automatic observers and manual sync commands reject/skip their dependent clinical/financial roots rather than export an incomplete graph. Existing patient identity synchronization is unchanged. Do not rely on remote/branch synchronization for Specialist records or recovery; it requires a separately implemented and tested exchange contract. Restore database and private files together.

## Verification and facility acceptance

Automated release checks:

```sh
php vendor/phpunit/phpunit/phpunit tests/Feature/SpecialistWorkflowTest.php tests/Feature/InstallationSetupTest.php tests/Feature/PharmacyServicesTest.php tests/Feature/DepartmentUsersTest.php --no-progress
php scripts/verify-specialist-migrations.php
php artisan view:cache
```

The targeted suite passed 64 tests / 329 assertions. It covers package combinations, scope/role denials, retry handling, signing/amendments, snapshots, external and internal order creation, referral acceptance, private files, financial privacy, disabled history, shared encounters and existing pharmacy/staff regressions. It is not a claim that the entire legacy test suite or every physical device was tested.

The MySQL rehearsal copies table definitions only into a unique disposable database, runs both migrations up/down/up, seeds synthetic data, executes booking/clinical/billing/payment retries and launches two competing booking processes. Exactly one competing slot is accepted. It then drops only its uniquely named temporary database. It needs local CREATE/DROP DATABASE privileges; it never copies patient data. It passed against the existing 96-table baseline.

Before live use, the facility must verify:

- Correct package combination, staff permissions, assignments, service prices and practitioner credentials; unrelated roles cannot access private records.
- A synthetic standalone journey through booking, signing, prescription print, external report return/review, referral, partial/full payment and receipt.
- Each enabled receiving workflow: actual pharmacy dispensing, lab/imaging completion and clinician review, or authorized hospital admission/discharge and outcome linkage. Automated tests cover the Specialist handoff, not every legacy downstream screen.
- Physical/online display, long text, chosen timezone (including DST where relevant), mobile layout, browser printing and the site's actual printer/paper configuration. No physical printer/browser automation was available during implementation.
- Backups/restoration, HTTPS/session configuration, private storage permissions and audit retention under the site's existing production policy.

Embedded video, public self-booking, payment gateways, automatic clinical decisions, automated cross-organization exchange and independent commercial subscriptions remain outside the supplied first-release scope.

## Rollback

Disable Specialist in Installation Setup to stop new workspace activity; preserve additive tables, shared references, documents, audit and payments. Existing integrations retain their own module authorization. Re-enable after remediation without reseeding records.

Do not run migration rollback on a database containing new clinical or financial activity. Restoring earlier code may require compatible middleware/model handling for external orders; prefer feature disablement. Full database restoration must account for post-upgrade activity and restore matching private files. The destructive migration down path is rehearsed only in disposable databases.

## Care Network extension

Care Network is the displayed package name for Specialist Care with the optional Partner Network extension. See [Care Network Operations](CARE_NETWORK_OPERATIONS.md) for its migration, activation, onboarding and restricted portal. Existing directory entries still confer neither network approval nor patient access. The original Specialist clinical workflows remain available independently.
