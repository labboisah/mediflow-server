# Diagnostic Center Package Implementation Plan

**Based on:** [Diagnostic Center Migration Review](DIAGNOSTIC_CENTER_MIGRATION_REVIEW.md)  
**Date:** 2026-10-03  
**Purpose:** Add Medistop-equivalent diagnostic-center capabilities to MediFlow while preserving the current behavior of all other packages.

## 1. Outcome

A MediFlow installation with the Laboratory and/or Radiology modules enabled should support the agreed Medistop workflows:

- service catalog, price, and diagnostic request management
- grouped billing and paid-work controls
- laboratory parameter results and radiology narrative reports/images
- separate radiographer performance and radiologist reporting
- personal report templates and client-branded printouts
- diagnostic-specific commission rules, booked shares, and reports
- historical import from Medistop with reconciliation and audit references

The same codebase must continue to support clinic, maternity, pharmacy, hospital, and mixed-package installations without changing their existing routes, billing calculations, UI, permissions, or reports.

## 2. Non-negotiable package-isolation rules

1. **Keep shared billing contracts stable.** Do not change the meaning of Bill, Payment, BillItem, BillService, RevenueCalculator, or existing service-line distribution records for non-diagnostic packages.
2. **Add diagnostic behavior behind the correct module.** Laboratory routes and data require the laboratory module; imaging routes and data require radiology. Shared diagnostic screens must declare whether either module or both are required.
3. **Use additive, reversible schema changes.** Add diagnostic-specific tables/columns and indexes. Do not repurpose or remove shared tables or old Medistop compatibility tables during the parity work.
4. **Apply every access layer.** A request must pass installation license, user module access, and role/permission checks. Hiding a sidebar link is not authorization.
5. **Keep shared patient and payment models neutral.** Diagnostic features may create PatientVisit, WalkinPatient, Bill, and Payment records through their existing contracts, but must not change the behavior of those records for other workflows.
6. **Preserve mixed installations.** Do not make the diagnostic-center plan exclusive. A client must be able to enable diagnostic modules with other licensed packages.
7. **Avoid fixed client branding.** Diagnostic reports must use MediFlow branding/configuration and per-installation settings, not Medistop-specific names or addresses.

## 3. Architecture decisions

### 3.1 Keep the active request as the diagnostic anchor

Continue to use InvestigationRequest as the anchor for each requested test. Keep existing InvestigationResult parameter/value rows for structured laboratory values and radiology measurements.

Add a request-linked DiagnosticReport record for Medistop-style narrative content and reporting lifecycle:

- clinical note, findings, impression
- report status and version/audit metadata
- performed_by / performed_at
- reported_by / reported_at
- one report per InvestigationRequest unless clinical sign-off approves versioned amendments

Keep optional report attachments linked to the report or request using private storage rules. Reuse the existing radiology image capability where possible.

Use ResultTemplate for user-owned templates after confirming its ownership and uniqueness behavior. Make templates available from report entry without coupling them to billing or another package.

The legacy BillResult model and table remain compatibility data for the old BillItem/service path. New diagnostic-center requests must not write to both BillResult and DiagnosticReport. A later cleanup decision can be made after migration and usage checks.

### 3.2 Isolate diagnostic revenue distribution

Medistop commission calculations are per bill line. The current MediFlow RevenueDistribution is tied to BillItem; active diagnostic charges use BillInvestigation. Do not extend RevenueCalculator in a way that changes existing service-line calculations.

Add diagnostic-only rule and snapshot records, for example:

- DiagnosticRevenueRule: rule scope, percentages, effective dates, active state, and who changed it
- DiagnosticRevenueDistribution: bill_investigation_id, rule snapshot, base amount, staff/radiographer/radiologist/center amounts, and relevant performer/reporter IDs

Use the target's existing InvestigationType and/or Department as the rule scope only after finance owners choose the policy. Snapshot amounts when the bill line is issued or when the agreed earning event occurs. Historical reports must use the saved distribution, not today's rule.

