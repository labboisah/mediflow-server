# FHIR Readiness and Enablement Plan

**Review date:** 1 November 2026  
**Review type:** Static code and documentation review  
**FHIR baseline proposed for the first implementation:** HL7 FHIR R4 (4.0.1), subject to target-partner confirmation  
**Scope:** Compare MediFlow's current application and data model with FHIR and define a safe, staged path to interoperability.

## Executive summary

MediFlow already stores many of the clinical concepts that FHIR exchanges: patients and demographics, visits, admissions, appointments, vital signs, diagnoses, investigation orders and reports, prescriptions, dispensing, and maternity records. These are useful mapping sources, but the reviewed application did not previously expose FHIR. The read-only adapter now adds Patient, Encounter, structured vital-sign Observation, ServiceRequest, DiagnosticReport, Medication, MedicationRequest, and MedicationDispense APIs plus a `CapabilityStatement`; it is not yet a broad or partner-certified FHIR implementation. The existing synchronization API remains a separate MediFlow-specific protocol.

The recommended approach is an additive FHIR API layer over the existing application—not a replacement of its database, clinical workflows, or synchronization endpoints. Start with read-only exchange of a small, well-defined clinical dataset, stabilize identifiers and terminology, then consider carefully authorized writes. R4 (4.0.1) is a proposed compatibility baseline, not a claim that it is the universally preferred or current FHIR release; confirm the target partner's supported release and national or domain Implementation Guide before locking terminology, profiles, or write behavior. Publish the exact resource/profile/interaction support in a `CapabilityStatement`.

This is a repository-level readiness assessment, not a FHIR conformance certification. It does not validate production data, local terminology, legal basis, partner requirements, or deployment security.

## Initial adapter implementation status

The first additive implementation slice is now present in the application:

- `GET /api/fhir/R4/metadata` publishes a FHIR R4 `CapabilityStatement`.
- `GET /api/fhir/R4/Patient` and `GET /api/fhir/R4/Patient/{id}` support bounded search and read by persisted FHIR logical ID.
- `GET /api/fhir/R4/Encounter` and `GET /api/fhir/R4/Encounter/{id}` expose `PatientVisit` records as read-only Encounter resources.
- `GET /api/fhir/R4/Observation` and `GET /api/fhir/R4/Observation/{id}` expose structured `VitalSign` records as Observation panels. The separate legacy nursing `Observation` model is not exported because its measurements and units are not sufficiently defined.
- `GET /api/fhir/R4/ServiceRequest` and `GET /api/fhir/R4/ServiceRequest/{id}` expose both general `ServiceRequest` and diagnostic `InvestigationRequest` records for registered patients. The FHIR logical ID map uses source-qualified keys so IDs from the two local tables cannot collide. Walk-in orders are excluded because they do not have a mapped FHIR Patient.
- `GET /api/fhir/R4/DiagnosticReport` and `GET /api/fhir/R4/DiagnosticReport/{id}` expose reports linked to patient-based investigation requests. Reports link back to their ServiceRequest; performed reports map to `preliminary`, reported reports to `final`, and a latest recorded amendment to `amended`.
- Service and investigation catalog names and free-text diagnoses remain text; the local investigation code is not assumed to belong to a standard terminology. Diagnostic report clinical note, findings, and impression are returned as a text attachment; impression is also mapped to `conclusion`. Uncoded parameter results, private report images, and user actors are not exported as Observation, media, or Practitioner resources.
- `GET /api/fhir/R4/Medication` exposes the local medicine catalogue, `MedicationRequest` exposes one resource per patient-linked prescription item, and `MedicationDispense` exposes actual pharmacy dispense movements linked to a prescription item. Requests refer to both Medication and MedicationRequest logical IDs; dispenses link to their patient, encounter, medication, and authorizing request.
- Medicine name, generic name, and strength are retained as text in `Medication.code.text`; form and prescription directions remain local text. No standardized medication/ingredient code or dose quantity is inferred, and manufacturer/description fields are omitted because no safe equivalent is modeled. Prescriber/performer users are omitted because no Practitioner identity mapping exists. A paid parent prescription remains an active MedicationRequest unless its item is explicitly stopped. MedicationDispense quantity is omitted because inventory quantities have no unit. Only patient-linked prescription dispenses (`type=dispense`) are exported; walk-in sales and unrelated stock movements are excluded.
- Search results are bounded to 100 resources per page, use `Bundle.link` continuation links, and support only the search parameters declared in the `CapabilityStatement`. All resources use stable, persisted FHIR logical IDs separate from local database keys.
- Patient output maps the existing hospital number, demographic name, gender, birth date, telephone, email, and address fields where present. It does not infer a coded clinical profile.
- Encounter status uses a documented mapping from local visit statuses; visit type and reason remain text. Encounters with an admission record are represented as inpatient class (`IMP`), otherwise ambulatory (`AMB`).
- Vital-sign components use LOINC codes and the clinically approved units: temperature °C, blood pressure mmHg, heart/respiratory rate per minute, oxygen saturation %, weight kg, and height cm. Blood glucose is omitted because its unit is not stored or approved. Observation status is `unknown` because no status field exists locally.
- Each resource requires a resource-specific Sanctum token ability and an active MediFlow user with its corresponding application permission: Patient (`fhir.patient.read` + `patient.read`), Encounter (`fhir.encounter.read` + `visit.read`), Observation (`fhir.observation.read` + `vital_sign.read`), ServiceRequest (`fhir.servicerequest.read` + both `investigation_request.read` and `service_request.read`), DiagnosticReport (`fhir.diagnosticreport.read` + `investigation_result.read`), Medication (`fhir.medication.read` + `medicine.read`), MedicationRequest (`fhir.medicationrequest.read` + `prescription.read`), and MedicationDispense (`fhir.medicationdispense.read` + `dispense.read`). Authenticated reads/searches write an audit event without recording search values or clinical payload; when the existing audit queue is enabled, its worker must be monitored and kept operational.
- The FHIR API is disabled by default (`FHIR_API_ENABLED=false`). Sanctum tokens expire after 30 days by default (`SANCTUM_EXPIRATION=43200` minutes).
- Existing clinical routes and `/api/v1/sync` are unchanged. The adapter is read-only and advertises only the eight resource types documented above with read/search interactions.

