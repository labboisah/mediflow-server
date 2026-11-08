# Specialist Package: Implementation Specification

Status: implemented local-installation release; deployment configuration and operational acceptance are documented in [Specialist Operations](SPECIALIST_OPERATIONS.md).
Prepared: 2026-09-29.
Source: user-supplied **MediFlow - Specialist Package Integration**, sections 1-32.
Scope: Specialist as the seventh package, with optional companion packages.

This document retains the original requirements and design assessment for traceability. Sections describing the baseline or proposed structure are the pre-implementation contract, not a claim about current code. The implementation record below and operations guide describe the shipped behavior and remaining deployment acceptance.

## Implementation record (2026-09-29)

- Implemented package combinations, dependencies, setup and welcome preset; existing explicit module choices remain authoritative.
- Added configurable specialties, practitioner profiles/schedules, shared-catalogue services, approved partners and scoped patient assignment.
- Added appointments/walk-ins, identity-preserving rescheduling, shared encounters, versioned notes, signed author snapshots, append-only amendments, care plans and follow-up.
- Reused Prescription, InvestigationRequest, PatientReferral, PatientVisit, Service, Bill and Payment. Internal orders keep their original IDs; external issue/print/report return works standalone.
- Added private report upload/download, clinical review, referral acceptance/outcomes, optional links to actual Admission records, scoped shared history and read-only Specialist history when disabled.
- Added separate administrative, clinical, assistant, receiving and cashier bundles, route/record restrictions, action auditing and scoped operational/financial reports.
- Implemented conventional controllers in `app/Http/Controllers/Specialist`, shared commands in `app/Services/SpecialistWorkflow.php`, scope checks in `SpecialistAccess`, and Blade views in `resources/views/specialist` using the modern shell. These replace the proposed Livewire/policy file layout; authorization runs on every command.
- Repeatable role seeding creates no staff accounts, patient records, credentials or service prices. No outbound notification provider was added; queues and dashboard counts are the delivery surface for this release.
- Specialist clinical graphs and documents are local-only. Partial automatic/manual synchronization is blocked; automatic cross-installation exchange and independent branch isolation remain outside this release.
- Automated verification: 64 targeted tests, 329 assertions; disposable MySQL schema up/down/up, synthetic clinical/financial journey and competing slot booking test. Browser, physical printer and facility-specific end-to-end acceptance remain deployment checks, not claimed automated coverage.

Use [Specialist Operations](SPECIALIST_OPERATIONS.md) for exact roles, commands, setup, supported boundaries and rollback. The acceptance matrix below remains the facility rollout checklist; passing the targeted tests does not mean every matrix row has been manually certified.

## 1. Outcome and boundaries

Specialist supports an individual practitioner or group working physically, online or in hybrid mode. Specialties are configurable; practitioners may hold multiple specialties. A patient retains the same MediFlow identity and longitudinal record across authorized packages.

Standalone Specialist must support patient registration, appointments/walk-ins, consultations, clinical documentation, care plans, external prescriptions, external investigations, referrals, follow-up, service billing, payments and reporting. Pharmacy, Diagnostics, Clinic, Maternity, Hospital and Enterprise are optional integrations, never prerequisites.

Reuse the modern shell, patient model, authorization, financial records, audit system, branding and installation lifecycle. Do not create separate Specialist patient, accounting, inventory, dispensing, diagnostic-processing or inpatient systems.

Online care initially means remote scheduling and digital consultation documentation. Embedded video, payment gateways, public self-booking, automated clinical decisions, commercial remote-license enforcement and automatic cross-organization exchange require separate integration work. Approved partner directories, printable requests, controlled document return and manual outcome tracking are included.

## 2. Repository assessment

### Reuse and extension map

