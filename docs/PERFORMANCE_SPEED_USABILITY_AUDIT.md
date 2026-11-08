# Mediflow Performance, Speed, and Usability Audit

**Review date:** 1 November 2026  
**Review type:** Static code and documentation review  
**Scope:** Laravel/Livewire application performance, perceived speed, and user-facing usability

## Executive summary

Mediflow has a broad set of hospital workflows and a newer, responsive UI foundation. Shared UI components, role-specific dashboards, and paginated patient-register browsing are good foundations. The main risks identified in this review are concentrated in data-heavy screens:

1. Before Phase 1, the admin dashboard issued roughly 30 database queries per render and refreshed every 10 seconds. Phase 1 reduced the local synthetic-fixture refresh to no more than 10 SQL queries while preserving the ten-second refresh interval and existing dashboard content.
2. Phase 2 moved patient-register summary calculations to SQL and CSV generation to 500-row streaming batches. Synchronous PDF generation still loads all matching patients for DomPDF and remains the main unbounded export risk.
3. Phase 2 moved patient summary totals to SQL and paginated visit, admission, and payment history at 25 rows per page while retaining access to all pages. Deeply nested requests attached to each visit may still be large.
4. As users and records grow, dashboard polling, synchronous PDF reports, nested history data, and search patterns that are difficult to index can become system-wide bottlenecks. The deployment topology and capacity limits were not available for review, so horizontal-scaling readiness must be verified before adding application instances.
5. The interface is mid-transition between legacy and modern layouts. This provides a migration path but risks inconsistent navigation, terminology, and interaction patterns until the migration is complete.

These are code-level risks, not measured production incidents. The review did not run load tests, inspect production database statistics, or conduct usability sessions. Establish a representative baseline before setting final service-level targets or changing clinical workflows.

## Scope and method

The review sampled the Laravel routes, Livewire dashboards, patient-register and report controllers, shared modern layout and components, relevant migrations, package manifests, and existing UI architecture documentation. The supplied `AdminMiddleware` was also checked for user-facing failure behavior.

No production data or secrets were accessed. The report does not include a security audit. Database behavior, actual response times, frontend bundle sizes, and user satisfaction must be verified in the target deployment.

## Phase 0 baseline checkpoint

**Local verification date:** 1 November 2026  
**Environment:** PHP 8.3.16, Laravel 12.69.3, PHPUnit configured for in-memory SQLite

- `php artisan route:list --except-vendor` completed successfully, confirming the application and route table boot locally.
- `php artisan test` completed with **156 passed, 23 failed, and 710 assertions** in **238.27 seconds**.
- All 23 failures were in the existing Auth/Profile feature tests and reported `RuntimeException: This database driver does not support dropping foreign keys by name.` The failure occurs in SQLite schema migration setup, before those workflows can provide a useful application-behavior baseline. This is recorded as a pre-existing validation blocker; it was not changed as part of this phase.
- The test environment is in-memory SQLite and local-only. It does not provide production p50/p95 latency, database query plans, peak memory under representative load, bundle-size measurements, peak concurrency/soak results, or staff usability feedback.
- The repository was unchanged by verification; only this audit document was updated. `git diff --check` reported no whitespace errors.

**Checkpoint outcome:** local application boot is verified, but production-scale performance and user experience remain unmeasured. Use this test result as the regression baseline and investigate the SQLite migration blocker separately before treating the full suite as a reliable gate. At the end of Phase 0, no implementation-phase code changes had been made.

## Phase 1 dashboard optimization checkpoint

- Consolidated admin dashboard counts and totals into grouped or combined aggregate queries. The admin dashboard now stays within a **10-SQL-query budget** in the synthetic SQLite fixture, including a recent audit item; the test does not model production table sizes or concurrency.
- Medical directors no longer execute access-control, finance, sync, bill-status, or recent-activity queries that are not shown to them.
- Kept the dashboard markup, metric meanings, role-specific content, routes, and `wire:poll.10s` refresh interval unchanged. No caching or delayed data refresh was introduced.
- Added regression coverage in [AdminDashboardMetricsTest.php](../tests/Feature/AdminDashboardMetricsTest.php), asserting the expected totals and query budget for administrator dashboards and asserting medical directors do not query admin-only finance tables.
- Verification: the focused dashboard, central-licensing, and role/module-access suites passed (**22 tests, 125 assertions**). PHP syntax checks and `git diff --check` passed.
- This validates metric behavior on the local fixture only. Production latency, database I/O, and load scalability still require Phase 0 staging benchmarks.

