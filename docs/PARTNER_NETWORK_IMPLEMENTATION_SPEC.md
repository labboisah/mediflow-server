# Partner Network: implementation specification

Status: hosted Partner Network implemented as the Care Network package extension. See [Care Network Operations](CARE_NETWORK_OPERATIONS.md) for deployment and supported boundaries.
Prepared: 2026-09-29.
Source: user-supplied **Partner Network, Registration and Onboarding**, sections 33-57.
Depends on: [Specialist Package Implementation Specification](SPECIALIST_PACKAGE_IMPLEMENTATION_SPEC.md) and [Specialist Operations](SPECIALIST_OPERATIONS.md).

## Implementation record

Care Network is the displayed package name; `specialist` remains the canonical key and `care_network` is accepted as an alias. Specialist Care and Partner Network remain separate module/workspace areas.

Implemented in `app/Services/PartnerNetwork`, `app/Http/Controllers/PartnerNetwork`, `resources/views/network` and `routes/partner-network.php`, with migration `2026_09_30_000001_create_partner_network.php`, focused `PartnerNetworkSeeder`, `partners:check` and `partners:deliver` commands.

The hosted implementation covers provider reuse, configurable requirement templates, invitations, independent approval, agreement acknowledgement, service/location offerings, separate external authentication, memberships/case transfer, selected-data disclosure versions, three original-order handoffs, event/report versions, origin review, revocation/cancellation, private files, operational reporting and audit. Agreements independently grant request types, status updates, report submission, document exchange and case messages. Legacy manual flows and saved installation selections are preserved.

Implementation choices: requirements are pinned JSON snapshots on submissions; service offerings carry location/availability rather than introducing a separate facility authority; approved services/capabilities are immutable agreement snapshots; case assignment currently has one responsible partner member with audited transfer. Documents are case/onboarding-owned private records, with explicit manual-release policy disabled by default. Invitations use secure copy-link distribution; the outbox handles optional minimal case-availability notices. No automated credential verification or malware scanner is claimed.

Hosted means one originating organization per installation. Automatic federation, remote provider registry matching, pharmacy partner fulfillment and settlement remain outside this release. The remaining design sections preserve the source contract and future extension points; read the operations guide for the exact shipped workflow and deployment acceptance.

## 1. Outcome and delivery boundaries

Extend Specialist with a separate Partner Network workspace for registration, invitations, verification, agreements, directory search and clinical collaboration. Initial types are Diagnostic Center, Hospital and Specialist. Types, services and verification requirements remain configurable. A partner is an independent party, not an internal department, owned subsidiary or automatically provisioned MediFlow installation.

Specialist Care owns patient identity, consultation, clinical decisions, original orders, review and follow-up. Partner Network owns relationships and permitted exchange around those orders. Standalone Specialist must continue working with zero partners or the network module disabled.

Deliver in two boundaries:

1. **Hosted network:** the originating installation hosts onboarding and a restricted authenticated partner portal. External staff see explicitly shared cases. This release includes actual partner submission/response; a directory and printable referral alone do not complete the requirement.
2. **Federated network:** separate MediFlow installations exchange identity and clinical messages through a separately authenticated protocol. Preserve extension points, but do not claim federation, global identity discovery or shared tenancy from existing synchronization.

The hosted release supports one originating organization per installation and multiple independent partners. Its relationship model permits multiple partnerships per provider. Cross-installation identity reuse requires verified remote identifiers or a future authoritative registry; disconnected databases cannot guarantee global deduplication.

Embedded video, commissions, partner settlement, subjective provider rankings and automatic clinical decisions are outside this extension. Section 56's partner-pharmacy branch is a future type/capability extension. Existing internal/external prescribing continues; pharmacy onboarding/fulfillment is not silently included in the initial three types.

## 2. Verified repository baseline

Inspection covered models, migrations, services and routes for organization/facility/provider registration, invitations, consent, onboarding, documents, referrals, permissions, audit and notifications. No general organization/facility registry, partner invitation service, partnership agreement workflow or dedicated consent model was found in those inspected areas. Recheck before implementation in case intervening work adds reusable functionality.