| Area | Verified repository anchor | Decision |
| --- | --- | --- |
| Package presets | `config/mediflow_modules.php` | Add Specialist and dependencies; preserve existing keys/aliases. |
| License lifecycle | `app/Services/LicenseService.php`, `app/Models/ClientLicense.php` | Reuse latest-license validity and module overrides; extend package combination representation. |
| Installation/branding | `app/Livewire/Admin/InstallationSetup.php`, `app/Services/SystemBranding.php`, `config/welcome_templates.php` | Extend setup and welcome template; retain installation-admin authorization. |
| Route enforcement | `config/installation_routes.php`, `app/Http/Middleware/EnsureInstallationModules.php` | Add rules and preserve persistent Livewire checks. Separate inpatient from outpatient capabilities. |
| Navigation/permissions | `app/Services/SidebarService.php`, `database/seeders/ModulePermissionSeeder.php`, `app/Models/User.php` | Reuse role, permission and module-access conventions; add record-level scope. |
| Patient identity | `app/Models/Patient.php`, demographic relationships | Reuse identity and identifiers unchanged; no Specialist registry. |
| Encounters | `app/Models/PatientVisit.php` | Reuse visit as encounter anchor; link consultation-specific metadata. |
| Appointments | `app/Models/Appointment.php` | Extend with practitioner, service, mode, location, duration and visit linkage. |
| Notes/history | `app/Models/Continuation.php`, `app/Livewire/Clinical/ContinuationSheet.php`, `app/Http/Controllers/Patient/PatientController.php`, `resources/views/patient/history.blade.php` | Preserve existing notes and extend shared history with linked Specialist events. |
| Prescriptions | `app/Models/Prescription.php`, `app/Models/PrescriptionItem.php`, `app/Livewire/Clinical/PrescriptionWorkspace.php` | Separate clinical issue from optional internal pharmacy submission. |
| Pharmacy | `app/Livewire/Pharmacy/PrescriptionDispenseWorkspace.php`, `routes/pharmacy.php` | Pass original prescription to existing dispensing; do not recreate stock workflows. |
| Diagnostics | `app/Models/InvestigationRequest.php`, `app/Models/InvestigationResult.php`, `app/Livewire/Clinical/InvestigationRequestWorkspace.php` | Reuse internal requests/results; extend with external request mode and report attachments. |
| Referrals | `app/Models/PatientReferral.php` | Extend existing root with structured destination, origin, urgency and outcomes. |
| Hospital | `app/Models/Admission.php`, `app/Models/PatientAdmission.php`, `app/Models/Discharge.php`, Clinical Admission/Discharge workspaces | Reconcile actual operational admission path before linking referrals; do not create a third admission model. |
| Finance | `app/Models/Service.php`, `ServiceRequest.php`, `Bill.php`, `BillService.php`, `Payment.php` | Reuse catalogue, bills/payments/receipts; add origin references and retry protection. |
| Audit | `app/Models/AuditLog.php`, `app/Observers/AuditModelObserver.php`, `app/Models/Traits/Auditable.php` | Reuse audit storage; explicitly instrument clinical actions. |
| Reports/sync | `LicenseService::reportTableEnabled()`, existing reports, `app/Models/Traits/Syncable.php` | Add scoped datasets and verify new-model synchronization coverage. |

### Material gaps

1. **Single preset:** ClientLicense stores one `plan`; setup supports a preset plus explicit module add-ons. Multiple independently subscribed packages are not currently represented.
2. **Installation-level organization:** license lookup selects the latest license; branding uses singleton settings. General tenant/workspace isolation was not established in inspected models. Initial release targets one organization per installation. Department scope is not tenant isolation; the Enterprise branch flag does not prove full branch isolation.
3. **External prescribing:** existing entry can resolve/create medicine catalogue records without stock, but submission currently marks the prescription as submitted to Pharmacy. Standalone issue/print needs separate routing semantics.
4. **External investigations:** the current workspace creates bills from internal catalogue tests, while shared investigation routes require `laboratory|radiology`. Standalone Specialist cannot reuse that route unchanged.
5. **Basic scheduling:** current appointments contain patient/date/time/status/notes/cancellation metadata. Availability, duration and concurrent slot protection need additions.
6. **Basic referrals:** current destination is a department string with acceptance/completion dates. Structured targets, encounter linkage and returned outcomes need additions.
7. **Broad clinical gating:** admissions/discharges share `clinical_care` checks with outpatient care. Specialist must not gain inpatient operations merely by enabling clinical documentation.
8. **Unverified shared infrastructure:** no dedicated specialist profile, general care-plan, organization collaboration or general clinical-document service was established in inspected files. Verify existing migrations/UI before adding overlapping tables.
9. **Patient scope:** role/route checks alone do not establish assignment-based access across every operation. Reads, mutations, searches, downloads, exports and Livewire actions need explicit record policies.

## 3. Package and capability contract

### Registration

Add canonical package and workspace module key `specialist`. Proposed preset:

