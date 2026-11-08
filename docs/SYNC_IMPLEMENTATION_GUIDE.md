# Bidirectional Sync Implementation Guide

This guide walks through implementing bidirectional synchronization between a local hospital server and an online platform.

## Overview

The sync system provides:
- **Automatic change detection** via model observers
- **Queue-based outbound sync** to prevent blocking operations
- **Inbound sync API** for receiving changes from remote server
- **Conflict resolution** with configurable strategies
- **Comprehensive logging and error tracking**

---

## 1. Environment Configuration

### On Local Hospital Server (`.env`)

```env
SYNC_ENVIRONMENT=local
SYNC_REMOTE_ENDPOINT=https://online-server.example.com
SYNC_REMOTE_TOKEN=your-sync-api-token-generated-on-online-server
SYNC_API_TOKEN=local-server-sync-token-shared-with-online-server
SYNC_QUEUE_CONNECTION=database
SYNC_AUTO_SYNC_ENABLED=true
SYNC_CONFLICT_RESOLUTION=last_write_wins.
```

### On Online Server (`.env`)

```env
SYNC_ENVIRONMENT=online
SYNC_REMOTE_ENDPOINT=http://hospital-local-server.local
SYNC_REMOTE_TOKEN=hospital-server-sync-token-generated-locally
SYNC_API_TOKEN=online-server-sync-token-shared-with-local-server
SYNC_QUEUE_CONNECTION=database
SYNC_AUTO_SYNC_ENABLED=true
SYNC_CONFLICT_RESOLUTION=last_write_wins
```

---

## 2. Run Migrations

Execute migrations on both servers:

```bash
php artisan migrate
```

This creates:
- `sync_operations` table (tracks outbound/inbound operations)
- `sync_conflicts` table (stores conflict records)
- Adds `sync_uuid`, `sync_status`, `sync_origin`, `sync_updated_at`, `remote_id` columns to syncable models

---

## 3. Add Syncable Trait to Models

For each model you want to sync, use the `Syncable` trait:

### Example: Patient model

```php
<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use Syncable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        // ... other fields
    ];

    // Optional: Override getSyncPayload() for custom sync data
    public function getSyncPayload(): array
    {
        $payload = parent::getSyncPayload();
        // Custom modifications if needed
        return $payload;
    }
}
```

### Models to update (Priority 1):
- `App\Models\Patient`
- `App\Models\Admission`
- `App\Models\AntenatalCare`
- `App\Models\Labour`
- `App\Models\Delivery`
- `App\Models\InvestigationRequest`
- `App\Models\Prescription`
- `App\Models\NewbornExamination`
- `App\Models\ChildFollowUp`

---

## 4. Start Queue Workers

The sync system requires queue workers to process sync jobs. On both servers:

```bash
# Start a sync queue worker
php artisan queue:work --queue=sync

# Or start with multiple workers for high volume
php artisan queue:work --queue=sync --tries=5
```

For production, use a process manager like Supervisor to keep workers running.

---

## 5. Verify Sync is Working

### Check pending syncs
```bash
php artisan tinker
>>> App\Models\SyncOperation::where('status', 'pending')->count()
```

### Test the health endpoint
```bash
curl -H "Authorization: Bearer YOUR_SYNC_API_TOKEN" \
  https://your-server.com/api/v1/sync/health
```

### View sync logs
```bash
tail -f storage/logs/laravel.log | grep -i sync
```

---

## 6. Sync Flow Diagram

### Outbound Sync (Local → Online)

```
User creates/updates record locally
         ↓
Model observer fires (created/updated/deleted)
         ↓
SyncObserver creates SyncOperation record (status: pending)
         ↓
SyncRecordJob dispatched to 'sync' queue
         ↓
Queue worker processes job:
  - Sends HTTP POST to remote endpoint
  - Includes sync_uuid, model_type, operation, payload
         ↓
Remote server receives at: POST /api/v1/sync/records
         ↓
Remote SyncController validates and applies changes
         ↓
Remote sends response with remote_id
         ↓
Local job receives response:
  - Updates SyncOperation status to 'synced'
  - Stores remote_id in local record
```

### Inbound Sync (Online → Local)

```
User creates/updates record on online server
         ↓
Model observer fires (outbound sync to local server)
         ↓
Local server receives POST /api/v1/sync/records
         ↓
AuthenticateSyncToken middleware validates token
         ↓
SyncController::receiveSyncRecords():
  - Validates payload
  - Finds model by sync_uuid
  - Checks for conflicts
  - Applies changes via applySyncData()
         ↓
Returns success response with local record ID
         ↓
Acknowledgment sent back to online server
```