| Existing anchor | Reuse and required extension |
| --- | --- |
| `app/Models/CollaborationPartner.php` | Simple directory identity with name/type/contact/active flag. Extend rather than introduce a second provider directory. |
| Specialist `SetupController` | Existing partner editing; move network lifecycle commands to the new workspace while preserving legacy directory behavior. |
| `SystemBranding`, singleton system settings | Local installation branding, not a tenant registry. Reference the existing authority rather than duplicate editable branding. |
| User, Role, Permission, ModuleUserAccess | Authentication and explicit grants; add scoped external memberships, not internal clinical roles for external users. |
| SpecialistProfile, Specialty | Internal practitioner profiles and specialty catalogue. Do not create internal staff/profile records merely to register an external practitioner. |
| PatientReferral | Original referral, consultation, destination and outcome. Add routing/collaboration references and retain the same ID. |
| InvestigationRequest | Original request, external destination, partner reference and clinical review. Preserve internal laboratory/imaging behavior. |
| SpecialistDocument and WorkspaceController | Private storage/download and report provenance. Onboarding evidence needs its own ownership context because current documents require a consultation. |
| SpecialistWorkflow | Original orders and review; extract shared commands where needed instead of reproducing clinical or billing workflows. |
| SpecialistAccess | Internal patient assignment. Never grant this broad scope to an external member as a portal shortcut. |
| SpecialistSyncBoundary | Existing local-only clinical graph; retain until a complete federation contract is implemented and verified. |
| AuditLog and audit infrastructure | Extend lifecycle/disclosure/access events without logging complete clinical payloads or invitation secrets. |
| Auth email verification/Laravel delivery | Reuse authentication; verify actual queue/mail configuration before adding after-commit partner notifications. No equivalent partner invitation workflow was found. |
| Module/config/route registry and InstallationSetup | Add module, navigation and route enforcement; preserve installation-admin setup access and saved selections. |

Current `collaboration_partners.is_active` only controls directory availability. It does not prove verification, approval, agreement, consent or permission to exchange records.

## 3. Capability, roles and access

Proposed module key: `partner_network`, dependent on `specialist`. Do not require local laboratory, radiology, wards/beds or doctor modules for external collaboration. Include the network in freshly applied Specialist preset suggestions after release; never enable it silently for saved installations. It can be disabled independently of Specialist Care.

Use registry-driven **Partner Network** navigation for internal staff and a separate **Partner Portal** shell. Internal routes require active capability, user module access, action permission and record scope. Portal routes require active capability, authenticated/verified identity, active membership, valid relationship, assigned/intake-authorized case, valid disclosure and action permission. External members receive no internal patient-record module grants or originating sidebar.

Onboarding is a separate restricted surface: a prospective partner can complete its own submission without clinical access. Installation Setup retains its existing authorization. No new licensing/mandatory activation requirement is introduced.

Suggested internal permissions: `partner.read/register/edit/invite/verify/approve/suspend`, `partnership.manage`, `partner_service.manage`, `partner_requirement.manage`, `partner_collaboration.create/read/manage`, `partner_result.receive`, `partner_report.read` (slash-separated actions denote individual permission names).

Suggested bundles: coordinator, verifier, approver, relationship manager and network reporting. Reuse clinical permissions for ordering/review; administrative approval is not clinical authority. Do not automatically grant all permissions to every specialist or department head. External organization administrator and case-worker roles operate through partner membership and case scope. Managing partner staff does not automatically permit viewing every case.

## 4. Identity and schema contract

Separate provider identity, onboarding, partnership and case access. Keep CollaborationPartner as the canonical local directory identity. One user can belong to multiple partners; a partnership does not own the provider.

Reuse any canonical organization registry found at implementation time. If none exists, evolve the existing directory into a party registry with one distinguished local-organization anchor (stable UUID and `is_local`; not selectable as its own partner). Its display data derives from system branding. Partnership references that source anchor and a provider entry. This establishes stable identity without inventing another editable organization authority or claiming general tenant isolation.