**Checkpoint outcome:** Phase 1's local regression and query-budget checks passed, and the existing dashboard interaction flow is unchanged.

## Phase 2 data-scale checkpoint

- Patient-register summary cards now use a SQL aggregate over the filtered patient IDs and demographics instead of hydrating every matching patient. Date, search, gender, patient-type, pagination, and displayed summary semantics are covered by regression tests.
- CSV download keeps the same button, filename, columns, filters, and newest-registration-first ordering, while sending rows in 500-record chunks rather than buffering the full export in memory. A 503-patient fixture verified the complete header-plus-row output across chunk boundaries.
- Patient summary cards now calculate counts and financial totals in SQL. Visit, current admission, legacy admission, and payment history are loaded 25 records per page, with the complete records still reachable through page links. The existing summary fields, latest five-bill snapshot, and history tabs are retained.
- Verification: patient-register/history plus dashboard, licensing, and role-access suites passed (**26 tests, 149 assertions**); Blade view caching succeeded; PHP syntax checks and `git diff --check` passed.
- **Unresolved by design:** PDF generation remains synchronous and still materializes all matching patient rows for DomPDF. Moving it to a queued export or limiting the requested range would change the current download flow or output. This remains a scalability risk pending approval for an alternate user experience.

**Checkpoint outcome:** SQL summary, streamed CSV, and bounded patient-history rendering are verified locally without changing current download controls or removing access to history. PDF scaling remains an explicit residual risk; production-scale latency and memory still require staging measurements.

## Phase 3 usability checkpoint

- Added explicit tab-to-panel relationships, selected-state semantics, and keyboard focus order to the existing patient-summary tabs.
- Added an accessible name to the icon-only patient-register filter reset link.
- No clinical workflow, visible wording, route, or navigation destination was changed. The new tab semantics follow the existing Bootstrap tab markup and pagination-selected section.
- Verification: Blade templates compiled successfully; the patient-register/history and dashboard regression suites passed. This code-level accessibility improvement does not substitute for keyboard/screen-reader review or staff usability testing.

**Checkpoint outcome:** the selected patient workflows retain their visible content and navigation, with improved assistive-technology semantics. Broader role-based usability validation remains outstanding.

## Phase 4 production-controls checkpoint

- Added configurable per-query slow-query logging. Queries taking at least `DB_QUERY_TIME_THRESHOLD_MS` (default **1000 ms**) generate a warning containing only the connection name and duration; SQL text and bindings are intentionally excluded from logs.
- Confirmed the application exposes Laravel's `/up` liveness endpoint. It does not by itself prove database, queue, shared-storage, or worker readiness.
- The repository supports Redis and other shared cache/session/queue options and S3 storage, but `.env.example` currently selects database cache, database sessions, database queues, and local filesystem storage. Those defaults do not establish that multiple application instances share all state/files; deployment settings must be selected and verified with the hosting owner.
- No production environment was available. This phase did not run `php artisan optimize`, change installation `.env` values, add or tune workers, run migrations, or perform multi-instance/load tests. Run Laravel optimizations only in the deployment process after its environment is configured.
- Verification: `php artisan config:show performance` reported the 1000 ms default, `/up` was present in the route collection, and the dashboard/reporting/licensing/access regression suites passed (**26 tests, 149 assertions**). PHP syntax checks and `git diff --check` passed.

**Checkpoint outcome:** low-risk query observability is implemented without changing response behavior. Multi-instance readiness remains deployment-specific and is not claimed until shared session/cache/queue/storage, scheduler ownership, and database connection capacity are verified.

## Findings