```php
'specialist' => [
    'label' => 'Specialist',
    'description' => 'Specialist consultations, patient records, care plans, referrals, billing, and reports.',
    'modules' => ['core', 'access_control', 'patient_records', 'clinical_care', 'specialist', 'billing', 'reports'],
],
```

Add `specialist => [patient_records, clinical_care, billing]` to dependencies. Standalone Specialist does not implicitly enable doctor, nursing, pharmacy, laboratory, radiology, maternity, wards/beds, finance administration or branch management. Shared clinical services remain reusable without those workspaces.

Keep existing keys: `pharmacy`, `general_clinic`, `maternity_clinic`, `diagnostic_center`, `hospital`, `enterprise_hospital`. A specialty is clinical configuration, not a package or permission.

### Backward-compatible combinations

Recommended first-release design:

- Add nullable JSON `selected_packages` to `client_licenses`: distinct validated canonical keys. Backfill from the normalized current plan without editing patient data, module assignments or expiry.
- Retain `plan` as the primary package for legacy consumers and automatic welcome-template selection; require it to belong to the selection.
- Add `LicenseService::selectedPackages()` with null/legacy fallback. Suggested modules are the union of selected package presets.
- Keep explicit `client_enabled_modules` authoritative. Package selection changes draft suggestions only; display additions/removals and preserve add-ons unless deliberately removed. Validate dependencies on the final saved selection.
- One installation license controls start/expiry/activation for all selected packages. Independent per-package subscriptions, remote purchase and signed commercial entitlements are not implemented by this extension.
- Preserve latest-license rules, no-license compatibility fallback, and inactive/future/expired behavior. Core/Access Control and authorized Installation Setup remain accessible.
- Change the current primary-plan-only branch-management condition to require Enterprise anywhere in the selected packages.
- Enterprise uses `*`: new known modules can expand a fresh preset. Deployment must not silently enable Specialist for installations with explicit saved module rows. Applying a new preset is an administrator action.
- Removing/disabling packages changes capabilities, not tables or historical data. Validate remaining dependencies. Never reset staff access or void paid bills.

Separate multi-organization hosting and independent subscription lifecycles remain future architectural work, not hidden prerequisites.

### Central capability resolver

Introduce a small `PackageCapabilities` service composed with LicenseService. Capability availability depends on effective modules and configured destinations; execution also checks actor permission and patient scope. Avoid scattered package-name checks.

| Capability | Required modules / configuration | Unavailable behavior |
| --- | --- | --- |
| Specialist consultation | specialist + patient_records + clinical_care | Deny new Specialist operations. |
| Clinical prescription issue | patient_records + clinical_care and eligible prescriber | No inventory requirement; preserve prescribing authority checks. |
| Internal dispensing | pharmacy + patient_records + clinical_care + billing | External prescription issue/print remains available. |
| Internal laboratory | laboratory + patient_records + billing | External laboratory request. |
| Internal imaging | radiology + patient_records + billing | External imaging request. |
| Hospital referral destination | clinical_care + wards_beds + patient_records and receiving workflow | External hospital referral. |
| Maternity collaboration | maternity + patient_records | General/external referral without maternity workspace access. |
| Branch operations | branch_management and verified branch scope | Stay inside current installation scope. |

Lab and imaging are separate capabilities. Internal specialist-to-specialist referrals depend on active profiles and authorization, not Hospital. Preferences can restrict a capability but cannot grant a disabled one.

Re-evaluate availability on server commands, stale Livewire requests and queued actions. If a destination is disabled while work is pending, preserve the original request and offer explicit external rerouting; never silently claim delivery or duplicate the order.

## 4. Workspace contract

Use `layouts.modern`, `x-ui` components and existing menu metadata. Proposed `routes/specialist.php`, named `specialist.*`, contains dashboard, profiles, specialties, appointments, consultations, care-plans, requests, referrals, settings and reports.

| Workspace | Responsibilities |
| --- | --- |
| Dashboard | Assigned appointments, waiting patients, in-progress care, follow-ups, pending/reviewable results, referrals and care-plan reviews. |
| Patients/history | Scoped shared search/registration and longitudinal history. |
| Scheduling | Availability, booking, mode/location, rescheduling, cancellation and arrival. |
| Consultation | Assessment, history/allergies, findings/diagnosis, plan, linked orders/referrals and completion. |
| Care plans/follow-ups | Objectives, actions, responsible clinician, review dates, revisions and follow-up appointments/tasks. |
| Requests/referrals | Internal/external routing, acknowledgement, reports, review and outcomes. |
| Billing | Shared service charges, bills, payments and receipts with Specialist source context. |
| Administration/reports | Specialties, profiles, services, locations, settings, scoped activity and financial summaries. |