| Proposed record/extension | Fields and invariants |
| --- | --- |
| `collaboration_partners` | Unique UUID, partner_type_id, practice/organization or individual name, structured contacts/address, title/subspecialty/registration reference where applicable, consultation modes, identity status, optional verified remote identity. Retain legacy contact. |
| `partner_types` | Unique stable code, label and active flag. Seed diagnostic_center, hospital, specialist; use a lookup rather than database enum. Legacy clinic/pharmacy/other remain directory-only until supported. |
| `partner_locations` | Partner FK, address/contact/timezone and active flag. |
| `partner_services` | Partner FK, configurable service/category, optional specialty/catalogue mapping, locations, advertised availability. Not automatically a local billable Service row. |
| `partnerships` | UUID; source-party/partner FKs; lifecycle status, purpose, approval actor/time, effective/expiry dates and version. Unique source/partner relationship; renew through agreement versions. |
| Service/capability grants | Partnership FK, approved service/location, capability code and validity. Only explicit approved grants authorize collaboration. |
| `partner_invitations` | UUID; partnership/contact/type/purpose; token hash; inviting actor/time; expiry; accepted/revoked timestamps; status; separate delivery state; idempotency key. Never store reusable raw tokens. |
| `partner_onboarding_submissions` | Partnership, revision, submitter/time, registration snapshot, pinned requirement-template version and review state. Submitted revisions immutable. |
| Requirement templates/checks | Versioned per-type field/document/verification/agreement requirements; required flags; evidence, reviewer, decision/reason/time. |
| Agreement versions | Partnership, version/reference, approved services/capabilities snapshot, validity, both parties' acknowledgement, documents and change reason. Preserve previous terms. |
| `partner_memberships` | Partner/user FKs, external role, status and verification provenance; unique partner/user. No patient scope by itself. |
| `partner_collaborations` | UUID; partnership; exactly one original InvestigationRequest or PatientReferral FK; consultation; required service/capability; transport; status/version; idempotency key; sent/acknowledged/accepted/completed timestamps. |
| Case assignments | Collaboration/member pair, role, validity and revocation; never an unrestricted patient grant. |
| `partner_disclosures` | Collaboration; immutable selected-field/document packet version; purpose; consent/authorized-basis reference; authorizer/time; validity/revocation; recipient/agreement snapshot. |
| Case events | Unique event UUID, collaboration, transition/type, actor/source, occurred_at/received_at, prior/new version and minimal outcome data. |
| `partner_submissions` | Collaboration, result/report/update kind, immutable version/superseded reference, source author/time, clinical content/documents and origin review state. |
| Private partner documents | Validated exclusive onboarding/agreement/submission/disclosure owner; uploader/source, MIME/size/hash/private path, version, scan state and timestamps. Do not accept arbitrary polymorphic class names from clients. |
| Delivery outbox | Unique event UUID, destination/channel, payload reference, attempts/retry time, error classification, delivery state/time. Reuse a proven existing outbox if available. |

Use FKs and indexes for source/partner/status, memberships, original orders, due dates and events. Enforce exactly-one-order linkage with validation and a supported database check constraint. A unique active dispatch identity prevents retry duplicates; rerouting requires audited cancel/supersede, not simultaneous active destinations.

Reuse an existing User only after verified identity matching; email similarity cannot grant membership. Match providers by verified stable identifiers first; similar names/addresses/credentials trigger authorized review. Do not expose another organization's private registrations through duplicate search. A remote URL or matching email domain is not verified organizational identity.

## 5. Onboarding state machines

Onboarding/approval belongs to a partnership, so organization A's approval does not activate organization B's relationship. Provider identity may independently be archived. Clinical eligibility checks all states rather than one label.

| Transition | Preconditions and actor |
| --- | --- |
| Prospective -> Invited | Coordinator identifies contact/type/purpose and issues an expiring invitation. |
| Invited -> Registration Submitted | Verified invitee accepts and submits requirements. Assisted registration records assistance/source; it cannot fabricate partner acknowledgement. |
| Registration Submitted -> Under Review | Verifier reviews the pinned submission/template version. |
| Under Review -> Registration Submitted | Corrections requested with reason; retain prior revision. |
| Under Review -> Approved / Rejected | Mandatory checks resolved; approver records decision/reason. Approved still has no clinical exchange access. |
| Approved -> Onboarding | Agreement, approved capabilities/services and memberships prepared. |
| Onboarding -> Active | Current acknowledged agreement, validity and mandatory verification satisfied. |
| Active -> Suspended / Inactive / Terminated | Authorized reason/time; immediately stop protected portal access and new disclosures. |
| Suspended / Inactive -> Under Review | Explicit reactivation; recheck expired evidence and terms. |
| Rejected -> Registration Submitted | Authorized reopening with new revision/reason. |
| Terminated | Remains historical; renewed participation requires reviewed agreement/onboarding revision, never delete/recreate to evade history. |

