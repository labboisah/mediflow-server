# Diagnostic Center Migration Review: Medistop to MediFlow

**Review date:** 2026-10-03  
**Scope:** Read-only comparison of C:\laragon\www\medistop with C:\laragon\www\mediflow. This is a migration review and upgrade plan; it does not implement the migration.

## Executive summary

Medistop is a standalone diagnostics and billing application. Its operational path is organized around a bill, bill lines, and a result per bill line. It also has a distinct radiographer-performed / staff-reported workflow, user-owned report templates, per-service revenue splits, staff commission views, and its own finance and reporting.

MediFlow represents a diagnostic-center installation as a diagnostic_center plan preset in config/mediflow_modules.php. That preset enables patient records, laboratory, radiology, billing, finance, reports, and access control; those modules can coexist with modules from other plans. Its active diagnostic path is organized around a patient visit or walk-in, investigation requests, investigation results per parameter, and investigation bill lines.

Some Medistop-era pieces already exist in MediFlow (BillResult, ResultTemplate, BillItem, RevenueSharingRule, and RevenueDistribution, plus legacy bill columns). However, MediFlow's current lab/radiology pages use InvestigationRequest, InvestigationResult, and BillInvestigation. StaffResultController and the legacy narrative result tables are not wired into the current route set. Revenue distribution is attached to BillItem, while diagnostic requests are billed through BillInvestigation. These two paths do not yet provide end-to-end parity.

**Recommendation:** Treat this as a versioned, repeatable data migration plus a diagnostic workflow integration project. First choose one canonical result and commission path in MediFlow; then build and reconcile the importer. Do not import production records directly into a live client database before that decision and a rehearsal.

## What exists today

### Medistop source workflow

- A bill can contain several BillItem rows. Each line points to a service, whose category holds the revenue-sharing percentages.
- Staff look up a bill and can enter results only after it is fully paid.
- A radiographer can mark an investigation performed. A radiologist/staff reporter then records clinical note, findings, and impression. The BillResult holds separate performer and reporter identities and timestamps.
- Reporters can save personal result templates. Reports can be printed with the diagnostic center's own header.
- Revenue splits are snapshotted per bill item. Staff, radiographer, radiologist, and center shares feed commission and finance reports.
- Bills carry patient name, age, and gender directly; the reviewed schema has no normalized patient or visit table for these records.
- Staff identity combines role=staff with staff_type values such as radiologist, radiographer, and staff.

Evidence: medistop/routes/web.php, medistop/app/Http/Controllers/StaffResultController.php, medistop/app/Models/BillResult.php, medistop/app/Models/ResultTemplate.php, medistop/app/Services/RevenueCalculator.php, and migrations for bills, bill_items, bill_results, result_templates, revenue_sharing_rules, and revenue_distributions.

### MediFlow target workflow

- diagnostic_center is a plan preset, not a single standalone feature module. Its module set is declared in config/mediflow_modules.php.
- Lab and radiology routes are package-gated by config/installation_routes.php. The global installation middleware checks that the licensed module is enabled.
- The current investigation request workspace can create multiple requests on one bill. Requests are linked to a patient visit and have lab numbers, requester, performer, status, specimen, and payment status.
- Lab result entry stores structured values against investigation parameters. Radiology supports parameter values and a result image.
- The target has both Medistop-style narrative result models and the structured investigation result model. StaffResultController exists, but its staff.results.* routes are absent from the current routes/ files.
- Current BillInvestigation rows do not have RevenueDistribution; the existing revenue calculator and result-commission paths use BillItem and its service/category relationship.

Evidence: config/mediflow_modules.php, config/installation_routes.php, bootstrap/app.php, routes/lab.php, routes/radiology.php, app/Livewire/Clinical/InvestigationRequestWorkspace.php, app/Http/Controllers/Lab/RequestController.php, app/Http/Controllers/Radiology/RequestController.php, app/Models/InvestigationRequest.php, app/Models/InvestigationResult.php, app/Models/BillInvestigation.php, app/Models/BillResult.php, and app/Services/RevenueCalculator.php.

## Source migration caveat

The Medistop repository includes a migration that drops the bill_refunds table even though refund-related code remains. The actual client database may have a different migration history. Inspect its schema and migration ledger before finalizing the import scope or treating refund records as available.

## Parity and upgrade gaps

