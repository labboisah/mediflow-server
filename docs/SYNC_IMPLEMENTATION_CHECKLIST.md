# Implementation Checklist - Copy-Paste Ready

Use this as your actual implementation guide. All code is copy-paste ready.

---

## Step 1: Verify Files Exist

All these files should now exist in your project:

```
✓ routes/api.php
✓ config/sync.php
✓ app/Models/SyncOperation.php
✓ app/Models/SyncConflict.php
✓ app/Models/Traits/Syncable.php
✓ app/Http/Controllers/Api/SyncController.php
✓ app/Http/Middleware/AuthenticateSyncToken.php
✓ app/Jobs/SyncRecordJob.php
✓ app/Observers/SyncObserver.php
✓ app/Console/Commands/SyncStatus.php
✓ app/Console/Commands/SyncRetry.php
✓ app/Console/Commands/SyncInitialize.php
✓ database/migrations/2026_04_29_000001_create_sync_operations_table.php
✓ database/migrations/2026_04_29_000002_create_sync_conflicts_table.php
✓ database/migrations/2026_04_29_000003_add_sync_columns_to_models.php
✓ SYNC_QUICK_START.md
✓ SYNC_IMPLEMENTATION_GUIDE.md
✓ SYNC_IMPLEMENTATION_SUMMARY.md
✓ SYNC_MODEL_EXAMPLES.md
```

Run this to verify:
```bash
ls -la app/Models/Traits/Syncable.php
ls -la routes/api.php
ls -la config/sync.php
```

---

## Step 2: Update Your Models

### Patient Model

Open `app/Models/Patient.php` and add the trait:

**FIND THIS:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
```

**CHANGE TO:**
```php
<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use Syncable;

```

### Admission Model

Open `app/Models/Admission.php` and add the trait:

**FIND THIS:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
```

**CHANGE TO:**
```php
<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use Syncable;

```

### AntenatalCare Model

Open `app/Models/AntenatalCare.php` and add the trait:

**FIND THIS:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AntenatalCare extends Model
{
```

**CHANGE TO:**
```php
<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class AntenatalCare extends Model
{
    use Syncable;

```

### Labour Model

Open `app/Models/Labour.php` and add the trait:

**FIND THIS:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Labour extends Model
{
```

**CHANGE TO:**
```php
<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Labour extends Model
{
    use Syncable;

```

### Delivery Model

Open `app/Models/Delivery.php` and add the trait:

**FIND THIS:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
```

**CHANGE TO:**
```php
<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use Syncable;

```

---

## Step 3: Update .env Files

### On Local Hospital Server

Edit `.env` and add/update these lines:

```env
SYNC_ENABLED=true
SYNC_ENVIRONMENT=local
SYNC_REMOTE_ENDPOINT=https://your-online-server.com
SYNC_REMOTE_TOKEN=hospital-sync-token-here
SYNC_API_TOKEN=online-server-sync-token-here
SYNC_QUEUE_CONNECTION=database
SYNC_QUEUE_NAME=sync
SYNC_AUTO_SYNC_ENABLED=true
SYNC_CONFLICT_RESOLUTION=last_write_wins
SYNC_ALLOW_REMOTE_DELETES=false
```

### On Online Server

Edit `.env` and add/update these lines:

```env
SYNC_ENABLED=true
SYNC_ENVIRONMENT=online
SYNC_REMOTE_ENDPOINT=http://hospital-local-server.local
SYNC_REMOTE_TOKEN=online-server-sync-token-here
SYNC_API_TOKEN=hospital-sync-token-here
SYNC_QUEUE_CONNECTION=database
SYNC_QUEUE_NAME=sync
SYNC_AUTO_SYNC_ENABLED=true
SYNC_CONFLICT_RESOLUTION=last_write_wins
SYNC_ALLOW_REMOTE_DELETES=false
```

---

## Step 4: Generate Secure Tokens

On local hospital server, run:

```bash
php artisan tinker
>>> bin2hex(random_bytes(32))
# Copy the output - this is your hospital-sync-token-here
# Exit tinker with: exit
```

On online server, run:

```bash
php artisan tinker
>>> bin2hex(random_bytes(32))
# Copy the output - this is your online-server-sync-token-here
# Exit tinker with: exit
```

Now update `.env` files with the tokens you generated:
- Hospital `.env`: Put online-server token in `SYNC_REMOTE_TOKEN`, hospital token in `SYNC_API_TOKEN`
- Online `.env`: Put hospital token in `SYNC_REMOTE_TOKEN`, online token in `SYNC_API_TOKEN`

---

## Step 5: Run Migrations

On BOTH servers, run:

```bash
cd /path/to/fayhos
php artisan migrate
```

Expected output:
```
Migrating: 2026_04_29_000001_create_sync_operations_table
Migrated:  2026_04_29_000001_create_sync_operations_table (123ms)

Migrating: 2026_04_29_000002_create_sync_conflicts_table
Migrated:  2026_04_29_000002_create_sync_conflicts_table (45ms)

Migrating: 2026_04_29_000003_add_sync_columns_to_models
Migrated:  2026_04_29_000003_add_sync_columns_to_models (890ms)
```

---

## Step 6: Initialize Existing Records

On BOTH servers, run:

```bash
php artisan sync:initialize
```

This generates `sync_uuid` for all existing Patient, Admission, AntenatalCare, Labour, and Delivery records.

Check it worked:
```bash
php artisan tinker
>>> App\Models\Patient::count()
>>> App\Models\Patient::whereNull('sync_uuid')->count()
# Should be 0 if all initialized
```

---

## Step 7: Start Queue Workers

On BOTH servers, open a new terminal and run:

```bash
cd /path/to/fayhos
php artisan queue:work --queue=sync
```

You should see:
```
[2026-04-29 10:30:00] Processing: App\Jobs\SyncRecordJob
```

Keep this terminal open while testing.

---

## Step 8: Test Sync Works

### Terminal 1: Monitor queue
On local hospital server, ensure queue worker is running:
```bash
php artisan queue:work --queue=sync
```

### Terminal 2: Create test record
On local hospital server, open another terminal:
```bash
php artisan tinker

# Create a patient
>>> $patient = App\Models\Patient::create(['first_name' => 'Test', 'last_name' => 'Patient', 'email' => 'test@hospital.local']);
>>> $patient->sync_uuid
# Should return a UUID

# Create an admission
>>> $admission = App\Models\Admission::create(['patient_id' => $patient->id, 'admission_date' => now()]);
>>> $admission->sync_uuid
# Should return a UUID
```

### Terminal 1: Watch queue process
In Terminal 1, you should see the queue worker processing the sync jobs:
```
[2026-04-29 10:30:05] Processing: App\Jobs\SyncRecordJob
[2026-04-29 10:30:06] Processed:  App\Jobs\SyncRecordJob (1234ms)
```

### Verify on Online Server
Log into the online server and check:
```bash
php artisan tinker
>>> App\Models\Patient::where('sync_uuid', 'THE-UUID-FROM-HOSPITAL')->first()
# Should find the patient!
```

### Check Sync Status
```bash
php artisan sync:status
```

Should show:
```
Synced: X
Pending: 0
Failed: 0
```

---

## Step 9: Monitor Sync

Running the monitoring dashboard:

```bash
php artisan sync:status
```

This shows:
- Count of operations by status
- Recent failed operations with errors
- Pending operations queue
- Current configuration

---

## Step 10: Create a Supervisor Job (Production)

On both servers, create `/etc/supervisor/conf.d/fayhos-sync.conf`:

```ini
[program:fayhos-sync-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/fayhos/artisan queue:work --queue=sync --tries=5
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/fayhos-sync.log
stopasgroup=true
```

Replace `/path/to/fayhos` with actual path.

Then run:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start fayhos-sync-worker:*
sudo supervisorctl status
```

---

## Common Issues & Fixes

### Queue workers not running
```bash
php artisan queue:work --queue=sync
# If nothing shows, queue is empty or not configured
```

### Sync tokens mismatch
```bash
# Check your .env values match:
# Hospital SYNC_API_TOKEN should equal Online SYNC_REMOTE_TOKEN
# Online SYNC_API_TOKEN should equal Hospital SYNC_REMOTE_TOKEN
cat .env | grep SYNC_
```

### Records not syncing
```bash
# Check sync_operations table
php artisan tinker
>>> App\Models\SyncOperation::latest()->limit(5)->get();

# Check for errors
>>> App\Models\SyncOperation::where('status', 'failed')->get();

# Retry failed syncs
>>> App\Models\SyncRecordJob::dispatch(...);
```

### Network connectivity
```bash
# From hospital, test online server
curl -I https://your-online-server.com/api/v1/sync/health

# From online, test hospital (if public)
curl -I http://hospital-local-server.local/api/v1/sync/health
```

---

## Verification Checklist

Run these to verify everything works:

```bash
# 1. Check files exist
ls routes/api.php
ls config/sync.php

# 2. Check migrations ran
php artisan migrate:status | grep sync

# 3. Check sync_uuid generated on records
php artisan tinker
>>> App\Models\Patient::first()->sync_uuid

# 4. Check sync_operations table
>>> App\Models\SyncOperation::count()

# 5. Check configuration
>>> config('sync.environment')
>>> config('sync.remote.endpoint')

# 6. Check queue
php artisan queue:work --queue=sync
# Should show "Processing" when syncs happen

# 7. Check sync status
php artisan sync:status
```

---

## You're Done!

If all checks pass, your bidirectional sync system is:
- ✅ Installed
- ✅ Configured
- ✅ Running
- ✅ Ready for production

All new records created on either server will automatically sync to the other!

---

## Next: Optional Enhancements

1. Build a sync dashboard in your admin panel
2. Add conflict resolution UI
3. Set up email alerts for failed syncs
4. Create data reconciliation tools
5. Add selective field sync

See SYNC_IMPLEMENTATION_GUIDE.md for these advanced topics.