Expose diagnostic commissions through diagnostic reports or a module-owned finance view. Do not change existing RevenueDistribution totals or service-revenue reports.

### 3.3 Make report lifecycle explicit

Implement transitions with authorization in component/controller methods and domain services:

- requested -> paid/eligible according to policy
- eligible -> performed by an assigned lab/radiology role
- performed -> reported by an authorized reporter
- reported -> amended only by an authorized user, with prior content and authorship retained

The client must approve whether full payment is always required. Preserve current MediFlow paid-request behavior by default until a deliberate product decision changes it.

## 4. Work phases

### Phase 0 — Baseline, policy, and package matrix

**Work**

- Record current Lab, Radiology, billing, finance, navigation, and role behavior before changes.
- Confirm package combinations supported for release: Diagnostic Center only; Diagnostic + Clinic; Diagnostic + Maternity; Diagnostic + Pharmacy; Hospital; and non-diagnostic Clinic/Maternity/Pharmacy.
- Decide patient identity handling for imported bills, paid/unpaid result rules, report amendments, template ownership, revenue rule scope, and which historical ledgers must move.
- Inspect each client’s actual Medistop database and migration ledger. The repository drops the bill_refunds table in a later migration while refund code remains.
- Record route names and protected Livewire actions for diagnostics.

**Exit condition**

A signed behavior matrix and migration scope exist. The non-diagnostic package regression baseline is recorded.

### Phase 1 — Licensing and authorization boundary

**Likely files**

- config/mediflow_modules.php
- config/installation_routes.php
- database/seeders/ModulePermissionSeeder.php
- database/seeders/RoleSeeder.php
- app/Services/LicenseService.php
- routes/lab.php
- routes/radiology.php

**Work**

- Confirm Laboratory and Radiology remain separate license modules; retain diagnostic_center as a convenient plan preset that composes modules.
- Map every route, export, file endpoint, report print, and Livewire action to the license requirement.
- Add/verify user module-access and role/permission enforcement on direct requests and Livewire updates. In particular, authorize lab technicians/scientists and radiographers separately from radiologists.
- Decide whether a diagnostic request entry route requires laboratory, radiology, or either module based on the request type. Keep management of a department-specific investigation scoped to its department.
- Ensure disabling a diagnostic module hides its navigation, blocks its endpoints and actions, and does not hide billing or patient records required by other enabled modules.
- Preserve diagnostic history in the database if a module is disabled; disabling is access control, not data deletion.

**Exit condition**

Tests prove that unlicensed users, users without module access, and users without role permission cannot access diagnostic functions through URLs, exports, file links, or Livewire requests.

### Phase 2 — Additive diagnostic domain schema

**Likely files**

- new migrations and models under app/Models
- InvestigationRequest and InvestigationResult relationships
- ResultTemplate ownership and validation
- diagnostic module seeders

**Work**

- Add DiagnosticReport with a unique request link, narrative fields, performer/reporter identities and timestamps, status, and amendment/version history as required by policy.
- Keep InvestigationResult for structured parameters. Define how final completion is derived when a request has both parameter values and narrative reporting.
- Add diagnostic revenue rules and distribution snapshots keyed to bill investigation lines; preserve old BillItem distribution tables and behavior.
- Add stable source-system/source-ID cross references for Medistop import and idempotent reruns.
- Add indexes and foreign keys without changing existing table semantics. Include safe deletion/retention behavior for bills, requests, users, and reports.
- Reuse ResultTemplate only if it supports safe per-user ownership; add uniqueness/validation through an additive migration if needed.

**Exit condition**

Migrations run on fresh and existing databases. Diagnostic records link to current requests/bills while old service bill, pharmacy, and finance records still behave as before.

### Phase 3 — Request-to-result operations

**Likely files**