| Area | Current mismatch | Upgrade needed |
| --- | --- | --- |
| Patient identity | Medistop stores demographics as bill text; MediFlow expects a patient visit or a walk-in record. | Define deterministic mapping for reliable identities. Route records without a trustworthy patient key to walk-in history; do not merge people solely because names match. |
| Catalog | Medistop has categories and services. MediFlow has departments, investigation types, investigations, and optional parameters. | Map each source category/service to a department/type/investigation; preserve service codes, active state, price history, and aliases. Review ambiguous categories with the client. |
| Billing | Medistop uses bill_no, bill_items, and stored totals. MediFlow uses bill_number, bill_investigations, payment rows, status/balance fields, plus legacy bill columns. | Preserve source bill numbers and totals, maintain one source bill as one target bill with all its request lines, and reconcile discounts, payment history, and balances. |
| Result shape | Medistop stores narrative sections per bill line. MediFlow's active lab path stores parameter/value rows per investigation request; radiology can add an image. | Choose a canonical diagnostic report model supporting both narrative sections and structured observations, with a link to request, bill line, author, status, and attachments. Import narrative reports without flattening them into a fake parameter. |
| Result lifecycle | Medistop separates performed and reported actors. MediFlow request rows currently provide a single performed_by and completion timestamp; radiology routes require radiologist. | Add or connect a separate performed/reported lifecycle and allow a radiographer to record performance while a radiologist reports, with attribution and edit restrictions. |
| Templates and printouts | Medistop has personal result templates and a branded print report. Target template model exists, but the current parameter entry flow does not use it; current route set does not expose StaffResultController. | Integrate templates and a printable, client-branded diagnostic report into the canonical workflow. Decide whether templates are imported per user or recreated after staff mapping. |
| Commissions | Medistop snapshots staff/radiographer/radiologist/center shares per service line. MediFlow's RevenueDistribution is linked to BillItem; active diagnostic billing uses BillInvestigation. | Implement and snapshot diagnostic distributions against the canonical investigation bill line. Reconcile historic rule percentages separately from already-earned distribution amounts. |
| Finance and history | Medistop has expenses, salaries, payment, and commission reporting. Refund-related code exists, but the repository's latest migration drops the bill_refunds table; verify the client database and deployed migration history before promising refund migration. MediFlow offers separate finance/report modules and has overlapping finance models, but the old diagnostic staff routes are absent. | Inventory which ledgers the client expects to carry forward. Map each ledger with opening balances and source references; do not assume bills alone recreate cash, salary, expense, refund, or commission history. |
| Access and roles | Medistop staff type is a field on a generic staff role. MediFlow uses roles such as lab_scientist, lab_technician, radiologist, and radiographer, plus module access. Lab routes show auth/verified middleware; radiology routes require the radiologist role. | Map every source user to a MediFlow identity, department, role, and licensed module access. Add and test route/action authorization for technicians, scientists, radiographers, reporters, and administrators, including direct URL and Livewire requests. |
| Package composition | Medistop runs alone. MediFlow can combine diagnostic modules with patient records, billing, finance, and other licensed modules. | Confirm the client’s final module set, dependencies, sidebar visibility, and shared billing/finance ownership before enabling the package. |

## Proposed data mapping

| Medistop source | MediFlow target | Migration rule |
| --- | --- | --- |
| categories | departments + investigation_types | A source category may need a department assignment plus an investigation type. Keep an explicit mapping table; do not assume every category name is a department. |
| services | investigations | Preserve source ID as an external reference and preserve code/name/current price/active state. Create stable codes where the source has no code. |
| bills | bills + patient visit or walk-in link | Keep the source bill number in a legacy/reference field and allocate a unique bill_number if required. Preserve bill dates, totals, discounts, payment state, patient text, and source ID. |
| bill_items | one investigation_requests row plus bill_investigations row per line | Keep all lines on their original bill. Preserve line price, source IDs, and result link. Avoid creating duplicate charge rows. |
| payments | payments | Preserve payment ID/reference, date, amount, method, status, and payer mapping. Recalculate target balance from migrated successful payments and compare with source balance. |
| bill_results | canonical diagnostic report, with a legacy BillResult bridge only if needed | Preserve clinical note, findings, impression, status, timestamps, performer, reporter, and source bill-line identity. |
| result_templates | result_templates | Preserve owner mapping and template text. Resolve deleted or unmapped owners to an explicit migration owner or export for manual recreation. |
| revenue_sharing_rules | mapped diagnostic distribution rules | Preserve percentages and effective date/version if recoverable. Verify totals and rounding for every category. |
| revenue_distributions | diagnostic bill-line distribution records | Preserve booked amounts as historical facts; do not recalculate old amounts using current rules. |
| users, staff_type, designation | users, roles, department, module-user access | Match by verified identity/email plus client confirmation. Keep a source-user-to-target-user map for authorship and financial audit. |
| expense, salary, revenue, and report records | corresponding MediFlow finance/report ledgers where supported | Migrate only after field/status mapping and accounting sign-off. Preserve source references and PDF/archive links when the target has no direct equivalent. |

