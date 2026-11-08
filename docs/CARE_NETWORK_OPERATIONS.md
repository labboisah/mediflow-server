# Care Network: deployment and operations

Care Network is the displayed package name for Specialist Care plus the optional Partner Network. The canonical stored key remains `specialist`; `care_network` is an accepted alias. Existing package/module assignments and branding overrides are preserved. The `specialist` workspace remains Specialist Care; network coordination is a separate module, `partner_network`.

## Implemented hosted release

Internal workspace: `/care-network`. External workspace: `/partner-portal/login`.

The hosted release provides a reusable provider directory, configurable types/checklists, registration, expiring single-use invitations, versioned submissions, independent approval, agreement acknowledgement, activation/suspension, services/locations, external memberships, clinical intake/assignment, selected-data disclosures, diagnostic/hospital/specialist reports, corrected versions, origin review, private documents, operational counts/timing and audit.

It uses existing PatientReferral and InvestigationRequest records. External admission references never create a local admission or bed. Partner dispatch does not create local diagnostic charges or settlement. Original clinical notes remain authoritative. Partner results use their own immutable submissions and review history rather than becoming internally verified lab results.

One originating organization is supported per installation. Provider identity is separate from partnership and membership. Existing directory providers can be reused; no global remote-provider search or authenticated federation is implemented. Partner records and patient data remain subject to SpecialistSyncBoundary. Do not enable generic synchronization as a substitute for clinical exchange.

## Upgrade

Back up database/private files and follow the site's maintenance procedure. Specialist migrations must already be applied. On the target installation:

```sh
php artisan migrate --path=database/migrations/2026_09_30_000001_create_partner_network.php --force
php artisan db:seed --class=PartnerNetworkSeeder --force
php artisan optimize:clear
php artisan view:cache
php artisan partners:check
```

Use the existing deployment process for route/config caches and the scheduler. The focused seed creates types, role bundles and one local identity anchor; it does not create partner accounts, approve providers, enable the module or send invitations. Existing directory `is_active` does not constitute network approval.

Select Care Network or add Partner Network in Installation Setup, review dependencies and save. Existing explicit module selections remain authoritative. The setup screen remains accessible to installation administrators.

## Provision internal staff

Assign roles through existing User Management and active `partner_network` module access, plus existing modules required by each person's clinical duties:

| Bundle | Responsibility |
| --- | --- |
| `network_coordinator` | Directory, prospective registration, invitations and offered services. |
| `network_verifier` | Submission/evidence review and type/checklist configuration. |
| `network_approver` | Independent approval or rejection. Cannot approve a relationship they registered or submitted. |
| `network_manager` | Agreements, activation, suspension, services, membership suspension/reactivation and operational reports. |
| `network_clinician` | Case dispatch/read, disclosure management and returned reports. Also needs existing Specialist patient assignment and clinical order/review permissions. |

Roles can be combined where appropriate, but the independent-approval rule still applies. Administrative network roles do not grant patient narrative access. No existing account receives these privileges automatically.

## Onboard a partner

1. Search the directory first; reuse the same provider identity when appropriate. Register a prospect with type, contact and partnership purpose.
2. Create an invitation to the intended representative. Copy the one-time link and share it through your approved channel. The application does not claim that a copied link was delivered. Links expire after seven days; resending revokes unused prior links.
3. The invited person verifies possession of the link and matching email. Existing accounts must authenticate with their existing password. New external accounts require at least 12 characters and are restricted to the Partner Portal.
4. An onboarding administrator submits contact/professional details, services/locations and private evidence. The submission pins the current requirement template; later corrections create another revision.
5. A verifier starts review. An independent approver confirms all pinned requirements and records evidence/reason before approval.
6. A manager creates an agreement with reference/terms, request types, separate status-update/report-submission/document-exchange/case-message capabilities, approved services and effective/expiry timestamps. The partner administrator acknowledges it. The manager explicitly activates the relationship after prerequisites are satisfied.
7. Invite an active **intake** member to receive cases. A solo practitioner can explicitly receive the combined **administrator and clinical intake** role to onboard and manage assigned care using one account. Onboarding administrators can invite colleagues but cannot browse clinical cases solely because they manage membership. Intake members accept cases and may transfer them to an active intake/worker member of the same partner.

All timestamps are interpreted in the application's configured timezone and persisted through the normal application date handling; configure that timezone before onboarding. Availability text is descriptive, not live scheduling. Operational staff must verify actual credentials/agreements under local policy; checkbox confirmation is an auditable human review, not automated credential verification.