- routes/lab.php and routes/radiology.php
- app/Http/Controllers/Lab/*
- app/Http/Controllers/Radiology/*
- app/Livewire/Lab/*
- app/Livewire/Clinical/InvestigationRequestWorkspace.php
- diagnostic result views and patient profile views

**Work**

- Preserve multi-investigation, single-bill requests and the current InvestigationRequest -> BillInvestigation linkage.
- Keep bill totals, discounts, payment status, and request payment status consistent through existing billing services.
- Build lab result entry using Investigation parameters and values, including validation for parameter ownership and result completeness.
- Build radiographer “mark performed” and radiologist report-entry steps against the same request. Do not make a radiographer impersonate a radiologist or report.
- Add narrative clinical note, findings, impression, report template selection, amendment history, and print action.
- Keep radiology result-image upload/edit behavior, with private/authorized delivery, file validation, replacement, and deletion rules.
- Preserve patient/walk-in name and identifying data on request/report screens without creating a separate duplicate patient record.
- Confirm report visibility and patient-profile views use null-safe handling for imported, external, or incomplete diagnostic records.

**Exit condition**

A paid lab request and paid radiology request can be requested, performed, reported, amended according to policy, viewed, and printed with correct attribution. No duplicate bill lines or duplicate result records are created.

### Phase 4 — Catalog and diagnostic finance

**Likely files**

- investigation/type administration
- new diagnostic revenue models/services
- diagnostic report screens and exports
- admin finance/report views

**Work**

- Map category to Department + InvestigationType using an explicit import mapping; map service to Investigation while preserving source identifiers and current price.
- Preserve historic line prices on billed rows even when the catalog price changes.
- Create an administrator-controlled diagnostic sharing policy and immutable per-line distribution snapshot.
- Define exact rounding, percentage total, refunds/voids, partial payment, and commission earning rules with finance.
- Add staff commission summaries and detail reports for the proper diagnostic roles. Report performed and reported earnings separately when both are payable.
- Ensure finance totals distinguish diagnostic distributions from service-line RevenueDistribution records and do not count a bill twice.
- Use configured system branding and dynamic report metadata on printable reports.

**Exit condition**

Diagnostic revenue/commission values reconcile from a bill line to detail reports and summaries; existing service, pharmacy, and finance totals are unchanged in their package matrix tests.

### Phase 5 — Medistop importer and transition tooling

**Likely files**

- a dedicated Artisan command/job/service under app/Console or app/Services/Migration
- import mapping tables/models
- dry-run, result, and exception reports
- client-specific configuration stored outside source control

**Work**

- Build an idempotent importer with dry-run, checkpoints, resume, row-level exceptions, source IDs, and a cutover/delta mode.
- Import in dependency order: configuration/catalog and user map; patient/walk-in/visit links; bills, requests, and bill lines; payments; structured/narrative results and attachments; templates; historical distribution/finance records agreed in scope.
- Preserve multi-item bill grouping, source bill numbers, historical prices, discount, payment state, balance, result attribution, and source timestamps.
- Use a reliable source patient identifier for Patient creation. Keep unmatched/free-text identities as walk-in or reviewed exceptions; never auto-merge based only on name.
- Preserve already-booked commission amounts as historical facts; do not recompute them using current rules.
- Map users by approved identity, then map staff_type to MediFlow role, department, and module access. Keep an exception path for departed/unmatched users so historical authorship is not rewritten.
- Do not import refund rows until the client’s deployed source schema proves they exist and finance approves the mapping.
- Never delete or mutate Medistop source data during import.

**Exit condition**

A test copy can be imported repeatedly with identical target counts/totals, and every skipped or ambiguous source row appears in an actionable exception report.

### Phase 6 — Package regression and migration acceptance

**Required package matrix**

| Installation | Required check |
| --- | --- |
| Diagnostic Center only | All licensed lab/radiology workflows, billing, finance, reports, and diagnostic roles operate. |
| Diagnostic + Clinic | Clinical requests use the shared patient/billing services without changing clinic prescriptions, visits, or service billing. |
| Diagnostic + Maternity | Antenatal and maternity investigation links, patient tabs, role access, and billing remain correct. |
| Diagnostic + Pharmacy | Diagnostic and pharmacy bill lines/payments remain distinct; totals and inventory are unaffected. |
| Hospital | All modules coexist; module access and role restrictions continue to apply. |
| Clinic/Maternity/Pharmacy without diagnostic modules | Diagnostic links are hidden; direct routes/actions/files are blocked; all package workflows remain unchanged. |

**Required test groups**

- License matrix: module enabled/disabled, plan preset/combined modules, direct route, export, file retrieval, and Livewire action.
- User access: granted, revoked, expired, unrelated role, administrator override policy, and department scope.
- Workflow: unpaid/partial/paid policy, lab parameter completeness, radiographer performance, radiologist report, template ownership, amendments, attachments, printout, and audit attribution.
- Billing: multi-line group, discounts, partial payment, paid-state update, void/delete restrictions, and historical price preservation.
- Commission: rule validation, snapshot, rounding, performer/reporter split, reversal, and historical totals.
- Import: identity mapping, repeated run, bill grouping, no duplicate records, totals, source links, missing user/catalog, attachments, and exception output.
- Cross-package: run existing billing, patient, finance, pharmacy, maternity, and clinic feature suites under the matrix above.

**Exit condition**

All diagnostic acceptance criteria pass, and non-diagnostic behavior matches its recorded baseline across the package matrix.

### Phase 7 — Client rehearsal and cutover

**Work**

- Rehearse against a protected copy of the client’s deployed Medistop database and files.
- Have the client sign off catalog/category mapping, patient match exceptions, paid/unpaid policy, report template/branding, staff mapping, and commission treatment.
- Reconcile bill/line counts, gross, discount, paid, outstanding, payment methods, completed/performed reports, templates, and historic shares by date and category.
- Agree downtime or delta-import window, verified backups, rollback path, and read-only retention for Medistop.
- Run final import, verify sample cases with mapped roles, then enable modules for users.
- Keep Medistop available read-only until clinical, finance, and access acceptance is signed.

**Exit condition**

Clinical owner, finance owner, and installation administrator approve the reconciliations and workflow/access checks before the standalone system is retired.

## 5. Definition of done

- Diagnostic features are limited to the licensed Laboratory and Radiology modules and do not change unrelated package behavior.
- Every diagnostic request, bill line, report, attachment, template, and commission entry has a clear owner and audit trail.
- A report can represent both structured results and narrative findings while keeping the performer and reporter distinct.
- Diagnostic commissions use diagnostic bill-line snapshots and never alter existing service-line revenue calculations.
- The importer is repeatable, auditable, resumable, and reconciled; ambiguous source records are surfaced instead of guessed.
- The supported package matrix passes, including installations that do not license diagnostic modules.
- Client-specific policies and migration exceptions are documented before cutover.

## 6. Decisions to resolve before Phase 1

1. Is the report lifecycle exactly “radiographer performs, radiologist reports” for all imaging, and what Lab role may finalize results?
2. Is full payment mandatory before work begins, or can approved credit/emergency paths bypass it?
3. What should happen when a reported result is corrected: append amendment/version, reopen the same report, or create a new report?
4. Are diagnostic shares configured per InvestigationType, Department, individual Investigation, or another grouping?
5. Which historical finance/report records are imported versus retained in a read-only Medistop archive?
6. What patient identifiers are reliable enough to merge a Medistop bill identity into an existing MediFlow patient?
7. Which package combinations are part of the first client rollout, and which can follow after the diagnostic-only cutover?
## Patient identity decision (2026-10-03)

- Preserve Medistop `patient_id` as a source-system reference so repeated imports resolve to the same MediFlow patient or walk-in record.
- Try an exact MediFlow hospital-number match and an exact phone-number match. If they resolve to different existing patients, stop that row for manual review. Never match on name alone.
- Reuse the matched MediFlow patient and attach the imported bill to a patient visit. If neither identifier matches, create one `WalkinPatient` for that source patient and reuse it for that patient's bills.
- Preserve source patient ID, phone, and hospital number on a diagnostic-only bill contact record. Walk-in phone is optional when the source has no phone; no placeholder number is fabricated.
- Keep unmatched/ambiguous identities in the importer exception report. Do not merge two source IDs merely because they share a phone or name.