Invitation states are separate: pending, accepted, expired, revoked. Resend revokes unused earlier tokens. Acceptance is transactional, single-use and idempotent; concurrent clicks cannot duplicate membership. Bind redemption to the intended verified account, rate-limit issue/redeem, prevent email enumeration and require renewed verification for a changed address. Invitations carry no clinical data.

Requirements differ by partner type and are versioned. The organization supplies professional/organizational evidence and agreement policy; this specification does not prescribe jurisdiction-specific legal requirements. By default a submitter cannot approve their own submission. Material identity/service/capability changes require review. New template requirements trigger an explicit review/effective-date policy rather than silently rewriting past approval.

Agreements use UTC, inclusive start and exclusive end. Recheck expiry on every sensitive action; scheduled reminders/status updates are not an authorization boundary. Suspension blocks reads/downloads/writes on open cases while origin staff retain history and can explicitly reroute. Previously downloaded documents cannot be technically recalled; revocation stops future system access.

## 6. User interfaces

Registration captures partner type, practice/organization, contact person, phone/email, address/locations, specialties, services and notes. Individual specialists also supply name, title, subspecialty, practice affiliation, modes and policy-required professional details. No stock, internal employment or internal clinical role is needed to register a provider.

Internal screens: dashboard, directory, registration/details, invitations, review queue, evidence checklist, agreement/capability editor, membership management, collaboration queue and reports. Distinguish provider, onboarding and relationship status; explain ineligibility without exposing confidential evidence to clinical directory users.

Onboarding screens: accept invitation, verify account, enter profile/services/locations, upload evidence, respond to corrections and acknowledge terms. Partners propose service changes but cannot approve their own relationship or expand approved permissions.

Clinical directory filters eligible relationships by type, service, specialty and location. Administrative search can include clearly marked prospective/inactive records. Advertised availability is descriptive, not a reservable live slot unless an authenticated scheduling integration exists.

Partner portal: assigned incoming cases, minimum patient context, pending actions, documents, reports and case communication. Authorized intake staff may see an explicitly agreed minimum triage packet before acceptance; further selected details require acceptance/assignment. No full patient directory, unrelated encounters, global clinical search, originating balances or internal workspace.

## 7. Routing and disclosure

Offer three explicit choices:

- **Internal service:** existing same-organization installed queue and financial behavior.
- **MediFlow partner:** active approved relationship with matching service/capability; hosted portal first.
- **External provider:** existing print/manual return without network approval or portal-access claims.

Keep clinical `destination_type` (hospital/specialist/etc.) and introduce independent `routing_method` (internal/partner/external). A partner diagnostic request remains external to the local lab queue (`is_external=true`) with partner routing/collaboration references. Never silently downgrade a failed partner delivery to external routing.

Dispatch requires active capability, clinical permission/patient scope, current approved partnership, eligible receiving membership/intake configuration, matching service/capability and authorized disclosure. Reuse consent mechanisms if available; otherwise implement versioned disclosure authorization under the site's policy before clinical release. A prechecked consent box is insufficient.

Preview exactly which demographics, clinical summary and documents will be shared. Create an immutable packet; portal reads never serialize unrestricted live Patient/Consultation records. Later clinical edits require a new authorized packet/version to be shared. Record purpose, basis/evidence, actor, recipient, agreement and validity. Patient matching at the partner is explicit; origin identifiers confer no patient-search rights.

Enforce the intersection of installation capability, actor identity, membership, agreement, capability, case assignment/intake permission, disclosure and action permission on pages/actions/search/counts/downloads/exports/workers/notification links. Cross-partner and cross-case IDs fail even for the same patient. External referral acceptance must not call the existing internal workflow that grants patient-level SpecialistAccess.