---

## 7. Conflict Resolution

The system handles conflicts using configured strategies:

### Last Write Wins (default)
```php
// In config/sync.php
'conflict_resolution' => 'last_write_wins'
```

When a conflict is detected:
- Compares `updated_at` timestamps
- Applies remote change only if remote timestamp is newer
- Logs conflict for audit

### Origin Precedence
```php
// In config/sync.php
'conflict_resolution' => 'origin_precedence'
```

When a conflict is detected:
- Checks if record origin matches current environment
- Applies change only if it came from the origin environment
- Good for read-only replicas

---

## 8. Monitoring and Troubleshooting

### Check sync operation status
```bash
php artisan tinker

// View pending syncs
>>> App\Models\SyncOperation::where('status', 'pending')->get();

// View failed syncs
>>> App\Models\SyncOperation::where('status', 'failed')->get();

// Manually retry a failed sync
>>> $sync = App\Models\SyncOperation::find(1);
>>> App\Jobs\SyncRecordJob::dispatch($sync);
```

### View conflicts
```bash
php artisan tinker

>>> App\Models\SyncConflict::where('resolution', 'pending')->get();
```

### Common issues

**Issue: Queue workers not running**
- Check if supervisor is running: `supervisorctl status`
- Restart workers: `supervisorctl restart laravel-worker:*`

**Issue: Sync jobs not processing**
- Verify `QUEUE_CONNECTION` in .env matches your setup
- Check if queue table exists: `php artisan queue:failed`
- Check storage/logs/laravel.log for errors

**Issue: Token authentication failing**
- Verify `SYNC_API_TOKEN` is set correctly on both servers
- Ensure `Authorization: Bearer {TOKEN}` header is sent
- Check firewall/network connectivity between servers

---

## 9. Advanced Configuration

### Custom sync payload

Override `getSyncPayload()` in your model:

```php
public function getSyncPayload(): array
{
    return [
        'id' => $this->id,
        'sync_uuid' => $this->sync_uuid,
        'first_name' => $this->first_name,
        'email' => $this->email,
        // Exclude sensitive fields
        // Don't include: password, secret_token, etc.
    ];
}
```

### Selective model syncing

Disable auto-sync for specific operations:

```php
// Temporarily disable sync
config(['sync.behavior.auto_sync_enabled' => false]);

// Make changes
$patient->update(['name' => 'New Name']);

// Re-enable sync
config(['sync.behavior.auto_sync_enabled' => true]);
```

### Batch sync endpoint

Send multiple records at once (more efficient):

```bash
curl -X POST https://your-server.com/api/v1/sync/batch \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "records": [
      {
        "sync_uuid": "uuid-1",
        "model_type": "App\\Models\\Patient",
        "operation": "create",
        "payload": {...}
      },
      {
        "sync_uuid": "uuid-2",
        "model_type": "App\\Models\\Patient",
        "operation": "update",
        "payload": {...}
      }
    ]
  }'
```

---

## 10. Deployment Checklist

- [ ] Add Syncable trait to all syncable models
- [ ] Run migrations on both servers
- [ ] Configure .env variables on both servers (matching tokens)
- [ ] Start queue workers on both servers
- [ ] Test health endpoint from both sides
- [ ] Create test record and verify sync
- [ ] Monitor logs during initial testing
- [ ] Set up alerts for failed sync operations
- [ ] Document sync tokens and endpoints (securely)
- [ ] Train staff on conflict handling procedures

---

## 11. Security Best Practices

1. **Use strong random tokens**
   ```bash
   php artisan tinker
   >>> bin2hex(random_bytes(32))
   ```

2. **HTTPS only** - Always use HTTPS for sync endpoints

3. **IP allowlisting** (optional, in nginx/Apache)
   ```nginx
   location /api/v1/sync/ {
       allow 192.168.1.100;  # hospital local server
       deny all;
   }
   ```

4. **Audit logging** - All sync operations are logged with:
   - sync_uuid
   - model_type
   - operation
   - timestamp
   - origin

5. **Token rotation** - Regularly update sync tokens in .env

---

## 12. Next Steps

1. Start with one model (e.g., Patient) to test the flow
2. Monitor queue workers and logs closely
3. Gradually add more models once stable
4. Implement dashboard for sync monitoring (optional)
5. Set up alerting for failed syncs