### Phase 2 implementation checkpoint

- The generic read-only R4 surface now covers Patient, Encounter, Observation, ServiceRequest, and DiagnosticReport. ServiceRequest combines the two local order sources using distinct namespaced logical-ID keys; DiagnosticReport links only to patient-based investigation orders and preserves latest-amendment status.
- Focused adapter verification passes: `php artisan test --compact tests/Feature/FhirPatientApiTest.php` reports 11 tests and 101 assertions passing. PHP syntax, Pint formatting checks, route listing, and `git diff --check` also pass.
- The API changes are additive; the existing user-facing clinical routes and `/api/v1/sync` handlers were not changed. This checkpoint does not replace partner-specific R4/Implementation Guide validation or the broader clinical regression suite.

### Phase 3 base-R4 validation checkpoint

- `FhirPatientApiTest` includes an opt-in conformance test that builds a synthetic FHIR `Bundle` from the generated Patient, Encounter, Observation, ServiceRequest, DiagnosticReport, Medication, MedicationRequest, and MedicationDispense API responses, then invokes the official HL7 validator. The test skips when `FHIR_VALIDATOR_JAR` is not set; it never submits real patient data.
- The validator is pinned to HL7 FHIR validator CLI `7.0.3`, run with FHIR version `4.0.1`. The official GitHub release publishes SHA-256 `57aa43fb40cec5de41def81c927725fd85908eeb71509b7f9313e04d9b8ec79b`; verify the downloaded JAR against this digest before use.
- The pinned binary was downloaded from the official release, its SHA-256 digest was verified, and the synthetic cross-resource bundle containing all eight resources passed base R4 validation. With the validator enabled, the focused suite reports 13 tests and 141 assertions passing.
- Validation passes with nine non-fatal best-practice warnings: missing DomainResource narrative (`dom-6`) and an Observation performer not represented by the current actor mapping. Keep this as a dedicated conformance job and cache the validator/package data when CI is introduced; the cold first run took about seven minutes, while the cached run took about 87 seconds.
- This checks base R4 structure only; partner Implementation Guide profiles, terminology packages, and clinical workflow acceptance remain outstanding.

### Phase 4 medication read-only checkpoint

- Medication, MedicationRequest, and MedicationDispense read/search routes are additive and use separate token abilities plus existing application permissions. MedicationRequest maps each patient-linked prescription item; MedicationDispense includes only completed pharmacy dispense records linked to those orders.
- Medication/catalog labels and dose instructions remain text; the adapter does not invent drug codes, strength ratios, or dose units. Dispense quantity is omitted because inventory quantities have no unit, and walk-in sales/stock-only movements are not represented as patient medication events.
- Focused mapping and authorization tests pass, and the validator-enabled synthetic bundle passes for all eight resource types. No prescription, pharmacy, inventory, clinical screen, or `/api/v1/sync` workflow was changed.

### MedicationRequest write planning checkpoint — planning only