| Priority | Area | Finding | User/system impact |
|---|---|---|---|
| High | Performance / speed | Before Phase 1, the admin dashboard performed separate counts and sums across access, patient, visit, finance, setup, and sync data. Phase 1 consolidated these reads and brought the local fixture within a 10-query ceiling. The view still uses `wire:poll.10s`, so the optimized refresh continues every ten seconds while the dashboard is open. | Repeated polling still causes load to grow with open dashboards and table size. The synthetic query ceiling is not a production load or latency measurement. |
| High | Performance / speed | The patient-register summary now calculates aggregate counts in SQL, and CSV is streamed in 500-row chunks. PDF generation still loads all matching patients synchronously into DomPDF. | Interactive summary and CSV memory use are bounded, but a broad PDF export can still time out or exhaust memory. Changing that behavior requires approval for queued delivery or an export limit. |
| High | Performance / speed | Patient summary totals are now calculated with database aggregates, and visits, current/legacy admissions, and payments are loaded in separate 25-item pages. | History page/model loads are bounded while all records remain accessible. Deeply nested requests attached to each visit can still be large and need measurement against representative data. |
| Medium | Performance / speed | Patient-register text search applies leading-wildcard `LIKE` searches to hospital number and demographic fields, including relationship subqueries. Conventional B-tree indexes generally cannot accelerate a pattern beginning with `%`. | Search can become increasingly slow as the patient and demographic tables grow. Search semantics must be preserved when evaluating alternatives. |
| Medium | Performance / speed | The patient and patient-visit migrations do not explicitly add indexes for several frequently filtered columns, including patient registration date and visit patient/date/status fields. Some other date columns, such as bill issue date and payment date, are explicitly indexed. | Some dashboard, register, and patient-history queries may require table scans as data grows. Verify the deployed schema and actual query plans before adding indexes; avoid adding indexes without measuring write and storage costs. |
| High | Scalability | Dashboard polling repeats aggregate queries for every open dashboard; the PDF export remains synchronous and unbounded. Patient histories now paginate but can still load multiple nested request records per visit. This review did not establish the deployment's number of app instances, queue/cache/session drivers, database limits, or data-retention policy. | Increasing concurrent users and years of clinical/financial records may increase database contention, response times, and memory use. Whether additional application instances can be added safely depends on shared session/cache/storage configuration and database/queue capacity; validate these rather than assuming horizontal scaling is ready. |
| Medium | Scalability | The repository contains synchronization workflows and sync-operation records, but this review did not assess expected sync volume, retry/backlog behavior, or retention/cleanup at scale. | Large or prolonged sync backlogs can compete with interactive clinical work for database, queue, and storage resources. Define capacity, monitoring, and retention policies with deployment and operations owners. |
| Medium | Usability | The documented architecture retains legacy layouts while new or migrated modules use `layouts.modern`. Both UI generations are present in the repository. | Until migration and shared interaction conventions are complete, users may encounter differences in navigation, page structure, feedback, and form behavior between modules. |
| Low | Usability / responsiveness | The modern interface has positive usability foundations: responsive page grids, a collapsible/mobile sidebar, visible dashboard refresh information, a horizontally scrollable shared table component, and a shared input component that renders a label when supplied. | These reduce navigation and responsive-layout friction on modern screens. A code review cannot establish whether the workflows are understandable or efficient for clinical staff. |
| Low | Reliability / user feedback | `AdminMiddleware` directly reads `auth()->user()->role`. Routes using it must ensure authentication middleware runs first; otherwise a guest request can fail instead of receiving a clear authentication response. | Middleware ordering mistakes can result in an error page rather than a predictable sign-in flow. Confirm route middleware ordering wherever this middleware is attached. |

### Evidence sampled