Reuse shared patient/billing commands behind these screens rather than copy role-locked doctor/record-officer controllers.

## 5. Proposed data contract

Names are proposals, not existing schema claims. Reconcile column types, enums, relationships and indexes with the production migration chain before implementation. Existing tables receive additive nullable fields; historical rows remain valid.

| Entity | Minimum fields / extensions | Integrity requirements |
| --- | --- | --- |
| `specialties` (new) | name, description, active | Archive referenced specialties; no hard-coded specialty workflow. |
| `specialist_profiles` (new) | user_id, professional title, subspecialty, supplied professional-registration reference, online/physical availability, active | One profile per user per installation. User remains authentication identity; storing a registration is not credential verification. |
| Profile-specialty pivot | profile_id, specialty_id | Unique pair; multiple specialties supported. |
| Service-specialty/profile mappings | service_id, specialty_id/profile_id, consultation type, duration | Reuse existing Service prices. Validate service eligibility for practitioner. Do not reuse the pharmacy retail-service catalogue for specialist consultations. |
| Locations/availability (new if absent) | location, mode, timezone; profile, recurring windows, exceptions | UTC slot storage; retain scheduling timezone; valid location required for physical appointments. |
| Existing `appointments` | profile_id, specialty_id, service_id, mode, location_id, starts_at, ends_at, timezone, reason, patient_visit_id, originating consultation | Preserve legacy date/time consumers. Serialize overlapping-slot checks. Audit rescheduling without replacing identity. |
| `specialist_consultations` (new visit extension) | patient_visit_id, nullable appointment_id, profile_id, specialty_id, mode, status, assessment/findings/diagnosis/plan or established shared equivalents, started/completed timestamps, version | Visit owns patient identity. Unique appointment linkage; intentional encounter creation for walk-ins. One authoritative store for each narrative/clinical datum. |
| Care plans/revisions (new if absent) | patient/visit, responsible profile, problems, objectives, instructions, linked orders, start/review dates, status, version | Preserve previous versions. Reference medication/investigation orders instead of copying them. |
| Existing prescriptions | consultation link, routing type/destination, issued/submitted metadata | Same prescription ID across clinical issue and dispensing. Add historical medication-label snapshots if mutable catalogue references currently rewrite printed history. |
| Existing investigation requests | consultation link, internal/external mode, category, requested-name snapshot, external destination, reviewed_by/at | Internal requires catalogue ID. External supports no catalogue/no local diagnostic bill. Audit all nullable-catalogue consumers and queue filters. |
| Existing `patient_referrals` | visit/consultation, destination type/reference, partner, urgency, clinical summary/findings, requested service, acknowledgement and outcome links | Exactly one validated destination. Preserve legacy fields. Receiving teams own acceptance/admission. |
| Partner directory (new if absent) | name, facility type, contacts, approved/active | A partner is not a login account and gains no record access. |
| Clinical attachments (new if absent) | patient/visit and permitted owner record, private storage key, original name, content type/size, uploader, source, dates | Authorized private downloads; validate upload type/size and ownership. Label external reports by source. |
| Follow-up linkage | source consultation/plan/referral, due date, responsible profile, status, resulting appointment | Reuse Appointment when booked; retain unbooked/overdue tasks. |
| Specialist settings | operating mode, walk-in preference, scheduling defaults, consultation types, integration preferences, timezone | Separate workflow configuration from license flags and global branding. |
| Billing origin metadata | consultation/service-request or billable-occurrence link on bill lines/related source record | Unique source occurrence prevents duplicate charges; repeated sessions have distinct occurrences. |

The installation is the organization boundary in the first release. Do not add unscoped organization columns and claim tenant isolation. If an established branch/organization model is discovered, use its real keys/policies consistently; otherwise multi-organization hosting remains unsupported.

Use existing shared allergies, conditions, vital signs and diagnoses where verified. Add genuinely missing shared data to patient/visit structures and specialty-only fields to the consultation extension. Timeline entries link to source records rather than duplicate narratives.

Completed consultations are read-only. Corrections are attributed amendments with reason and prior-version linkage. Care-plan revisions and result review preserve chronology. Concurrent updates must detect version conflicts instead of overwriting another clinician's work.