## Recommended implementation sequence

1. **Client inventory and decisions**
   - Record source and target versions, database engine, row counts, latest activity, file storage, bill-number rules, currency/time zone, and user list.
   - Confirm whether historical reports must remain editable or become read-only archives.
   - Confirm patient identity rules, whether unpaid work may proceed, and the commission policy for new and historic bills.

2. **Target configuration**
   - Activate the diagnostic-center module set alongside any other contracted packages.
   - Create/confirm departments, investigation types, catalog, payment methods, roles, user departments, and module access.
   - Configure logo, name, address, report footer, and any legal/clinical report content.

3. **Close the target workflow gaps**
   - Select the canonical report and bill-line models. Prefer a single request-linked report path used by lab and radiology, with structured values plus narrative sections and attachment support.
   - Connect report templates, printable reports, performer/reporter lifecycle, and diagnostic revenue distribution to that path.
   - Verify package licensing and per-user/action authorization across routes, controllers, and Livewire calls.

4. **Build a repeatable importer**
   - Use a migration command/job with source-to-target ID maps, checkpoints, idempotency keys, dry-run output, row-level error log, and resume support.
   - Import catalogs and mapped users first; then patients/walk-ins and visits; then bills, requests, and line items; then payments; results/templates; finally historical revenue and finance ledgers.
   - Store source table/ID on imported records or in a durable cross-reference table. Keep original bill/result exports for audit and rollback.

5. **Rehearse and reconcile**
   - Rehearse on a copy of the client database and a separate MediFlow installation configured with the final package composition.
   - Reconcile counts and totals by month, status, department, payment method, and bill. Check every imported result's bill line and patient/walk-in link.
   - Verify report printing, result history, commission calculations, user access, and mixed-package navigation with mapped user accounts.

6. **Cutover**
   - Agree a source freeze window or delta-import process, take verified backups, run the final import, and keep Medistop read-only until acceptance.
   - Obtain sign-off for financial totals, clinical report history, and user access before decommissioning the standalone instance.
   - Retain a documented rollback path and the source-to-target mapping.

## Acceptance criteria

- Every source bill, bill line, payment, result, template, user, and agreed financial record is either imported and linked or listed in an exception report with an owner and disposition.
- Source and target bill totals, discounts, paid amounts, outstanding balances, and historical distribution amounts reconcile within an approved rounding tolerance.
- Each migrated diagnostic request is attached to the correct target bill and patient or walk-in record; multi-item bills remain grouped.
- Historical reports retain performer/reporter identity, timestamps, text, and printable/archive access.
- Current lab and radiology flows enforce the approved paid/unpaid policy, role split, edit rules, and package/user access.
- A client can enable diagnostic-center features together with other licensed packages without duplicate charges, duplicate patient records, or diagnostic access leaking to unrelated roles.

## Decisions required before implementation

1. Should new diagnostic work use the existing parameter-based InvestigationResult model, the legacy narrative BillResult model, or a unified report model?
2. Should patient identity be migrated only when a reliable identifier exists, with the rest stored as walk-in records?
3. Does the client require historic finance, salary, expenses, refunds, report PDFs, and staff commissions in MediFlow, or is a read-only archive acceptable for some ledgers?
4. Should the source paid-bill restriction remain exact, or should MediFlow support approved exceptions such as emergency or credit-account workflows?
5. What is the final package composition, and which roles should be able to register, perform, report, edit, print, and view each diagnostic record?
## Patient identity decision update (2026-10-03)

The migration will retain Medistop `patient_id` as the durable source identity. It will look up MediFlow records by hospital number or phone, stop for review if both identifiers point to different patients, and will not match by name alone. If no target patient matches, it will create/reuse a `WalkinPatient` for that source ID. Diagnostic bill contacts will retain the source ID, phone, and hospital number per bill; a missing phone remains null rather than being fabricated.
