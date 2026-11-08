# Installation Setup

The local installation now has a super-admin-only configuration interface at `/admin/installation`. This implements the local package setup requested in September 2026 and supersedes earlier guidance that all license configuration must be external.

## Use

Sign in with an installation administrator account and open **Installation Setup** in the sidebar. Enter the organization name, choose Pharmacy, General Clinic, Diagnostic Center, Maternity Clinic, Hospital, or Enterprise Hospital, then review the modules and save. Choosing a package resets the draft module selection to that preset; it does not save until Save installation is clicked.

Core and Access Control are required. Other modules can be enabled as add-ons or disabled. Dependencies are validated before saving. Branch Management is reserved for Enterprise. These settings enable existing application capabilities; they do not install separate software or implement additional branch workflows.

Saving updates the latest installation license and records an explicit enabled/disabled row for each known module inside a transaction. Existing operational records, staff roles, and module assignments are retained. Disabled features are hidden in navigation and blocked on direct requests, including persistent Livewire requests. Configured installations use a dashboard listing available workspaces. Activity report screens and exports filter their datasets by enabled modules.

Inactive, future-dated, or expired saved licenses leave only Core and Access Control enabled, while the setup screen remains accessible to installation administrators. An expired license cannot fall back to a previous license or the default hospital package. Before a license is configured, the configured default plan is used for compatibility.

## Administration

The installation-administrator flag is separate from editable staff roles and is not mass assignable. Ordinary administrators cannot modify an installation administrator through User Management. Grant/revoke this flag for an existing account from the server:

```sh
php artisan installation:admin someone@example.com
php artisan installation:admin someone@example.com --revoke
```

Apply the additive `2026_09_27_000001_add_installation_admin_to_users` migration on other installations. No default credentials are seeded. Role permissions and staff module access remain separate from installation configuration.

Package definitions/dependencies: `config/mediflow_modules.php`.
Route restrictions: `config/installation_routes.php`; all matching rules apply and `|` means either module can support a shared route. Add rules when introducing new feature routes. Package checks do not grant operational role permissions and do not constitute cryptographically signed remote licensing.

## Verification

```sh
php vendor/phpunit/phpunit/phpunit tests/Feature/InstallationSetupTest.php
php artisan view:cache
```

Tests use an isolated in-memory SQLite schema and cover setup authorization, revoked access, presets, dependency validation, explicit disabled modules, inactive/expired licenses, direct route restrictions, middleware persistence, and report datasets.

## System branding and welcome pages

The same Installation Setup screen includes a separate **System branding** form. It saves brand name, organization address, optional welcome heading and statement, logo, and welcome template without altering the installation package or staff access.

Logo uploads accept PNG, JPEG, and WebP up to 2 MB and 2048 ? 2048 pixels. Logos use generated filenames on the private local disk and are served by the read-only `/branding/logo` endpoint; no storage symlink is required. Replacement/removal cleans up the previous logo after the database save succeeds. SVG uploads are rejected. Changes are audited as `system.branding_updated`.

Automatic template selection follows the saved installation package. Seven styles are available: Pharmacy, Clinic, Diagnostics Center, Maternity Clinic, Hospital, Enterprise, and Care Network. A manual selection overrides the automatic choice without changing module access. Empty welcome text uses the selected template?s default wording. Welcome text is escaped, not rendered as HTML. The preview uses the selected package; save package changes separately to apply them publicly.

Saved branding appears on the welcome page, login screen, app favicon/sidebar, and existing web receipt/report headers that read `app.name`, `app.title`, or `app.address`. No `.env` editing is required. The original marketing welcome page remains until branding is first saved. Settings live in the singleton `system_settings` row, separate from license data. Apply `2026_09_28_000001_create_system_settings_table` when upgrading another installation.

## Department heads and technician roles

Department-specific head roles are named from the department, for example `head_of_pharmacy` (Head of Pharmacy) and `head_of_laboratory` (Head of Laboratory). Administrators assign these roles and the matching department through User Management. Existing `head_of_department` assignments remain supported; existing users are not automatically reassigned. A named head role only acts as an HOD when its department matches the user's assigned department.

HODs use `/department/users` to create staff, change names/login details, and assign operational roles within their own department. Staff role choices follow `config/department_staff.php` and the enabled installation modules. Unrecognized department names require an administrator to add the appropriate mapping. HODs cannot edit themselves, other departments, installation administrators, administrator/head accounts, or accounts holding roles outside the department's allowed set. Role and user updates are transactional and audited without recording passwords. Associated operational module access is updated alongside the roles.