## 6. Workflow contracts

### Booking and consultation

1. Find/register the shared patient. Entering from Clinic/Maternity/Hospital does not create another identity.
2. Book an eligible specialist/service or allow a configured walk-in. Validate active profile, assignment, mode, location, duration and availability.
3. On arrival, create one linked visit/consultation or attach to the explicitly selected existing encounter. Never infer the correct encounter merely from the patient's latest visit.
4. Document complaint, history, allergies/conditions, assessment, examination, diagnosis/findings and plan under record-level policies.
5. Issue optional prescriptions, investigations and referrals as linked records. Consultation completion does not require optional receiving packages.
6. Complete with an attributed summary and follow-up decision; book an appointment or create an unbooked follow-up task.

Proposed consultation lifecycle: `draft -> in_progress -> completed`, with cancellation before completion. Proposed appointment lifecycle: `scheduled -> arrived -> in_progress -> completed`, plus cancelled/no-show. Reconcile existing status strings and database constraints before adopting these names. Rescheduling retains identity and audit history.

### Prescriptions

Clinical issue validates the prescriber and contents, records issuance and supports print/download without Pharmacy. Medicine catalogue entry must not require stock or create batches. External issuance is not internal dispensing.

When capability exists, explicitly submit the same prescription to the existing Pharmacy queue. Pharmacy retains stock allocation, payment and dispensing ownership. Repeat submission must not duplicate records; preserve partial dispensing and stopped-medication behavior. External fulfillment is recorded only as an external outcome and never decrements local stock.

### Investigation requests and results

Internal requests use existing diagnostic catalogue, billing, processing and results. Select a compatible lab/imaging destination and preserve IDs. A result becoming available and a specialist reviewing it are separate events.

External requests capture requested test/procedure, destination and clinical question without manufacturing an internal test or local diagnostic bill. Permit private report upload and external summary, with source, uploader, report date and review/verification status. An uploaded external file is not automatically a verified internal result.

Proposed routing/review lifecycle: `draft -> requested -> acknowledged -> result_available -> reviewed`, with rejection/cancellation branches. Add orthogonal routing/review metadata rather than replace existing diagnostic-processing statuses.

### Referrals and hospital care

Extend PatientReferral for specialist, clinic, maternity, hospital and external targets. Include urgency, relevant summary, service request and selected attachments. Internal acknowledgement requires receiver authorization. External dispatch initially means generated documents and manual delivery records, not automatic transmission.

Proposed referral lifecycle: `draft -> sent -> accepted -> completed`, plus rejected/cancelled and documented outcome. Map existing statuses before changing schema.

Hospital acceptance links to the existing receiving review/admission workflow with the same patient. Referral creation must never invoke `PatientVisit::currentAdmission()`: that helper can create an admission and occupy a bed. Only appropriately authorized receiving staff initiate admission. Link discharge/outcome back to the referral and follow-up; limit information shown to the originating specialist by permission.

### Billing and payments

Reuse Service, ServiceRequest, Bill, BillService, Payment and receipt paths. Snapshot charge, quantity, service label and discount at billing. Later price edits affect future charges only. Clinical completion and payment are separate states; preserve configured existing payment requirements without inventing a mandatory pay-before-care rule.

Booking and consultation must not both bill the same service occurrence. Persist a unique billable occurrence atomically; retries/double-clicks reuse it. Receiving packages own their existing medicine, diagnostic and inpatient charges. Diagnostic request creation already generates bills: integration must not create a second Specialist copy.

Use existing payment methods/reconciliation and receipts; test partial payment and supported cancellation/reversal flows. External providers' charges are not local revenue unless explicitly configured as a legitimate locally billed service.

## 7. Authorization and historical access

Effective access requires installation capability, applicable user module access, action permission and patient/record scope. A subscription or installation-admin flag does not confer clinical access.

Proposed roles: `specialist`, `specialist_assistant`, `specialist_manager`. Reuse existing reception/billing/head roles where suitable; permission bundles do not require duplicate staff accounts.