## Clinical use and privacy

From an assigned Specialist consultation choose **Refer to a Care Network partner**. Select an approved service/request type. The preview shows the patient name, origin reference, service and summary to be shared. Record purpose and authorization evidence and explicitly confirm. Full patient history is never automatically exposed to a partner.

The original order and network case are created in one transaction. The portal receives the selected snapshot. Partner acceptance grants case assignment only, never internal patient-level access. Partner workers record status, messages and results/reports. Corrected reports create versions and reopen origin review while preserving the earlier signed review.

The origin can revoke access, publish a newly authorized disclosure version or cancel before rerouting. Suspension, expired agreements and revoked disclosures are checked on each protected request. Disabling Partner Network blocks the portal/new network operations while retaining Specialist's standalone manual workflows and internal historical records. Files already downloaded cannot be recalled.

Hospital case events can record presentation, partner-reported admission and discharge, followed by a discharge report. The receiving facility remains responsible for its actual admission/care. The origin reviews returned information and continues follow-up through Specialist Care.

## Documents and delivery

Files use private `local` storage: PDF/JPEG/PNG up to 10 MB. Uploads are quarantined. Set `partner_network.allow_manual_document_release` in `config/partner_network.php` only after adopting a documented manual file-review procedure. Authorized reviewers can then release a file with recorded evidence. No malware scanner is claimed or installed. Until that policy is configured, partners cannot download quarantined files; text reports and onboarding still function. Clinical attachments uploaded by originating staff require explicit sharing authorization. Back up private files with the database.

The authenticated portal is the primary delivery surface. An outbox records availability and supports bounded optional email-notice retries. `partners:deliver` is scheduled every minute when the network module is enabled; the server must run Laravel's scheduler. Notices are disabled by default. Enable `partner_network.notifications_enabled` only after verifying the installation's mail transport. Notices contain an authenticated portal link and no patient narrative. Delivery is at-least-once; after a crash, a minimal notice may repeat, but the clinical case/submission is not duplicated.

After five failed attempts, the outbox keeps a failed record and generic diagnostic message for an operator. Check agreement validity/mail configuration and retry with `php artisan partners:deliver --retry-failed` after resolving the cause. Portal case access never depends on email delivery. Invitation links are currently distributed manually rather than by the notice outbox.

## Reporting and verification

Reports select cases by dispatch creation period (maximum one year), aggregate partner/type/current state, and show local receipt-based mean time to acceptance and accepted-to-reviewed completion with sample counts. They contain no patient narrative or subjective quality ranking. Event timestamps retain the basis for later detailed turnaround analysis.

```sh
php vendor/phpunit/phpunit/phpunit tests/Feature/PartnerNetworkTest.php tests/Feature/InstallationSetupTest.php tests/Feature/PharmacyServicesTest.php tests/Feature/DepartmentUsersTest.php --no-progress
php scripts/verify-specialist-migrations.php
php artisan partners:check
```

Verified on 2026-09-29: **84 tests / 401 assertions passed**, Blade views compiled, and the disposable MySQL up/down/up, synthetic journey, competing booking and competing invitation-redemption checks passed. The network migration and focused role seeder were applied to the local development installation; no production server deployment is implied.

PartnerNetworkTest inherits the Specialist workflow fixture/tests, so the command includes Specialist regression coverage. The MySQL script uses only table definitions and synthetic data in a uniquely named disposable database. It rehearses all three additive migrations, clinical/finance retries, onboarding, concurrent booking and invitation redemption. It never copies application patient records and removes its own temporary database.

Before live use, verify staff roles, partner credentials/terms, privacy authorization policy, document handling, timezone, private-file backups/restoration, mail/scheduler if enabled, and browser/print/download behavior with synthetic cases. No physical printer or browser automation certification is implied by backend tests. Automatic cross-installation exchange, global identity verification, automated file scanning, partner settlement and partner pharmacy fulfillment remain outside this hosted release.

## Rollback

Disable Partner Network and its delivery processing while preserving all additive tables, documents, disclosures, submissions, audit and original orders. Restore compatible code if necessary. Do not run migration down on a live network that contains clinical history. Re-enable after remediation without reseeding or automatically approving relationships.

## Initial operational administrator

The installation super administrator uses **Installation Setup > Administrator registration** to choose the administrator's name, email and confirmed password. Existing linked administrators can be updated there; blank password fields preserve the password. Brand/package saves do not change credentials. See [Installation Setup](INSTALLATION_SETUP.md) for details.
