# Bidirectional Sync - Quick Start

All infrastructure for bidirectional sync is now in place. Follow these steps to activate it:

---

## Step 1: Add Syncable Trait to Models

Add the `Syncable` trait to your models. For example, in `app/Models/Patient.php`:

```php
<?php

namespace App\Models;

use App\Models\Traits\Syncable;  // Add this
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use Syncable;  // Add this line
    
    protected $fillable = [
        // ... existing fields
    ];
}
```

**Apply to these models first:**
- `app/Models/Patient.php`
- `app/Models/Admission.php`
- `app/Models/AntenatalCare.php`
- `app/Models/Labour.php`
- `app/Models/Delivery.php`

Then optionally:
- `app/Models/InvestigationRequest.php`
- `app/Models/Prescription.php`
- `app/Models/NewbornExamination.php`
- `app/Models/ChildFollowUp.php`

---

## Step 2: Configure Environment Variables

### On Local Hospital Server

Edit your `.env` file:

```env
SYNC_ENVIRONMENT=local
SYNC_REMOTE_ENDPOINT=https://your-online-server.com
SYNC_REMOTE_TOKEN=your-hospital-sync-token-here
SYNC_API_TOKEN=your-online-server-sync-token-here
SYNC_QUEUE_CONNECTION=database
SYNC_AUTO_SYNC_ENABLED=true
```

### On Online Server

Edit your `.env` file:

```env
SYNC_ENVIRONMENT=online
SYNC_REMOTE_ENDPOINT=http://hospital-local-server.local
SYNC_REMOTE_TOKEN=your-hospital-sync-token-here
SYNC_API_TOKEN=your-online-server-sync-token-here
SYNC_QUEUE_CONNECTION=database
SYNC_AUTO_SYNC_ENABLED=true
```

**Generate strong tokens:**
```bash
php artisan tinker
>>> bin2hex(random_bytes(32))
# Copy output to SYNC_REMOTE_TOKEN and SYNC_API_TOKEN
```

---

## Step 3: Run Migrations

On both servers, run:

```bash
php artisan migrate
```

This creates:
- `sync_operations` table
- `sync_conflicts` table
- Adds sync columns to Patient, Admission, and other models

---

## Step 4: Initialize Existing Records

If you have existing data, initialize it with sync UUIDs:

```bash
php artisan sync:initialize
# or for specific model:
php artisan sync:initialize --model="App\Models\Patient"
```

---

## Step 5: Start Queue Workers

The sync system requires queue workers. On both servers, run in background:

```bash
php artisan queue:work --queue=sync
```

**For production (using Supervisor):**

Create `/etc/supervisor/conf.d/fayhos-sync.conf`:

```ini
[program:fayhos-sync-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/fayhos/artisan queue:work --queue=sync --tries=5
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/fayhos-sync.log
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start fayhos-sync-worker:*
```

---

## Step 6: Test the Connection

### Check if API endpoints are accessible

```bash
# From local server, test online server
curl -H "Authorization: Bearer YOUR_SYNC_API_TOKEN" \
  https://your-online-server.com/api/v1/sync/health

# From online server, test local server
curl -H "Authorization: Bearer YOUR_HOSPITAL_TOKEN" \
  http://hospital-local-server.local/api/v1/sync/health
```

### Create a test record

On the local hospital server, create a patient:
```bash
php artisan tinker
>>> $patient = App\Models\Patient::create(['name' => 'Test Patient', ...]);
>>> $patient->sync_uuid  # Should have a UUID now
```

Watch the queue:
```bash
php artisan queue:work --queue=sync  # In another terminal
```

You should see the job execute. Check the database on the online server - the patient should appear there!

---

## Step 7: Monitor Sync

Check sync status anytime:

```bash
php artisan sync:status
```

This shows:
- Count of pending/synced/failed operations
- Failed operations with error details
- Pending operations queue
- Current configuration

---

## Step 8: Handle Failed Syncs

If syncs fail, check the logs:

```bash
tail -f storage/logs/laravel.log | grep -i sync
```

Retry failed syncs:

```bash
# Retry all failed operations
php artisan sync:retry --all

# Retry specific operation
php artisan sync:retry 550e8400-e29b-41d4-a716-446655440000
```

---

## Common Issues

### Queue workers not processing jobs

**Check:**
```bash
php artisan queue:failed  # See failed jobs
php artisan queue:flush   # Clear queue (be careful!)
```

**Restart:**
```bash
php artisan queue:work --queue=sync  # In separate terminal
```

### Sync tokens not working

**Verify tokens match across servers:**
```bash
# On local server
echo $SYNC_API_TOKEN

# Should match SYNC_REMOTE_TOKEN on online server
```

### Network connectivity issues

**Test endpoint availability:**
```bash
# Local to Online
curl -I https://your-online-server.com/api/v1/sync/health

# Online to Local (if publicly accessible) or test from within network
curl -I http://hospital-local-server.local/api/v1/sync/health
```

---

## Architecture

```
LOCAL HOSPITAL SERVER                 ONLINE PLATFORM SERVER
┌─────────────────────────┐           ┌──────────────────────┐
│ User creates Patient    │           │ User creates Patient │
└────────────┬────────────┘           └──────────┬───────────┘
             │                                    │
             ▼                                    ▼
    Model Observer fires               Model Observer fires
    (SyncObserver)                      (SyncObserver)
             │                                    │
             ▼                                    ▼
    Create SyncOperation                Create SyncOperation
    Status: pending                     Status: pending
             │                                    │
             ▼                                    ▼
    Dispatch SyncRecordJob              Dispatch SyncRecordJob
             │                                    │
             ▼                                    ▼
    Queue Worker processes              Queue Worker processes
    HTTP POST to online API             HTTP POST to local API
             │                                    │
             └────────────────┬───────────────────┘
                              │
                              ▼
                    SyncController receives
                    - Validates token
                    - Locates record by sync_uuid
                    - Checks for conflicts
                    - Applies changes
                    - Returns success
                              │
                              ▼
                    Job updates SyncOperation
                    Mark as 'synced'
                    Store remote_id if applicable
```

---

## Next: Phase 2 (Optional)

Once Phase 1 is stable:

1. Add sync dashboard in admin panel
2. Create conflict resolution UI
3. Add pull-based sync endpoint
4. Implement selective field sync
5. Add data reconciliation tool

---

## Support

For issues or questions, check:
1. `SYNC_IMPLEMENTATION_GUIDE.md` - Full documentation
2. Queue logs: `tail -f storage/logs/laravel.log`
3. Sync status: `php artisan sync:status`