| Actor | Responsibilities | Restrictions |
| --- | --- | --- |
| Installation administrator | Packages/modules and branding | No implicit prescribing, patient browsing or credential verification. |
| Specialist manager | Specialties, profiles, services, availability/settings | Clinical authoring or unrestricted history requires separate grants. |
| Specialist | Assigned/accepted patients, consultations, eligible prescribing, requests, plans, referrals/review | Clinical privileges are explicit; specialty names confer no prescribing authority. |
| Assistant/reception | Permitted demographics, booking, arrivals, limited administrative context | No signing, diagnosis, unrestricted notes or prescribing. |
| Billing staff | Charges, bills, payments/receipts | Minimum patient context; no clinical narrative by default. |
| Receiving team | Assigned requests/referrals and necessary patient context | No unrestricted originating practice records. |

Proposed permissions: `specialist.settings.manage`, `specialty.manage`, `specialist_profile.manage`, `specialist_service.manage`, `specialist_appointment.read/manage`, `specialist_consultation.read/create/update/complete/amend`, `specialist_record.read`, `specialist_prescription.create/issue`, `specialist_investigation.request`, `specialist_result.read/review`, `specialist_referral.create/accept/update`, `specialist_care_plan.manage`, `specialist_billing.read/create`, `specialist_payment.record`, `specialist_report.read`, `specialist_report.revenue`. Reuse established equivalents rather than create synonyms.

Implement policies and scoped queries for patients, consultations and orders, including search suggestions, exports, documents and every Livewire action. Care-team assignment or accepted referral establishes scope; department membership alone does not expose all patients. Restricted notes require explicit grants. Never trust client-supplied patient/record IDs without ownership checks.

Disabling Specialist blocks its workspace/new mutations. Preserve historical entries for authorized, read-only shared-history access while patient_records remains enabled. Implement this through scoped history projection, not a disabled-route bypass. Existing receiving teams may finish accepted work if their own capabilities/permissions remain active.

## 8. Setup, route restrictions and branding

Extend Installation Setup with primary package, selected packages and existing module choices. Preview changes/dependency errors; retain transactional saving, audit and super-admin authorization. Add a Specialist welcome template and preserve manual overrides, logo, brand name and welcome statement.

Add `specialist.*` module rules and narrower billing/report requirements. Do not simply broaden all shared investigation routes to let Specialist execute diagnostic processing: introduce an external-capable request command/route and keep internal result authoring gated to receiving modules.

Before standalone release, gate hospital admission/discharge operations using a verified inpatient capability (proposed clinical_care + wards_beds) and action permissions. Audit equivalent routes, legacy endpoints and Livewire mutations. Regression-test legitimate existing hospital/clinic use before tightening these shared routes.

On disabling a package: remove navigation/new operations, deny stale mutations, retain data and audit, preserve paid bills and unrelated capabilities. Re-enabling must not reseed records. No uninstall action may drop shared schema.

## 9. Reporting, auditing and external collaboration

Reports include consultations by period/practitioner/specialty, distinct patient volume, new/returning patients, follow-up due/completed/overdue, referral destinations/outcomes, pending/reviewed results, diagnoses where authorized, service utilization and authorized billed/paid/outstanding amounts. Define returning as having a prior completed Specialist consultation. Distinguish consultation, billing and payment dates; billed totals are not cash received.

Dashboard/report queries share record scopes. Separate pending results from results awaiting review. Extend module-aware report filtering and history projections without enabling unrelated datasets.

Reuse AuditLog for configuration, profile/service edits, signing/amendments, orders, review, referrals, plan revisions and billing actions; instrument sensitive access where implemented. Capture actor, target, time and necessary changes, not entire confidential documents or credentials in general logs. Notifications dispatch after commit and contain minimal permitted context. Verify existing channels before adding delivery adapters.

External partners are approved directory entries. Printable/manual referral and private document return form the initial collaboration channel. Cross-installation APIs, authentication, identity matching, consent-controlled export and expiring links need an explicit exchange contract before automation. Directory membership never grants unrestricted patient access.

## 10. Ordered implementation backlog

