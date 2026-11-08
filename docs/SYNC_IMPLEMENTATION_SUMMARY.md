# Bidirectional Sync Implementation - Summary

## Overview

Your Laravel hospital management system now has a **complete bidirectional synchronization system** ready to sync data between the local hospital server and the online platform.

The implementation is infrastructure-complete. All you need to do now is:
1. Add the `Syncable` trait to your models
2. Configure environment variables
3. Run migrations and queue workers

---

## What Was Implemented

### Core Infrastructure Files

| File | Purpose |
|------|---------|
| `routes/api.php` | RESTful API endpoints for sync operations |
| `config/sync.php` | Centralized sync configuration |
| `app/Models/SyncOperation.php` | Track all sync operations and their status |
| `app/Models/SyncConflict.php` | Track data conflicts between servers |
| `app/Models/Traits/Syncable.php` | Trait to add sync capability to models |

### Controller & Middleware

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Api/SyncController.php` | Handles inbound sync requests from remote server |
| `app/Http/Middleware/AuthenticateSyncToken.php` | Validates sync API tokens |

### Jobs & Observers

| File | Purpose |
|------|---------|
| `app/Jobs/SyncRecordJob.php` | Background job for sending sync data to remote |
| `app/Observers/SyncObserver.php` | Listens for model changes and queues sync jobs |

### Database Migrations

| File | Purpose |
|------|---------|
| `*_create_sync_operations_table.php` | Tracks sync operation history |
| `*_create_sync_conflicts_table.php` | Records and resolves conflicts |
| `*_add_sync_columns_to_models.php` | Adds sync metadata to your medical records |

### Artisan Commands

| Command | Purpose |
|---------|---------|
| `php artisan sync:status` | Dashboard showing sync operation counts and health |
| `php artisan sync:retry [uuid\|--all]` | Retry failed sync operations |
| `php artisan sync:initialize` | Initialize sync_uuid on existing records |

### Documentation

| File | Purpose |
|------|---------|
| `SYNC_QUICK_START.md` | Step-by-step activation guide (START HERE) |
| `SYNC_IMPLEMENTATION_GUIDE.md` | Comprehensive documentation and best practices |
| `SYNC_MODEL_EXAMPLES.md` | Code examples for adding trait to models |

---

## How It Works

### Sync Flow

```
USER CREATES RECORD
    ↓
Model Observer detects change
    ↓
SyncOperation record created (status: pending)
    ↓
SyncRecordJob dispatched to queue
    ↓
Queue worker sends HTTP POST to remote server
    ↓
Remote SyncController receives and applies changes
    ↓
Local job receives response and marks as 'synced'
    ↓
RECORD SYNCED ON REMOTE SERVER
```

### Bidirectional

The same process happens in reverse when remote server makes changes.

---

## Key Features

✅ **Automatic Change Detection** - Model observers automatically queue sync jobs
✅ **Queue-Based** - Never blocks user operations
✅ **Bidirectional** - Local→Online and Online→Local
✅ **Fault Tolerant** - Automatic retries with exponential backoff
✅ **Conflict Aware** - Detects and handles conflicts
✅ **Audit Trail** - Every sync operation is logged
✅ **Monitoring** - Dashboard and status commands
✅ **Scalable** - Designed for high-volume medical data

---

## Next Steps: Get It Running

Follow **SYNC_QUICK_START.md** in this order:

### 1. Add Syncable Trait to Models
Add `use Syncable;` to your key models:
- Patient
- Admission
- AntenatalCare
- Labour
- Delivery
- (See SYNC_MODEL_EXAMPLES.md for code)

### 2. Configure .env
Set sync variables on both servers:
- `SYNC_ENVIRONMENT` (local or online)
- `SYNC_REMOTE_ENDPOINT` (URL of other server)
- `SYNC_REMOTE_TOKEN` (API token for other server)
- `SYNC_API_TOKEN` (this server's API token)

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Initialize Existing Data
```bash
php artisan sync:initialize
```

### 5. Start Queue Workers
```bash
php artisan queue:work --queue=sync
```

### 6. Test It
Create a test record and watch it sync to the other server!

---

## Configuration Reference

### Environment Variables

```env
# Which server is this? local or online
SYNC_ENVIRONMENT=local

# Where to send syncs (the OTHER server)
SYNC_REMOTE_ENDPOINT=https://other-server.com
SYNC_REMOTE_TOKEN=token-to-authenticate-with-other-server

# What token do WE require from the OTHER server
SYNC_API_TOKEN=our-server-sync-token

