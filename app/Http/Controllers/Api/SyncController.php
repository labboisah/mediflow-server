<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    /**
     * Receive sync records from remote server
     */
    public function receiveSyncRecords(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'sync_uuid' => 'required|string|uuid',
                'model_type' => 'required|string',
                'operation' => 'required|in:create,update,delete',
                'payload' => 'required|array',
                'origin' => 'required|string',
                'timestamp' => 'required|string',
            ]);

            Log::info('Received sync record', [
                'sync_uuid' => $data['sync_uuid'],
                'model_type' => $data['model_type'],
                'operation' => $data['operation'],
            ]);

            $result = $this->applySyncRecord($data);

            return response()->json([
                'success' => true,
                'sync_uuid' => $data['sync_uuid'],
                'remote_id' => $result['id'] ?? null,
                'message' => 'Sync record received and processed',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Sync validation failed', ['errors' => $e->errors()]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Sync record processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Receive batch sync records
     */
    public function receiveBatchSync(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'records' => 'required|array',
                'records.*.sync_uuid' => 'required|string|uuid',
                'records.*.model_type' => 'required|string',
                'records.*.operation' => 'required|in:create,update,delete',
                'records.*.payload' => 'required|array',
            ]);

            $results = [];

            DB::transaction(function () use ($data, &$results) {
                foreach ($data['records'] as $record) {
                    $result = $this->applySyncRecord($record);
                    $results[] = [
                        'sync_uuid' => $record['sync_uuid'],
                        'success' => $result['success'] ?? false,
                        'remote_id' => $result['id'] ?? null,
                    ];
                }
            });

            Log::info('Batch sync completed', ['count' => count($results)]);

            return response()->json([
                'success' => true,
                'count' => count($results),
                'results' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Batch sync failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pending syncs (for pull-based sync)
     */
    public function getPendingSyncs(Request $request): JsonResponse
    {
        $syncs = \App\Models\SyncOperation::where('status', 'pending')
            ->limit(100)
            ->get(['sync_uuid', 'model_type', 'operation', 'payload', 'created_at']);

        return response()->json([
            'success' => true,
            'count' => $syncs->count(),
            'records' => $syncs,
        ]);
    }

    /**
     * Get sync status
     */
    public function getSyncStatus(Request $request, $syncUuid): JsonResponse
    {
        $modelType = $request->query('model_type');

        if ($modelType) {
            if (!class_exists($modelType)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model class not found',
                ], 404);
            }

            $model = $modelType::where('sync_uuid', $syncUuid)->first();

            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Synced record not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'sync_uuid' => $syncUuid,
                'status' => $model->sync_status ?? 'synced',
                'remote_id' => $model->getKey(),
                'model_type' => $modelType,
            ]);
        }

        $sync = \App\Models\SyncOperation::where('sync_uuid', $syncUuid)->first();

        if (!$sync) {
            return response()->json([
                'success' => false,
                'message' => 'Sync record not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'sync_uuid' => $sync->sync_uuid,
            'status' => $sync->status,
            'operation' => $sync->operation,
            'attempts' => $sync->attempts,
            'synced_at' => $sync->synced_at,
            'error_message' => $sync->error_message,
        ]);
    }

    /**
     * Health check endpoint
     */
    public function healthCheck(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 'healthy',
            'environment' => config('sync.environment'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Apply sync record to local database
     */
    private function applySyncRecord(array $data): array
    {
        $modelType = $data['model_type'];
        $operation = $data['operation'];
        $payload = $data['payload'];
        $syncUuid = $data['sync_uuid'];

        if (!class_exists($modelType)) {
            throw new \Exception("Model class not found: {$modelType}");
        }

        if (!in_array('App\Models\Traits\Syncable', class_uses($modelType), true)) {
            throw new \Exception("Model {$modelType} does not use Syncable trait");
        }

        $this->resolveRelationshipSyncUuids($payload);

        $payload['sync_uuid'] = $syncUuid;
        $payload['sync_origin'] = $data['origin'] ?? config('sync.environment');
        $payload['sync_status'] = 'synced';

        return DB::transaction(function () use ($modelType, $operation, $payload, $syncUuid) {
            $model = $modelType::where('sync_uuid', $syncUuid)->first();

            if ($operation === 'delete') {
                if ($model && config('sync.behavior.allow_remote_deletes', false)) {
                    $model->delete();
                }

                return ['success' => true, 'id' => $model ? $model->getKey() : null, 'model_type' => $modelType];
            }

            $model = $model ?: new $modelType();

            $modelType::withoutEvents(function () use ($model, $payload) {
                $model->forceFill($payload);
                $model->save();
            });

            return ['success' => true, 'id' => $model->getKey(), 'model_type' => $modelType];
        });
    }

    private function resolveRelationshipSyncUuids(array &$payload): void
    {
        foreach ($payload as $key => $value) {
            if (!Str::endsWith($key, '_sync_uuid') || empty($value)) {
                continue;
            }

            $foreignKey = Str::replaceLast('_sync_uuid', '_id', $key);
            $relatedClass = $this->resolveModelClassFromSyncKey($key);

            if (!$relatedClass || !class_exists($relatedClass)) {
                unset($payload[$key]);
                continue;
            }

            $related = $relatedClass::where('sync_uuid', $value)->first();

            if (!$related) {
                throw new \Exception("Cannot resolve relationship for {$key}: {$value}");
            }

            $payload[$foreignKey] = $related->getKey();
            unset($payload[$key]);
        }
    }

    private function resolveModelClassFromSyncKey(string $syncKey): ?string
    {
        $map = [
            'patient_sync_uuid' => \App\Models\Patient::class,
            'patient_visit_sync_uuid' => \App\Models\PatientVisit::class,
            'bill_sync_uuid' => \App\Models\Bill::class,
            'service_sync_uuid' => \App\Models\Service::class,
            'user_sync_uuid' => \App\Models\User::class,
        ];

        if (isset($map[$syncKey])) {
            return $map[$syncKey];
        }

        $base = Str::studly(Str::replaceLast('_sync_uuid', '', $syncKey));
        $candidate = "App\\Models\\{$base}";

        return class_exists($candidate) ? $candidate : null;
    }
}