- The admin dashboard calculates separate access, hospital, finance, setup, and sync metrics in [Dashboard.php](../app/Livewire/Admin/Dashboard.php). Its view polls every ten seconds in [dashboard.blade.php](../resources/views/components/admin/dashboard.blade.php).
- The patient-register controller now aggregates summary values in SQL and streams CSV in chunks. Its synchronous PDF action still materializes all matching patient rows in [PatientRegisterController.php](../app/Http/Controllers/Admin/PatientRegisterController.php).
- Patient summary pagination and SQL totals are implemented by [PatientRegisterController.php](../app/Http/Controllers/Admin/PatientRegisterController.php) and presented in [summary.blade.php](../resources/views/admin/patient/summary.blade.php).
- The main dashboard builds role-dependent metrics and recent activity in [Dashboard.php](../app/Livewire/Dashboard.php).
- The patient-visit and patient table definitions are in [2026_02_11_000004_create_patient_visits_table.php](../database/migrations/2026_02_11_000004_create_patient_visits_table.php) and [2026_02_11_000001_create_patients_table.php](../database/migrations/2026_02_11_000001_create_patients_table.php). Bill and payment date indexes are in [2026_02_11_000008d_create_bills_table.php](../database/migrations/2026_02_11_000008d_create_bills_table.php) and [2026_02_11_000008f_create_payments_table.php](../database/migrations/2026_02_11_000008f_create_payments_table.php).
- The coexistence of legacy and modern interfaces and the intended module migration approach are described in [UI_ARCHITECTURE.md](./UI_ARCHITECTURE.md).
- The repository includes synchronization workflows; expected throughput, retry policy, and production retention/capacity settings were not established in this static review.
- [AppServiceProvider.php](../app/Providers/AppServiceProvider.php) logs duration-only warnings for queries at or above `DB_QUERY_TIME_THRESHOLD_MS`; `config/performance.php` supplies the default.
- Laravel's `/up` liveness route is configured in [bootstrap/app.php](../bootstrap/app.php). The example defaults for cache, sessions, queues, and filesystems are in [.env.example](../.env.example); shared production settings have not been verified.
- The supplied access middleware is [AdminMiddleware.php](../app/Http/Middleware/AdminMiddleware.php).

## Recommended implementation plan

The sequence below prioritizes measurable performance wins while preserving existing clinical and financial behavior.

### Phase 0 — Establish a trustworthy baseline

**Suggested duration:** 3–5 working days

- Test in a staging environment with anonymized, production-like data volumes; do not use live patient data for load testing.
- Capture p50/p95 server response times, Livewire request times, query count and duration, peak PHP memory, database slow queries, frontend asset sizes, and queue depth/age.
- Run controlled tests at expected peak concurrency and an agreed growth scenario (for example, twice the expected peak), as well as a soak test. Confirm test limits and monitoring with the hosting/database owners to avoid impacting shared or production systems.
- Document current and projected record volumes, concurrent staff sessions, report/export sizes, synchronization throughput, and database/storage capacity.
- Measure representative workflows: sign in, open a dashboard, search and open a patient, register a patient, record a clinical update, review a bill, and generate a report.
- Run basic moderated usability sessions with representatives from clinical, records, pharmacy, and finance roles. Record task completion, errors, and time-on-task.
- Inspect deployed table definitions and use the database's query-plan tools on the slow paths before changing indexes.

**Exit criteria:** Baselines and test data volumes are documented; the slowest five relevant requests and queries are identified; usability issues are linked to specific tasks and roles; peak-load behavior and the first capacity constraint are known.

### Phase 1 — Reduce repeated dashboard work

**Suggested duration:** 1–2 weeks

- Replace the admin dashboard's many individual count/sum queries with a small number of conditional aggregate queries where this remains readable and correct.
- Cache dashboard summaries briefly (initial proposal: 30–60 seconds) and set an explicit invalidation/refresh policy for records whose updates matter to operators.
- Reduce automatic polling from ten seconds to a less frequent interval or make refresh user-controlled, subject to confirming the operational need for near-real-time data.
- Ensure dashboard refresh work is bounded and does not multiply unnecessarily with each connected user; validate aggregate database load at peak concurrency.
- Remove repeated schema introspection from frequently rendered paths where schema availability can be established during deployment or safely cached.
- Confirm role-specific totals and date-boundary behavior with tests before rollout.

**Provisional acceptance targets:** no more than 10 database reads for an admin dashboard refresh after aggregation; dashboard p95 server response below 1.5 seconds on the agreed staging dataset; dashboard polling generates no more than one refresh per minute unless a user explicitly requests one. Revisit these targets after Phase 0.

### Phase 2 — Make patient lists, histories, and exports scale

**Suggested duration:** 2–3 weeks