# Queue settings
SYNC_QUEUE_CONNECTION=database  # or redis
SYNC_QUEUE_NAME=sync
SYNC_MAX_ATTEMPTS=5

# Conflict resolution
SYNC_CONFLICT_RESOLUTION=last_write_wins  # or origin_precedence
SYNC_ALLOW_REMOTE_DELETES=false

# Enable/disable
SYNC_AUTO_SYNC_ENABLED=true
```

---

## Monitoring

### View Sync Status
```bash
php artisan sync:status
```

### Check Logs
```bash
tail -f storage/logs/laravel.log | grep -i sync
```

### List Pending Syncs
```bash
php artisan tinker
>>> App\Models\SyncOperation::where('status', 'pending')->get();
```

### List Failed Syncs
```bash
php artisan tinker
>>> App\Models\SyncOperation::where('status', 'failed')->get();
```

### Retry Failed Syncs
```bash
php artisan sync:retry --all
```

---

## Architecture Diagram

```
┌─────────────────────────────┐         ┌──────────────────────────┐
│   LOCAL HOSPITAL SERVER     │         │   ONLINE PLATFORM        │
├─────────────────────────────┤         ├──────────────────────────┤
│                             │         │                          │
│  User creates Patient       │         │  User creates Patient    │
│           ↓                 │         │           ↓              │
│  Model Observer fires       │         │  Model Observer fires    │
│           ↓                 │         │           ↓              │
│  Create SyncOperation       │         │  Create SyncOperation    │
│  Status: pending            │         │  Status: pending         │
│           ↓                 │         │           ↓              │
│  SyncRecordJob enqueued     │         │  SyncRecordJob enqueued  │
│           ↓                 │         │           ↓              │
│  Queue Worker processes     │         │  Queue Worker processes  │
│  HTTP POST to Online API    ├────────→│  Receives at /api/v1/sync│
│           ↓                 │         │           ↓              │
│           │                 │         │  SyncController validates
│           │                 │         │  Applies changes         │
│           │                 │         │           ↓              │
│  Response received          │←────────┤  Sends response          │
│  Mark as 'synced'           │         │                          │
│                             │         │                          │
└─────────────────────────────┘         └──────────────────────────┘
```

---

## Security

- ✅ HTTPS required for remote endpoints
- ✅ API token authentication on all sync endpoints
- ✅ Payload validation on receipt
- ✅ Sensitive fields excluded from sync
- ✅ All operations logged for audit

---

## Support Files

1. **SYNC_QUICK_START.md** - Read this first! Step-by-step setup
2. **SYNC_IMPLEMENTATION_GUIDE.md** - Full technical documentation
3. **SYNC_MODEL_EXAMPLES.md** - Copy-paste examples for your models
4. **config/sync.php** - All configuration options with comments

---

## Common Commands

```bash
# View sync status dashboard
php artisan sync:status

# Retry all failed syncs
php artisan sync:retry --all

# Initialize UUIDs on existing records
php artisan sync:initialize

# Start queue worker
php artisan queue:work --queue=sync

# View sync operations in database
php artisan tinker
>>> App\Models\SyncOperation::latest()->limit(20)->get();
```

---

## What Happens When Data Syncs

1. **Patient creates appointment locally**
2. Observer detects the change
3. SyncOperation record created
4. SyncRecordJob enqueued
5. Queue worker picks up job
6. HTTP POST sent to online server:
   ```json
   {
     "sync_uuid": "uuid-here",
     "model_type": "App\\Models\\Patient",
     "operation": "create",
     "payload": { /* patient data */ },
     "origin": "local",
     "timestamp": "2026-04-29T10:30:00Z"
   }
   ```
7. Online server validates request
8. Checks if patient already exists by sync_uuid
9. If new, creates record; if exists, updates
10. Applies changes atomically
11. Returns success response
12. Local job marks operation as 'synced'

---

## Next: Before You Go Live

- [ ] Read SYNC_QUICK_START.md
- [ ] Add `Syncable` trait to all 5 priority models
- [ ] Test on local development setup
- [ ] Configure both servers' .env files
- [ ] Run migrations on both servers
- [ ] Start queue workers on both servers
- [ ] Test syncing a patient record
- [ ] Verify it appears on the other server
- [ ] Check monitoring dashboard: `php artisan sync:status`
- [ ] Plan your rollout to staff

---

## Questions?

Check these files in order:
1. SYNC_QUICK_START.md - Most common setup questions
2. SYNC_IMPLEMENTATION_GUIDE.md - Technical deep dive
3. SYNC_MODEL_EXAMPLES.md - How to add trait to models
4. config/sync.php - All available configuration options

Good luck! The system is ready to use.