| Phase | Concrete work | Exit condition |
| --- | --- | --- |
| 1. Reconciliation | Verify real appointment/referral migrations/status values, registration side effects, encounter ownership, shared clinical/document fields, admission models, billing and sync consumers. | Confirmed schema/ownership map with no duplicated core entities. |
| 2A. Package foundation | Specialist preset/dependencies, selected_packages migration, compatible license resolution, setup UI, capability service, route rules, welcome template. | Existing installations unchanged; standalone/union/lifecycle checks pass. |
| 2B. Access/configuration | Policies, focused permission/menu seed, specialties/profiles/service mappings, locations, availability, settings. | Authorized managers configure services; other users cannot mutate setup. |
| 3A. Scheduling/encounters | Extend Appointment, booking conflicts, linked PatientVisit/consultation, walk-ins and shared history. | Repeated arrival creates one consultation; unauthorized patients remain inaccessible. |
| 3B. Clinical workflow | Assessment/documentation, signing/amendment, plans, follow-up and standalone prescription issue/print. | Clinical journey works without stock, internal diagnostics or inpatient modules. |
| 4. Internal integration | Existing Pharmacy/diagnostic queues, Clinic/Maternity context, hospital referral boundary, Enterprise scope. | Same patient/order IDs; no duplicate bills or automatic admissions. |
| 5. Collaboration | Structured PatientReferral, external investigation mode, approved partners, private reports/documents and outcome review. | External journey works without receiving packages and respects scope. |
| 6. Management | Dashboards, reports, finance views, notifications and complete audit coverage. | Aggregates reconcile with source records and access policies. |
| 7. Release | Combination/security/concurrency tests, MySQL migration rehearsal, UI/print checks, deployment notes. | Acceptance matrix and rollback procedure verified. |

Dependency adjustment: minimal external investigation/referral support from Phase 5 is required for the standalone Phase 3 release; implement those minimal paths alongside Phase 3. Richer partner management can follow. Billing-origin/idempotency work accompanies the first billable workflow. Policies/audit accompany every phase, not just Phase 6.

### Proposed file ownership

Extend current package/route/branding config, InstallationSetup, LicenseService, SidebarService, shared models/workspaces and patient history. Add `PackageCapabilities`, `app/Livewire/Specialist/*`, `resources/views/components/specialist/*`, `routes/specialist.php`, relevant policies, additive migrations and a focused `SpecialistModuleSeeder`. Extract shared commands only where multiple workspaces actually need them.

Use repeatable seeders that attach permissions/modules without resetting existing assignments. Do not deploy by broad user/catalogue reseeding. Do not invent default practitioner accounts, clinical privileges or service prices.

## 11. Acceptance and regression matrix

| ID | Scenario | Required evidence |
| --- | --- | --- |
| A | Specialist only | Register shared patient, book/walk in, consult, print external prescription, request external test, upload/review report, refer externally, bill/pay and follow up. No optional operational modules required. |
| B | Specialist + Pharmacy | Original prescription reaches queue and partial/final dispensing links back. No duplicated patient, prescription or medicine charge. |
| C | Specialist + Diagnostics | Original request produces existing bill/result and separate clinician review. Test lab-only and imaging-only configurations. |
| D | Specialist + Hospital | Referral -> receiving review -> authorized admission -> discharge -> follow-up. Referring clinician cannot occupy a bed without admission authority. |
| E | Multiple packages | One traceable patient journey through medications, diagnostics and inpatient outcome; payments reconcile. |
| F | Specialist disabled/re-enabled | Unrelated packages function; data survives; stale writes denied; read-only history remains scoped; reactivation creates no duplicates. |
| G | Specialist + Clinic | Same patient and explicit visit linkage where appropriate; no duplicate registration/file-opening/consultation charge. |
| H | Specialist + Maternity | Link authorized maternity context without recreating a pregnancy or unnecessary encounter. |
| I | Specialist + Enterprise | Preserve existing branch entitlements; verify real scope before allowing cross-branch records. |
| J | License lifecycle | Legacy aliases, absent/latest/expired/future/inactive license, primary package, module overrides and dependency validation preserve documented behavior. |
| K | Authorization | Reject forged IDs, other specialists' patients, assistant prescribing, disabled-module direct URLs, stale Livewire actions, unauthorized export and attachment access. |
| L | Concurrency/retries | Competing bookings, repeated arrival/dispatch/billing/payment and simultaneous note edits cause neither duplicates nor lost updates. |
| M | Historical integrity | Catalogue/price/profile edits do not rewrite signed or charged history. Deactivation preserves references and amendments. |
| N | Failure handling | Failed billing/storage/dispatch rolls back dependent work or leaves an explicit retryable state. No notification for rolled-back work. |
| O | Existing-system regression | Six presets, setup/branding, departmental staff roles, clinical prescriptions, diagnostics, pharmacy mixed receipts, accounting and hospital workflows still work. |
| P | UI/printing | Physical/online/hybrid forms, long text, timezone/DST booking, validation, printable prescriptions/referrals/receipts and authorized downloads work. |