## 8. Clinical collaboration workflows

### Diagnostic partner

Original InvestigationRequest from consultation -> authorized packet/dispatch -> delivery acknowledgement -> Accepted or Declined -> Scheduled where needed -> In Progress -> Result Submitted -> origin clinical review/completion. Separate delivery, clinical progress and review states.

The partner returns acknowledgement, appointment/performance times, structured results where supported and source-attributed reports/attachments. Preserve original consultation/request links and submission versions. External content is not an internally verified laboratory result. Corrected results supersede earlier versions, alert the origin and require renewed review without deleting prior review. Duplicate event/submission IDs return the previous result; ambiguous patient/request matches require reconciliation.

### Hospital partner

Original PatientReferral -> acknowledgement -> Accepted/Declined -> Presented -> Admission where appropriate -> care updates -> Discharged -> discharge report/follow-up -> origin review/continued care. Outpatient assessment without admission is also a valid outcome.

The partner owns admission and care. Remote admission references are partner-reported data, never local `outcome_admission_id` FKs or automatically created beds/admissions. Internal referrals continue linking actual local Admission records. Retain discharge documents, source identity and reported dates.

### Specialist partner

Original PatientReferral -> Requested -> Accepted/Declined -> Scheduled -> In Progress -> Report Submitted -> Completed/reviewed. Findings, diagnosis, recommendations, treatment and follow-up remain attributable to the receiving specialist. They never overwrite the origin's signed note; the origin continues through amendments, follow-up or another consultation.

### Shared transitions, communication and reliability

Cancelled and Declined are explicit alternatives with reason. Completed work is immutable except versioned corrections. Restrict transitions by actor/current version and lock the collaboration for changes. Events carry stable UUID, source, occurred/received timestamps and expected version; reconcile stale/out-of-order events rather than overwrite state. Case messages are scoped, attributable, audited and cannot disclose new records outside the authorized packet.

Create collaboration and outbox event atomically. Dispatch after commit with bounded retry/backoff, separating queued/sent/acknowledged/failed. Delivery failure is not clinical decline. Email contains minimal context and an authenticated link, not clinical narratives, attachments or reusable credentials. Provide retry/reconciliation screens. Migrations and seeds send no invitations/messages.

Shared bills/payments remain authoritative. Dispatch creates no automatic local diagnostic bill, partner commission or settlement. Any origin referral/admin fee is an explicitly configured shared service with an authorized billing action. Retries cannot duplicate charges. Pharmacy stock and dispensing remain unchanged.

## 9. Files, audit, history and reporting

Reuse private storage conventions with onboarding/agreement/case ownership. Validate content-derived MIME, extension, size and authorization; generate storage names and reject uploaded paths. Proposed initial limit matches Specialist: PDF/JPEG/PNG, 10 MB. Quarantine until the configured scanning policy permits sharing and expose recoverable failure states. If scanning is unavailable, require an explicit accepted deployment policy before enabling file exchange; never label unscanned files clean. Authorize each download, use no-store/nosniff headers and no public asset URLs. Clean up orphaned uploads after failed transactions.

Audit registration, invitation, submission/revision, verification, approval/rejection, agreement changes, activation/suspension, membership, disclosure/revocation, case access/download, response, result/report, review and rerouting. Include actor/partner/source, target/time/version/action; exclude complete clinical packets and secrets from general logs. Origin history connects original orders, partner reports/events and reviews with clinical scope. Administrative lifecycle views do not expose narratives.

Dashboards show directory providers, active relationships, onboarding/approval backlog, suspensions, recent referrals, pending cases, diagnostic/hospital/specialist work and overdue actions. Distinguish provider counts from relationship/case counts. Partner counts use the same scopes as reads.

Record sent/accepted/completed/declined/pending counts, first-response time, accepted-to-completed time and diagnostic performed-to-submitted turnaround. Define timestamps and denominator, exclude declined/cancelled cases from completion averages, show sample size and separate event time from receipt time. No subjective rankings. Reports filter authorized partner/type/service/date; clinical narratives and revenue require separate permission. Index timestamps even if advanced comparisons follow later.

## 10. Migration and compatibility