- **Authority decision:** MediFlow remains the source of truth. An accepted FHIR request must be validated and translated into MediFlow's existing prescription domain; it must not write around or replace the clinical workflow.
- **Proposed first write:** add only `POST /api/fhir/R4/MedicationRequest` to create one medication order item for an explicitly referenced, eligible `Encounter` and its matching `Patient`. Do not add update, delete, batch/transaction, or pharmacy-dispense writes in this slice.
- Accept only a supported `intent=order` and a status with a safe local equivalent. A newly accepted request should create an `active` MediFlow prescription for clinical/pharmacy review; it must not automatically transition the prescription to `submitted`, dispense stock, or create a payment. Unsupported statuses and intents must return an `OperationOutcome`, not be silently coerced.
- Require a reference to an existing local `Medication`; unknown products must not be auto-created from external text. Resolve the Patient and Encounter through persisted FHIR identities, then verify that the Encounter belongs to that Patient and is eligible for prescribing.
- **Prerequisite — clinical requester identity:** the current adapter has no Practitioner mapping. Before writes, define a trusted mapping from the incoming `requester` to an active, authorized MediFlow prescriber. Do not record the integration account as the clinical prescriber by default; audit both the API client and the resolved prescriber.
- **Prerequisite — dosage round-trip:** the local model stores route, dosage, period, and duration separately as a local route foreign key and text fields, while the current FHIR read mapping combines them into `dosageInstruction.text`. Define and test an explicit R4/profile mapping for these fields, including a verified route-code map, before accepting writes; do not parse free text or guess units/timing.
- **Prerequisite — idempotency:** define an identifier system owned by each integration and persist a unique external order identifier so retries return the already-created MedicationRequest rather than duplicate prescriptions. Keep it separate from the server-assigned FHIR logical ID.
- **Security and persistence:** use a dedicated write token ability and the existing `prescription.create` permission, with a separate write authorization path from the read-only middleware. Persist the prescription, item, FHIR identity/idempotency record, and clinical activity atomically; return a FHIR `201 Created` response with `Location`, and standard `OperationOutcome` errors for rejected requests.
- **Acceptance gate before coding:** approve the partner's MedicationRequest profile/required fields and requester identity policy; complete dosage/route mapping; define duplicate, validation, and audit behavior; add focused authorization, mapping, idempotency, rollback, and legacy prescription/pharmacy regression tests. Advertise the `create` interaction only after it is implemented and validated. This checkpoint makes no write-route or clinical-flow changes.

### Nigeria Implementation Guide discovery