Department Users uses the Access Control module, so it is available in standalone installations without enabling the full Departments & Inventory package. Installation Setup remains restricted to installation administrators, and no mandatory activation or remote licensing restriction was added.

`pharmacy_technician` is an operational role for pharmacy sales and dispensing; it does not grant medicine/stock management or pharmacy finance administration. `lab_technician` reuses the existing role instead of introducing a duplicate.

Apply the focused update on existing installations:

```sh
php artisan db:seed --class=TechnicianRoleSeeder --force
```

This creates/updates named department-head roles, adds Pharmacy Technician, ensures Lab Technician exists, and updates Department Users navigation. It preserves existing role assignments and permissions rather than reseeding users or resetting access.

Tests: `php vendor/phpunit/phpunit/phpunit tests/Feature/DepartmentUsersTest.php tests/Feature/InstallationSetupTest.php`.

## Specialist and companion packages

Specialist is the seventh installation package. Choose the primary package for branding and optional companion packages for combined operations. The setup form suggests the union of their modules; review the actual module selection before saving. Explicit saved module rows remain authoritative. Enterprise can be a companion package when branch management is required; this does not establish cross-branch clinical isolation.

Existing installations are backfilled to their current normalized package. Deployment does not activate Specialist or assign clinical privileges. Installation Setup remains accessible to authorized installation administrators.

See [Specialist Operations](SPECIALIST_OPERATIONS.md) for migrations, focused role seeding, staff provisioning, practice configuration, verification and rollback. [Specialist Package Implementation Specification](SPECIALIST_PACKAGE_IMPLEMENTATION_SPEC.md) retains the design and acceptance contract.

## Care Network naming and Partner Network

Care Network is now the displayed name of the Specialist package. The persisted `specialist` key remains valid; `care_network` is an alias. The Specialist Care workspace retains its clinical name. Partner Network is a separately selectable module depending on Specialist Care; new preset suggestions include it while existing saved module selections are preserved. See [Care Network Operations](CARE_NETWORK_OPERATIONS.md) for the additive upgrade and restricted partner portal.

## Administrator registration

Installation Setup includes a separate **Administrator registration** form for the full name, email, password and password confirmation. Save system branding first on a new installation. The brand-based email is only an editable suggestion; registering the account requires an explicit form submission. New passwords require at least eight characters and matching confirmation.

The form manages one linked operational administrator. Existing linked accounts populate the form. Leave both password fields empty when updating to preserve the password; enter and confirm a new password to change it. Duplicate emails, deleted/partner-only/installation-admin account links and unauthorized access are rejected. Passwords are hashed and excluded from audit data and success messages; form password fields clear after submission.

Branding and package saves no longer create or rename accounts. Existing accounts, including the previously provisioned administrator, are preserved. The account receives the existing administrator role, not installation-super-admin authority. Additional staff remain managed through User Management.

The existing `2026_09_30_000002_link_installation_administrator.php` migration remains required. The earlier automatic provisioning command has been removed; use the registration form. No new migration is needed for this form change.


## Welcome page appearance

In **Installation Setup > System branding**, the installation super administrator can upload a welcome background, main welcome image, and supporting graphic, in addition to the existing logo. These assets are public welcome-page content; do not upload patient information. Main and supporting images have optional screen-reader descriptions. The background is decorative and uses a light overlay to help preserve readability.

Choose accent, welcome background, and welcome text colors with the color picker or a six-digit hex value (`#RRGGBB`). Clear a color with **Use template default**. Existing installations retain their package templates until an override is saved. Appearance overrides apply to the public welcome page; application branding continues to use the saved name and logo.

Images accept PNG, JPEG, and WebP, up to 4 MB and 4096 x 4096 pixels per artwork image. Logo limits remain 2 MB and 2048 x 2048 pixels. Each artwork slot has an upload preview and a removal checkbox. Replacements and removals take effect with **Save system branding**. Use **View saved welcome page** to review the complete layout on desktop and mobile. Replacement files are retained only after settings save successfully; superseded files are deleted after commit. Changes are audited.

Deploy migration `2026_09_30_000003_add_welcome_appearance_to_system_settings.php` before using these controls. Images use the local disk and controlled public branding routes, so a public storage symlink is unnecessary. Include local branding files and the system settings table in backups.