1. Inventory schema and legacy references; reconcile identities and local source mapping before adding anchors. Reuse any new canonical registry/catalogue found.
2. Add typed identity/relationship/onboarding/agreement/membership/case/disclosure/events and nullable routing references. Create migrations after the deployed Specialist migrations; do not rewrite already-applied migrations.
3. Map legacy types, for example diagnostics -> diagnostic_center, while preserving history. Existing active directory entries require network review; never auto-approve agreements or provision external accounts.
4. Backfill internal/external routing from actual existing semantics. A legacy partner FK alone must not turn a manual request into a network case or disclose historical records.
5. Apply focused repeatable module/type/permission seeds. Preserve saved module selections and user assignments. No broad user/catalogue reseeding or unsolicited invitations.
6. Keep manual external printing/return available and show network destinations only when eligible. Zero-partner installations retain the complete standalone journey.
7. Extend scoped history references. Network disablement stops portal access/new dispatch while retaining origin history and Specialist manual workflows. Re-enable without duplicating invitations/cases or reviving expired agreements.

Retain SpecialistSyncBoundary; generic synchronization is not a clinical network transport. Hosted portal and future federation use explicit disclosure contracts. Operational rollback disables the module/dispatch workers and preserves additive data, private files and audit with compatible code. Never reverse migrations containing clinical exchange; rehearse down migrations in disposable databases only.

## 11. Implementation sequence and ownership

| Phase | Work | Exit evidence |
| --- | --- | --- |
| 0. Reconciliation | Identity/auth/documents/consent/delivery reuse, source mapping and hosted deployment boundary. | Confirmed schema/permission map without duplicate identity authorities. |
| 1. Foundation | Module, identity/types/services/relationships, scoped management UI and focused seeds. | Register/search providers; standalone regression passes. |
| 2. Onboarding | Invitations, verified identity binding, requirements/evidence/review/agreements and lifecycle. | Mandatory checks and token races/replay covered; only eligible relationships activate. |
| 3. Portal boundary | Membership, isolated shell, assignments, disclosure policies, private files and audit. | Negative cross-partner/case tests pass before clinical dispatch. |
| 4. Clinical routing | Three routes, original-order references, diagnostic/hospital/specialist workflows. | Reports return to origin review without duplicate orders, admissions or bills. |
| 5. Reliability/operations | Outbox, notifications, reconciliation, dashboards/metrics, expiry/revocation and readiness command. | Failure/retry/correction/concurrent transitions and expiry verified. |
| 6. Release | Legacy MySQL rehearsal, combinations, browser/accessibility/files, deployment/rollback docs. | Hosted-release acceptance matrix complete. |
| Later: federation | Verified remote identities, mutual trust, authenticated exchange, replay protection and patient matching. | Separate protocol review and two-installation acceptance; not implied by hosted release. |

Proposed code ownership:

- `app/Services/PartnerNetwork/*`: lifecycle, eligibility, disclosure, routing and delivery commands.
- Partner models and policies: relationship, membership, case and document scope.
- `app/Http/Controllers/PartnerNetwork/*` and distinct portal controllers/policies.
- `routes/partner-network.php`, `routes/partner-portal.php`, modern internal views and a limited portal shell.
- `PartnerNetworkSeeder`, after-commit delivery jobs, expiry/reconciliation commands and configuration readiness checks.

These are proposed paths, not existing APIs. Reuse Specialist commands at integration boundaries; do not clone Patient, Bill, Investigation or clinical workspaces.

## 12. Acceptance matrix