- The [HL7 Nigeria Core R4 repository](https://github.com/HL7Nigeria/HL7-NG-FHIR-Core-R4) declares FHIR 4.0.1, canonical `http://hl7.ng`, status `draft`, and version `0.1.0`. Its public history currently shows an initial 21 October 2024 commit. Treat it as a draft candidate, not an approved national conformance requirement, until HL7 Nigeria or the receiving partner confirms its publication and applicable package/version.
- The [NPHCDA Immunization IG continuous build](https://build.fhir.org/ig/Nigeria-FHIR-Community/NPHCDA-ImmunizationIG/) declares canonical `https://nphcda.gov.ng/ig`, FHIR 4.0.1, and version `0.9.0` as a draft; the build page explicitly says it is not an authorized publication. It addresses immunization workflows, not the current diagnostic-order/report use case. Its repository README labels a draft 1.0, so obtain the publisher's approved release/version before binding production conformance to it.
- The current generic eight-resource slice has passed base R4 validation, but no Nigerian IG profile validation is claimed. Apply NPHCDA profiles only to an agreed immunization use case, and do not infer diagnostic profiles from an unrelated domain guide.
- **Checkpoint decision:** keep the current general clinical API at base FHIR R4 and defer Nigeria-specific profile binding until an applicable, approved national/domain guide and receiving-system requirements are confirmed. NPHCDA immunization remains a separate future scope.

### Enabling a controlled test integration

1. Apply application migrations, including Sanctum's `personal_access_tokens` table and the new `fhir_resource_identities` table.
2. Keep the API disabled until an integration owner approves the data scope and access policy. Configure `FHIR_SERVER_URL` to the canonical public base URL ending in `/api/fhir/R4`, and set `FHIR_PATIENT_IDENTIFIER_SYSTEM` to the stable URI namespace that identifies this facility's hospital numbers.
3. Use a dedicated, active integration account. Grant it only the application permissions required for the agreed resource scope (`patient.read`, `visit.read`, `vital_sign.read`, `investigation_request.read`, `service_request.read`, `investigation_result.read`); do not use a staff member's general-purpose token.
4. Issue a Sanctum token with only the abilities required by the approved scope using a trusted terminal and the application's credential-provisioning process. For example, in `php artisan tinker`, load the approved integration user and call `$user->createToken('fhir-partner', ['fhir.patient.read'])->plainTextToken` (add only the approved read abilities among `fhir.encounter.read`, `fhir.observation.read`, `fhir.servicerequest.read`, `fhir.diagnosticreport.read`, `fhir.medication.read`, `fhir.medicationrequest.read`, and `fhir.medicationdispense.read`). Do not issue a write ability until its separate workflow, authorization, and profile have been approved and implemented. The plaintext token is returned only once; store and transmit it through an approved secret manager/channel, not source control, chat, or shell history. Tokens expire after 30 days by default and should be revoked when no longer needed.
5. Set `FHIR_API_ENABLED=true` only in the approved environment. Require HTTPS at the ingress/reverse proxy, apply network restrictions where appropriate, and verify that read access and the generated audit events behave as intended.
6. Confirm the integration using synthetic/de-identified records and the partner's validator/sandbox before any production exchange.

This initial slice is not SMART on FHIR, does not implement OAuth authorization, does not enforce patient-specific consent/compartment rules, and is not certified against a national Implementation Guide. The integration account's application permissions allow broad resource reads within the installation; do not enable an external client until a data-sharing agreement and appropriate record-level access policy are established. Medication read/search is implemented; MedicationRequest writes, validation-operation support, and partner conformance remain future work.

## What FHIR requires from an implementation

FHIR is an exchange standard built from resources and references; it is not just a JSON format or a security protocol. A usable FHIR endpoint needs a defined FHIR version, stable resource identity, supported interactions and search behavior, terminology and profile rules, error behavior, and a declared conformance surface. The API should only claim what the deployed implementation actually supports.

For an initial RESTful server, provide at minimum:

- A versioned service root, for example `/fhir/R4`, pinned to one FHIR release.
- `GET [base]/metadata` returning an instance `CapabilityStatement` for that deployment.
- Resource endpoints using FHIR resource names and supported HTTP interactions.
- FHIR JSON responses with the correct FHIR media type and standard `OperationOutcome` errors.
- Stable server-assigned logical IDs (FHIR `Resource.id`) kept distinct from business identifiers in `Identifier`, and correct `Reference` relationships across resources.
- Supported search parameters, result paging, and self/next links in a FHIR search `Bundle`.
- Explicit FHIR R4 resource profiles and terminology rules for the agreed use cases.
- Validation against the base R4 definitions and the exact implementation-guide profiles and terminology package used by the integration.
- Authentication, authorization, audit, privacy, transport protection, and input validation implemented by the application/deployment around the FHIR API.

## Current system comparison

| FHIR concern | Current MediFlow evidence | Readiness and gap |
|---|---|---|
| FHIR endpoint and release | [routes/api.php](../routes/api.php) exposes the MediFlow-specific `/api/v1/sync/...` endpoints and the separate `/api/fhir/R4` adapter. | **Initial slice present.** The adapter exposes read/search for eight resources; it does not reinterpret or replace the sync API. |
| Capability discovery | `GET /api/fhir/R4/metadata` returns a `CapabilityStatement`. | **Initial slice present.** It declares Patient, Encounter, Observation, ServiceRequest, DiagnosticReport, Medication, MedicationRequest, and MedicationDispense read/search interactions; write interactions and partner profiles are not implemented. |
| Patient | [Patient](../app/Models/Patient.php) and [PatientDemographic](../app/Models/PatientDemographic.php) separate registration/hospital number and demographic fields; the initial patient schema uses a local numeric key and a unique hospital number. | **Initial read mapping present.** The adapter maps supported demographic fields and hospital number and uses persisted FHIR logical IDs. Confirm the facility identifier namespace, demographic authority, and missing-value behavior with the receiving partner before production. |
| Encounter and admissions | [PatientVisit](../app/Models/PatientVisit.php) contains date, type, status and reason. [Admission](../app/Models/Admission.php) links inpatient state to a visit. | **Initial Encounter mapping present.** Patient visits map to Encounter; status and class use documented mappings and do not yet expose a separate admission resource. Confirm these semantics with the partner before production. |
| Practitioner and Organization | User records are used as actors (for example `created_by`, `requested_by`, and `performed_by`); departments, wards, and the installation represent organizational context. | **Partial.** Build a curated actor/facility mapping. A MediFlow user account is not automatically a qualified FHIR `Practitioner`; publish only needed identity and role data. |
| Observation | [VitalSign](../app/Models/VitalSign.php) stores structured measurements; a separate [Observation](../app/Models/Observation.php) stores less-structured nursing/maternity notes. | **Initial vital-sign panel mapping present.** Confirmed units and LOINC component codes are emitted for structured vitals. Local status is absent, so FHIR status is `unknown`; blood glucose and the separate narrative observation model are withheld until their units/semantics are confirmed. |
| Condition / diagnosis | [Diagnose](../app/Models/Diagnose.php) exists, while diagnosis-like values also occur as clinical text in other workflows. | **Partial / uncertain.** Inspect deployed schema and actual records to identify the authoritative diagnosis source, lifecycle, encounter linkage, and code system. Free-text diagnoses cannot safely be advertised as coded `Condition` data. |
| Orders and results | [ServiceRequest](../app/Models/ServiceRequest.php), [InvestigationRequest](../app/Models/InvestigationRequest.php), [InvestigationResult](../app/Models/InvestigationResult.php), [Investigation](../app/Models/Investigation.php), and [DiagnosticReport](../app/Models/DiagnosticReport.php) support general service orders, diagnostic orders, and reports. | **Initial read mapping present with explicit limitations.** Both local order tables map to `ServiceRequest` with source-qualified stable IDs; only registered-patient orders are exposed. A linked report maps to `DiagnosticReport`, status/revision semantics are preserved, and reports reference the originating ServiceRequest. Local names and diagnoses remain text; no standard codes/units are inferred for InvestigationResult values, and walk-in orders/images are not exported. |
| Medication | [Prescription](../app/Models/Prescription.php), [PrescriptionItem](../app/Models/PrescriptionItem.php), [Medicine](../app/Models/Medicine.php), and pharmacy dispensing models represent orders, catalogue items, and dispense events. | **Initial read mapping present; writes not ready.** Medication, MedicationRequest, and patient-linked MedicationDispense are read/search only. Product labels and dosage remain text, dispensing quantity is omitted without a unit, and no standardized medication code is inferred. Before writes, complete the requester identity, structured dosage/route, status, and idempotency decisions in the MedicationRequest write-planning checkpoint. |
| Appointments and related people | [Appointment](../app/Models/Appointment.php) stores schedule, patient, status and cancellation data. [NextOfKin](../app/Models/NextOfKin.php) is a potential relationship source. | **Partial.** Map bookings to `Appointment` with explicit start/end/time-zone behavior; map a next-of-kin contact to `RelatedPerson` only after relationship and consent semantics are confirmed. |
| Allergies, procedures, documents, maternity | No dedicated allergy model appeared in the reviewed model list. Maternity entities include antenatal care, labour, delivery, newborn and postnatal records. Documents and diagnostic reports also exist. | **Mixed / gaps.** Do not infer `AllergyIntolerance` from narrative notes. Select appropriate R4 resources or agreed profiles for maternity and documents after reviewing actual fields and the partner's Implementation Guide. Identify missing structured data before claiming support. |
| Terminology and profiles | The reviewed clinical models include local strings and catalogues; no explicit FHIR terminology service or FHIR `StructureDefinition`/profile package was found. | **Not established.** Agree a jurisdiction-appropriate Implementation Guide and code systems with the receiving organizations. Maintain local-to-standard mappings and preserve original source text/codes. |
| Identity and references | Syncable records use internal `sync_uuid`/remote IDs in some workflows; ordinary database IDs also exist. | **Reusable foundation, not a FHIR identity policy.** Confirm coverage and stability per resource. Use an explicit persisted FHIR identity map or approved deterministic-ID policy; use `Identifier.system` and `Identifier.value` for business identifiers. References must resolve within the advertised FHIR base. |
| Security and audit | Existing API sync routes use a custom shared-token middleware; the application also has roles/permissions and audit logs. | **Not equivalent to SMART or FHIR authorization.** Define client/user identity, scopes, patient/record-level authorization, least privilege, rate limits, revocation, audit events, and TLS at the API boundary. Do not expose sync credentials as FHIR credentials. |
| Existing synchronization | [SYNC_IMPLEMENTATION_GUIDE.md](SYNC_IMPLEMENTATION_GUIDE.md) describes bidirectional change synchronization, queues and conflict handling. | **Keep separate.** FHIR is an interoperability interface, not a replacement for the internal sync protocol. Define transaction ownership and conflict policy if FHIR writes are later enabled. |

### Initial resource mapping proposal

These are candidate mappings for the first interoperability design, not claims that current records already satisfy the FHIR resource requirements.

| MediFlow concept | Candidate FHIR R4 resource(s) | Mapping notes |
|---|---|---|
| Registered patient + demographics | `Patient` | One patient resource per real patient; encode hospital number as a facility-scoped identifier. Define whether demographic corrections overwrite current state and how historical values are represented. |
| Visit / admission | `Encounter` | Decide whether outpatient visits and inpatient stays are separate encounters or one encounter with hospitalization details. Preserve the local encounter/admission distinction where the workflow depends on it. |
| User who provides or records care | `Practitioner`, optionally `PractitionerRole` | Only publish verified staff identity and role/department data required by the exchange use case. |
| Facility, department, ward, bed | `Organization`, `Location` | Separate facility/department organizations and physical locations only to the granularity required by partners. |
| Vital signs and structured measurements | `Observation` | Map each coded measurement (and blood-pressure panel/components) with status, effective time, quantity, UCUM unit, subject, encounter and performer. |
| Diagnosis | `Condition` | Requires a confirmed diagnosis source, clinical status, encounter context, and coded terminology strategy. |
| Investigation/lab/radiology order | `ServiceRequest` | Preserve order identifier, requester, subject, encounter, code, intent/status, and relevant timing. |
| Result measurement | `Observation` | Use for individual coded results with value, unit, reference range/interpretation where available, and request reference. |
| Grouped/narrative diagnostic report | `DiagnosticReport` | Preserve conclusion, report status/effective time, performer, result references, and revision history as appropriate. |
| Medication order | `MedicationRequest` + `Medication` | Read/search mapping is present. For planned writes, represent order intent separately from the product and complete requester identity, dosage/route, idempotency, and status mappings before accepting requests. |
| Pharmacy dispense | `MedicationDispense` | Record only actual dispense events; do not infer dispensing from an order or stock deduction. |
| Appointment | `Appointment` | Resolve local date/time/timezone fields and translate cancellation/completion states. |
| Next of kin / authorized contact | `RelatedPerson` | Include only with verified relationship and a documented privacy/consent basis. |
| Clinical document / attachment | `DocumentReference` and, where required, `Binary` | Define access control, content type, size limits, retention, and safe download behavior. |
| Data authorship and change history | `Provenance`; access/security audit where appropriate `AuditEvent` | Define actor, source, timestamp, and change-capture policy. FHIR audit resources supplement rather than replace operational security logs. |
| Billing and payment | Consider `Account`, `ChargeItem`, `Invoice`, `PaymentNotice`, and/or `PaymentReconciliation` only for an agreed financial workflow | Not a first-phase clinical interoperability scope. Validate resource fit and local financial/regulatory requirements before implementing. |

## Key design decisions before implementation

1. **Target exchange and jurisdiction:** Name the first receiving system, use case, country/jurisdiction, and applicable national or domain Implementation Guide. Confirm which FHIR release and profiles that partner actually supports.
2. **FHIR release:** Pin one release per endpoint. R4 (4.0.1) is a practical candidate because SMART App Launch 2.2 is based on R4, but HL7 has since published R5; choose based on actual partner and jurisdiction requirements. Do not mix release definitions in one endpoint.
3. **System of record:** Define which system owns each element and which direction is allowed to update it. Begin with MediFlow as the source for selected exported records, not with unrestricted two-way write access.
4. **Patient identity:** Assign a stable identifier namespace for each facility and decide how duplicate patients, merges, hospital-number changes, and records from multiple facilities are handled.
5. **Terminology:** Select the agreed code systems and value sets for diagnoses, observations, investigations, medication products, routes, units, encounter types, and statuses. Confirm licensing and deployment requirements for any terminology services.
6. **Clinical meaning:** Confirm whether local statuses, timestamps, values, units, and free text mean what the proposed FHIR elements imply. Preserve unknown or unverified values rather than inventing equivalence.
7. **Authorization and consent:** Define the API clients, human-user context, scopes, record-level rules, purpose of use, consent restrictions, audit retention, incident response, and data minimization.
8. **Operational boundary:** Decide whether to implement a Laravel adapter inside this application or a separately deployed FHIR gateway. Either option must call the same authorized application services and must not bypass domain rules.

## Staged implementation plan

### Phase 0 — Partner and data discovery

- Select one real exchange use case (for example, share a patient summary and completed diagnostic reports with one authorized partner).
- Inspect the live schema, representative de-identified records, model observers, authorization policies, sync identifiers, and actual status/code values.
- Obtain the target partner's FHIR release, Implementation Guide, profile package, supported search parameters, authentication requirements, and test endpoint.
- Produce a field-level mapping, data-quality gap list, ownership matrix, and privacy/security threat assessment.

**Exit checkpoint:** stakeholders approve the bounded use case, resource scope, release/profiles, terminology, identity policy, and whether exchange is read-only.

### Phase 1 — FHIR model and identity foundation

- Add a FHIR boundary/adapter; do not serialize Eloquent models directly or change existing clinical screens.
- Define stable FHIR logical IDs and an explicit resource identity mapping to local records and partner identifiers. Ensure migrations, restores, and sync do not create identity drift.
- Define a canonical facility base URL and correct reference generation. Avoid leaking integer database keys or user-controlled hostnames into references.
- Implement terminology mapping tables/configuration with source value, target code/system/display, mapping status, and provenance. Keep original local values.
- Specify R4 profiles/constraints and supported search parameters for every initial resource.

**Exit checkpoint:** mapping tests demonstrate deterministic IDs/references and explicit handling for missing, ambiguous, and unmappable values.

### Phase 2 — Read-only R4 API

- Add a separate `/fhir/R4` route group with FHIR-aware JSON content negotiation and `GET /metadata`.
- Implement only the agreed initial read/search resources—likely `Patient`, `Encounter`, `Observation`, `ServiceRequest`, and `DiagnosticReport`; add `Practitioner`, `Organization`, and `Location` only as needed for valid references.
- Return FHIR resources and search `Bundle`s, with paging links, correct `total` semantics, and FHIR `OperationOutcome` errors.
- Apply authorization before lookup and throughout included/reference resolution; use bounded queries and avoid unrestricted patient enumeration.
- Keep `/api/v1/sync` and current user-facing workflows unchanged. Add separate versioning, logging, metrics, and rate limits for FHIR.

**Exit checkpoint:** partner-facing contract tests verify capabilities, profiles, search filters, pagination, references, authorization, and error responses; existing Mediflow workflow/sync suites remain green.

### Phase 3 — Validation, conformance, and pilot

- Validate generated resources against FHIR R4 definitions and the selected Implementation Guide using the HL7 FHIR Validator (or an equivalent verified implementation), pinned to a documented version and package set. Validate representative resources in CI and, where useful, expose the FHIR validation operation with properly bounded input.
- Test terminology bindings, cardinality, references, date/time precision, decimal precision, narrative safety, and edge cases such as merged/deleted patients and corrected reports.
- Use synthetic or de-identified data in automated tests; keep production clinical payloads out of test logs and fixtures.
- Publish a deployment-specific `CapabilityStatement` and API documentation; run partner sandbox conformance and workflow testing.
- Pilot read-only exchange with a limited, approved user/client group. Monitor latency, errors, throttling, audit completeness, data quality, and partner reconciliation.

**Exit checkpoint:** clinical, privacy/security, operational, and partner owners accept the pilot evidence and rollback procedure before production expansion.

### Phase 4 — Controlled writes and broader scope

- Add writes only when a named use case and source-of-truth contract require them. Begin with narrowly scoped resources, strict profile validation, authorization, idempotency, and auditable transactions.
- Define duplicate handling, optimistic concurrency/versioning, transaction rollback, retries, provenance, deletion/retention, and conflict resolution with the existing sync system.
- Add further domains (medication, maternity, documents, referrals, finance, bulk export) only through separate reviewed profiles and acceptance checkpoints.
- Consider SMART App Launch/OAuth2 scopes where an app-launch integration needs delegated user context. Use the appropriate OAuth profile for the applicable ecosystem; simply putting a bearer token in an existing shared-token API is not SMART conformance.

**Exit checkpoint:** each additional write or domain has explicit clinical ownership, security/privacy review, conformance tests, observability, and a tested rollback path.

## Acceptance criteria for a first pilot

- The endpoint identifies itself as FHIR R4 and serves a valid, deployment-specific `CapabilityStatement`.
- Every advertised resource, interaction, profile, format, and search parameter is implemented and tested; unsupported operations are not advertised.
- Resource IDs remain stable across requests, and every included reference resolves to the intended resource at the advertised base URL.
- Search results are returned in a conformant FHIR `Bundle` with bounded paging; unauthorized users cannot infer patient existence or read protected data.
- Valid resources pass the pinned R4 and selected Implementation Guide validator; invalid requests receive an appropriate `OperationOutcome`.
- Local clinical values are mapped only where meanings and units are verified. Unmapped concepts remain explicit and are not silently coded.
- Authentication, authorization, TLS, audit events, rate limits, safe error/logging behavior, backup/restore, and incident response are reviewed for the deployment.
- Existing MediFlow routes, clinical workflows, licensing, and synchronization behavior are unchanged and verified by regression tests.

## Risks and cautions

- **A JSON endpoint is not automatically FHIR.** Resource shape, REST interactions, references, terminology, search, errors, version, and declared capabilities all matter.
- **Do not force a database redesign to match FHIR.** Keep FHIR as an exchange model and map to current domain services until data or interoperability evidence justifies schema changes.
- **Do not expose data merely because it can be mapped.** Each field/resource needs a documented purpose, authorization rule, consent basis, and minimization review.
- **Do not equate local strings to standard codes by guesswork.** Incorrect coding can be more harmful to downstream clinical decisions than an explicit unmapped value.
- **Do not enable writes or bidirectional sync by default.** Duplicate records, stale state, and conflicting clinical updates need explicit source-of-truth and reconciliation policy.
- **FHIR does not itself provide the security protocol.** Use TLS, robust client/user authentication, granular authorization, audit, input validation, and applicable OAuth/SMART mechanisms.
- **FHIR conformance is profile- and deployment-specific.** A generic successful JSON validation is not proof of interoperability with the intended partner.

## Source review

### Repository evidence reviewed

- API surface: [routes/api.php](../routes/api.php)
- Patient and demographics: [Patient.php](../app/Models/Patient.php), [PatientDemographic.php](../app/Models/PatientDemographic.php), [patients migration](../database/migrations/2026_02_11_000001_create_patients_table.php)
- Visits and admissions: [PatientVisit.php](../app/Models/PatientVisit.php), [Admission.php](../app/Models/Admission.php), [patient visits migration](../database/migrations/2026_02_11_000004_create_patient_visits_table.php)
- Observations, investigations, reports, medication and appointments: [VitalSign.php](../app/Models/VitalSign.php), [InvestigationRequest.php](../app/Models/InvestigationRequest.php), [InvestigationResult.php](../app/Models/InvestigationResult.php), [DiagnosticReport.php](../app/Models/DiagnosticReport.php), [Prescription.php](../app/Models/Prescription.php), [PrescriptionItem.php](../app/Models/PrescriptionItem.php), [Medicine.php](../app/Models/Medicine.php), [Appointment.php](../app/Models/Appointment.php)
- Existing sync contract: [SYNC_IMPLEMENTATION_GUIDE.md](SYNC_IMPLEMENTATION_GUIDE.md)

### Official HL7 FHIR R4 references

- [FHIR R4 Overview](https://hl7.org/fhir/R4/overview.html) — resources, references, capability statements, and profiles.
- [FHIR R4 RESTful API](https://hl7.org/fhir/R4/http.html) — interactions, service base, search, transaction, and `CapabilityStatement` expectations.
- [FHIR R4 CapabilityStatement](https://hl7.org/fhir/R4/capabilitystatement.html) — declaring an actual implementation's supported capabilities.
- [FHIR R4 Security](https://hl7.org/fhir/R4/security.html) — FHIR is not itself a security protocol; TLS, authentication, authorization, audit, input validation, and privacy responsibilities.
- [FHIR R4 Profiling](https://hl7.org/fhir/R4/profiling.html) — constraining base resources for implementation use cases.
- [FHIR R4 Terminology](https://hl7.org/fhir/R4/terminologies.html) — code systems, value sets, bindings, and terminology services.
- [FHIR R4 Resource Identity](https://hl7.org/fhir/R4/resource.html#identification), [Managing Resource Identity](https://hl7.org/fhir/R4/managing.html#7.12), and [References](https://hl7.org/fhir/R4/references.html#2.3.0) — logical IDs, business identifiers, and resource relationships.
- [FHIR R4 Validation](https://hl7.org/fhir/R4/validation.html#7.5) and [OperationOutcome](https://hl7.org/fhir/R4/operationoutcome.html#using) — validation guidance and structured error responses.
- [HL7 FHIR validator CLI 7.0.3 release](https://github.com/hapifhir/org.hl7.fhir.core/releases/tag/7.0.3) — pinned validator binary used for the optional synthetic base-R4 validation test.
- [HL7 Nigeria Core R4 draft repository](https://github.com/HL7Nigeria/HL7-NG-FHIR-Core-R4) and [NPHCDA Immunization IG continuous build](https://build.fhir.org/ig/Nigeria-FHIR-Community/NPHCDA-ImmunizationIG/) — candidate Nigeria-specific artifacts reviewed; neither is treated here as an approved profile requirement for MediFlow's diagnostic scope.
- [SMART App Launch 2.2](https://hl7.org/fhir/smart-app-launch/) — OAuth 2.0 patterns for delegated app and backend access; the published guide is based on R4. Confirm the partner-required version and flow before implementation.