- Calculate patient-register summary metrics in the database with aggregate queries instead of loading all matching patient models and demographics into PHP.
- Keep interactive lists paginated and select only the columns and relations required by the visible rows.
- Stream CSV generation in bounded chunks so all records are not held in PHP memory. For large PDF exports, consider an asynchronous queued export with a clear progress/completion message, or enforce and explain a product-approved size/date-range limit.
- Paginate or progressively load patient history sections. Keep the initial patient summary focused on key totals and the most recent records; load older details on demand.
- Preserve current search behavior while evaluating better search options. Benchmark query plans and representative terms before choosing full-text search, prefix search, or an exact-match-first experience.
- Add indexes only where query plans demonstrate benefit, including appropriate combinations for common filters and ordering. Test migrations against realistic table sizes and validate write overhead.
- Define and monitor sync backlog limits, retry behavior, and cleanup/retention for operational sync records without removing records required for audit or recovery.

**Provisional acceptance targets:** patient-register page and patient summary p95 below 1.5 seconds on the agreed staging dataset; CSV generation uses bounded memory independent of total row count. The current synchronous PDF path remains an acknowledged exception until a queued-export or approved size-limit behavior is authorized.

### Phase 3 — Improve consistency and usability by role

**Suggested duration:** 2–4 weeks, delivered module by module

- Prioritize high-frequency workflows identified in Phase 0 rather than migrating every screen at once.
- Establish shared patterns for page titles, navigation, search/filter placement, form validation, save/cancel actions, success/error feedback, and empty/loading states.
- Continue the modern-layout migration incrementally; verify keyboard operation, clear focus indicators, readable contrast, mobile/tablet layouts, and accessible names for controls on each migrated workflow.
- Review role-specific dashboard content and quick actions with staff. Prefer concise, actionable work queues over repeated aggregate metrics where a task-oriented view is more useful.
- Test workflow changes with staff before broad rollout; do not remove familiar actions or alter clinical steps without role-owner approval.

**Provisional acceptance targets:** at least 90% successful completion for each selected core task in moderated testing; task completion time and user-reported friction improve against the Phase 0 baseline; no critical keyboard or accessibility failures on migrated screens.

### Phase 4 — Production performance and regression controls

**Suggested duration:** ongoing after the first optimization release

- Enable Laravel production optimizations appropriate to the deployment, including configuration, route, and view caching, plus PHP OPcache.
- Use an appropriate production cache/queue backend if supported by the deployment; move large report generation and non-interactive work to queues with status and retry visibility.
- Document the scaling model and test it before increasing application instances: verify sessions and cache are shared as intended, uploaded/generated files use storage accessible to all instances, queue workers have explicit capacity, database connection limits are respected, and scheduled jobs do not run redundantly.
- Use `DB_QUERY_TIME_THRESHOLD_MS` to tune duration-only slow-query warnings to the deployment's baseline; alert on the resulting warning volume and review query plans in the protected operations environment.
- Treat `/up` as a process liveness check only. Define a separate protected readiness check if operations requires dependency status; do not expose credentials, patient data, or sensitive database details through health endpoints.
- During deployment, build the production frontend assets, run `php artisan optimize` after environment configuration, reload long-lived workers safely, and verify scheduler/queue ownership and shared cache locks before serving traffic.
- Set capacity alerts and review database connections, queue depth/age, storage growth, error rates, and p95 latency. Establish clinical/financial data retention and archival policies with authorized stakeholders; do not purge records solely to improve performance.
- Confirm compressed and cacheable frontend assets, production build outputs, and sensible cache headers. Measure actual route-level payloads before removing legacy dependencies.
- Add automated performance checks for the dashboard, patient register, patient summary, and exports using representative data. Alert on regressions in p95 latency, query count, slow-query volume, and memory.
- Re-test after framework, Livewire, database, or UI dependency upgrades.

## Measurement and review

Treat all numeric targets above as provisional until Phase 0 is complete. Compare like-for-like environments, datasets, concurrency levels, and user tasks before and after each release. Review the outcomes with clinical and operations stakeholders, especially where a change affects refresh frequency, search behavior, report limits, data retention, or when clinical history is loaded.