| ID | Scenario | Required result |
| --- | --- | --- |
| PN01 | No partners or module disabled | Existing Specialist care/manual orders/billing work unchanged. |
| PN02 | Three initial types and another configurable type | Type-specific requirements; no duplicate internal provider/staff authority. |
| PN03 | Existing provider/MediFlow identity | Verified match reused; ambiguity reviewed; no private registry leak. |
| PN04 | One provider, multiple partnerships | Agreements/capabilities/states and case visibility independent. |
| PN05 | Invitation replay/expiry/resend/concurrency | One redemption; revoked/expired/wrong-account attempts fail. |
| PN06 | Corrections/rejection/approval | Immutable revisions; evidence and independent approval enforced. |
| PN07 | Approved but inactive; future/expired agreement | No protected dispatch/read/submission despite scheduler delay. |
| PN08 | Directory and forged service/capability | Only eligible destination choices accepted server-side. |
| PN09 | Diagnostic journey | Same original request; returned versions and distinct origin review. |
| PN10 | Hospital journey | Presentation, remote admission/discharge and follow-up without local bed creation. |
| PN11 | Specialist collaboration | Scoped scheduling/report and continued care preserve authorship. |
| PN12 | Portal isolation | Cross-partner/case IDs, same-patient other case, unassigned member, internal URLs and search/export/download fail. |
| PN13 | Disclosure/revocation | Preview matches snapshot; no full-record serialization; future access stops on revocation. |
| PN14 | Private files/corrected reports | Validation/scan policy, no public URL, cleanup and prior review retained. |
| PN15 | Concurrent dispatch/status/submission/retry | Unique dispatch, no lost updates or duplicate result/bill; replay returns previous response. |
| PN16 | Delivery outage/out-of-order event | Outbox retries; failure distinct from decline; reconciliation available. |
| PN17 | Suspension/termination during care | Partner access stops immediately; origin retains history and can reroute explicitly. |
| PN18 | Dashboard/reporting | Scoped counts reconcile; timestamps/denominators defined; no clinical leakage. |
| PN19 | Legacy migration/disable/re-enable | Manual history preserved; no auto-approval, historical disclosure or duplicates. |
| PN20 | Existing regression | Installation/roles, internal pharmacy/diagnostics/hospital, finance/printing and local-only sync remain intact. |
| PN21 | Browser/operator acceptance | Both shells, mobile/long text, redemption, private downloads/printing and configured delivery verified with synthetic data. |

Add focused registration/onboarding/invitation, agreement authorization, disclosure, portal isolation, three clinical handoff, document and concurrency suites. Retain Specialist/Installation/Pharmacy/Department regressions. Supplement SQLite tests with disposable MySQL migration/concurrent-write tests. The prior Specialist 64-test result is not evidence that this proposed network passes these cases.

## 13. Decisions and release gates

| Decision | Proposed default / gate |
| --- | --- |
| Identity | Extend CollaborationPartner; reuse any newer canonical organization registry. No global duplicate claim without verified authority. |
| Transport | Hosted authenticated portal first; federation is separate work. |
| Consent/basis | Reuse established mechanism or implement versioned disclosure authorization under site policy before clinical release. |
| Requirements/agreements | Per-type versioning and independent approval by default; organization supplies evidence rules and terms. |
| External accounts | Verified User plus membership; prove legacy-route isolation or use a separate authentication guard. |
| Delivery | Configured verified mail/queue; audited secure copy-link fallback must not be reported as confirmed delivery. |
| Scanning/retention | Deployment policy must be explicit; storage permissions alone do not prove scanning or legal compliance. |
| Corrections | Immutable submissions/review history, superseding versions and origin acknowledgement. |
| Charges | No automatic diagnostic charge, commission or settlement; explicit configured shared-service fee only. |
| Availability | Descriptive until validated scheduling integration exists. |

Deployment does not automatically activate partnerships, send invitations or grant staff roles. Exact production credentials, partner evidence policies and agreement terms remain installation inputs, not invented defaults.

## 14. Source coverage

| Supplied sections | Specification coverage |
| --- | --- |
| 33-35: network/types/registration | Sections 1-4 and 6. |
| 36-39: onboarding/invitations/verification/agreements | Sections 4-5 and 13. |
| 40-42: services/directory/capabilities | Sections 3-4 and 6-7. |
| 43-46: routing and clinical workflows | Sections 7-8. |
| 47-48: patient scope/portal | Sections 3, 6-7 and PN12-PN14. |
| 49-50: existing providers/multiple partnerships | Sections 1-2, 4 and 10. |
| 51-54: dashboards/performance/audit/permissions | Sections 3 and 9. |
| 55-56: Specialist boundaries/connected care | Sections 1, 7-8; future pharmacy extension explicitly bounded. |
| 57: inspect/reuse/standalone compatibility | Sections 2, 10-13. |
