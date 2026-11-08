# Database Schema Duplicate Review

## Purpose

This note records the duplicate migration review and the cleanup approach used for the current database schema.

The goal is to stop fresh migrations from failing because the same table is created twice, while preserving compatibility columns that older controllers, views, or reports still reference.

## Duplicate Table Creates Found

The migration scan found duplicate `Schema::create()` definitions for these tables:

| Table | Canonical create migration | Later duplicate migration | Resolution |
| --- | --- | --- | --- |
| `audit_logs` | `2026_02_10_000010_create_audit_logs_table.php` | `2026_02_27_084934_create_audit_logs_table.php` | Kept the rich audit-log schema and changed the later migration to add legacy `description` and `user_id` only if missing. |
| `services` | `2026_02_11_000008a_create_services_table.php` | `2026_02_26_140837_create_services_table.php` | Kept the service schema used by the app and changed the later migration to add optional `category_id` only if missing. |
| `bills` | `2026_02_11_000008d_create_bills_table.php` | `2026_02_26_192437_create_bills_table.php` | Kept the billing schema used by Livewire/accountant flows and changed the later migration to add legacy summary columns only if missing. |
| `payments` | `2026_02_11_000008f_create_payments_table.php` | `2026_02_26_201738_create_payments_table.php` | Kept the payment schema with `payment_id`, `payment_method_id`, `paid_by`, and `payment_date`; changed the later migration to add legacy fields only if missing. |
| `expenses` | `2026_02_27_093641_create_expenses_table.php` | `2026_03_17_230332_create_expenses_table.php` | Kept the first expense ledger schema and changed the later migration to add department/category/creator fields only if missing. |

## Cleanup Rule

Do not add a second `Schema::create()` migration for an existing table.

Use this pattern instead:

```php
if (! Schema::hasTable('table_name')) {
    return;
}

Schema::table('table_name', function (Blueprint $table) {
    if (! Schema::hasColumn('table_name', 'new_column')) {
        $table->string('new_column')->nullable();
    }
});
```

## Compatibility Notes

- `bills.balance` was not re-added as a physical duplicate because `App\Models\Bill` already exposes `balance` through `getBalanceAttribute()`.
- The old duplicate bill migration column was retained as `legacy_balance` to avoid conflicting with the model accessor.
- Later duplicate migrations now behave as compatibility migrations, not replacement table definitions.
- Existing app code still contains mixed legacy and newer field names. Schema cleanup prevents migration collisions, but controller/model cleanup should follow so the app has one billing and payment vocabulary.

## Verification

The patched duplicate migrations passed PHP syntax checks.

The full migration set was also checked with:

```bash
php artisan migrate --pretend --no-interaction
```

That command completed successfully after also removing a migration-order dependency from `2026_04_29_000003_add_sync_columns_to_models.php`. That migration now uses a fixed list of syncable tables instead of requiring `User::find(1)` to exist.