Suggested future tests: `SpecialistInstallationTest`, `SpecialistAccessTest`, `SpecialistSchedulingTest`, `SpecialistConsultationTest`, `SpecialistStandaloneTest`, `SpecialistIntegrationTest`, `SpecialistBillingTest`, `SpecialistHistoryTest`. Retain `InstallationSetupTest`, `DepartmentUsersTest` and `PharmacyServicesTest` as regression coverage. Use isolated synthetic fixtures; rehearse migrations on disposable MySQL in addition to SQLite. No real patient data or live external dispatch is necessary.

## 12. Migration and release procedure

1. Reconcile schema/status compatibility; back up database/private documents. Record existing modules, package and staff assignments.
2. Deploy additive migrations before enabling Specialist. Backfill selected packages without changing explicit module rows, activation or expiry. Preserve legacy-code compatibility during rollout.
3. Apply focused navigation/permission seeds. Refresh route/config/view caches through the existing deployment process.
4. Installation administrator selects Specialist and companions, reviews dependencies and branding. Clinical managers configure services, specialties, profiles and operating preferences.
5. Provision users with roles, module access and patient assignments. Do not automatically promote existing staff to clinical privileges.
6. Run synthetic standalone and configured integration journeys; check finance reconciliation, audit, documents and history.
7. Operational rollback disables Specialist and restores compatible application code while retaining additive schema/history. Never reverse migrations containing new clinical records. Full restoration needs a recovery plan for post-upgrade data.

## 13. Decisions to verify before coding

| Decision | Proposed default / resolution |
| --- | --- |
| Commercial package lifecycle | One installation license, multiple selected packages; independent subscriptions require later design. |
| Organization/branch scope | One organization per installation; verify actual branch enforcement before cross-branch collaboration. |
| Admission ownership | Trace active Admission versus PatientAdmission controllers/migrations; link the operational entity without introducing a third one. |
| Scheduling timezone | Explicit installation timezone and UTC slots; carefully migrate legacy date/time values. |
| Registration prerequisites | Ensure file types, demographics, departments and automatic charges work standalone without accidental hospital dependencies. |
| Clinical field ownership | Reconcile allergies, conditions, diagnoses and narrative storage before adding fields; one authoritative owner per datum. |
| External investigations | Extend existing request root after auditing catalogue dereferences/queue filters; never fake internal processing/results. |
| Documents/notifications | Reuse verified providers; authorized printing/manual return when no delivery channel exists. |
| Prescribing eligibility | Explicit privilege assignment, not inferred from specialty/profile existence. |
| Synchronization | Determine UUID/dependency mapping for new records; no automatic cross-organization exchange until implemented and tested. |

These are targeted implementation checks. Foundation/configuration work can begin while later integration details are reconciled; do not redesign working core modules as a shortcut.

## 14. Source requirement coverage

| Supplied sections | Coverage in this specification |
| --- | --- |
| 1-3: architecture, purpose, core reuse | Sections 1-3 and repository assessment. |
| 4-6: profiles, specialties, services | Sections 4-5, 7 and Phase 2B. |
| 7-10: appointments, consultation, clinical information, plans | Sections 5-6 and Phases 3A-3B. |
| 11-14: prescribing, investigations, referrals, hospital | Section 6 and capability matrix. |
| 15-17: combinations, collaboration, modes | Sections 1, 3, 6, 9 and tests A-I/P. |
| 18-19: finance and permissions | Sections 6-7. |
| 20-23: dashboard, history, reports, settings | Sections 4-5 and 8-9. |
| 24-29: lifecycle, integrity, audit, compatibility | Sections 2-3, 7-9 and 12-13. |
| 30-32: incremental work, tests, outcome | Sections 10-12 and acceptance A-P. |

Related: [Installation Setup](INSTALLATION_SETUP.md), [Modern UI Continuation Guide](UI_MODERN_MIGRATION_CONTINUATION_GUIDE.md), [Pharmacy Services](PHARMACY_SERVICES.md).

## Care Network and Partner Network extension

The follow-on source requirements in sections 33-57 are specified in [Partner Network Implementation Specification](PARTNER_NETWORK_IMPLEMENTATION_SPEC.md). It covers registration, invitations, verification, agreements, scoped portal access and clinical collaboration. The hosted extension is now implemented; see [Care Network Operations](CARE_NETWORK_OPERATIONS.md) for activation and supported boundaries. The displayed package name is Care Network, with the existing specialist key retained for compatibility.
