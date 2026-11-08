<?php

namespace App\Jobs;

use App\Models\SyncOperation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncRecordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected SyncOperation $syncOperation;

    /**
     * Maximum number of attempts from Laravel's queue system
     */
    public $maxTries = 10;

    /**
     * Number of seconds to wait before retrying the job
     */
    public $backoff = [10, 30, 60, 120, 300];

    public function __construct(SyncOperation $syncOperation)
    {
        $this->syncOperation = $syncOperation;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        if (!$this->syncOperation->isPending()) {
            return;
        }

        $this->syncOperation->incrementAttempts();

        $endpoint = config('sync.remote.endpoint');
        $token = config('sync.remote.token');

        if (!$endpoint || !$token) {
            $this->syncOperation->markFailed('Sync not configured: missing remote endpoint or token');
            return;
        }

        // Check if remote server is available
        if (!$this->isRemoteAvailable()) {
            if ($this->syncOperation->shouldRetry()) {
                Log::info('Remote server unavailable, retrying later', [
                    'sync_uuid' => $this->syncOperation->sync_uuid,
                    'endpoint' => $endpoint,
                ]);
                $this->release(config('sync.queue.dependency_delay', 10));
                return;
            }

            $this->syncOperation->markFailed('Remote server unreachable after max attempts');
            Log::error('Remote server unavailable, sync failed', [
                'sync_uuid' => $this->syncOperation->sync_uuid,
                'endpoint' => $endpoint,
                'attempts' => $this->syncOperation->attempts,
            ]);
            return;
        }

        $payload = $this->syncOperation->payload ?? [];
        $operation = $this->resolveRemoteOperation();

        if ($operation === null) {
            return;
        }

        if ($operation !== 'delete' && !$this->dependenciesReady($payload)) {
            return;
        }

        $requestPayload = [
            'sync_uuid' => $this->syncOperation->sync_uuid,
            'model_type' => $this->syncOperation->model_type,
            'operation' => $operation,
            'payload' => $payload,
            'origin' => config('sync.environment'),
            'timestamp' => now()->toIso8601String(),
        ];

        Log::info('Sync start', [
            'sync_uuid' => $this->syncOperation->sync_uuid,
            'model_type' => $this->syncOperation->model_type,
            'operation' => $operation,
            'attempt' => $this->syncOperation->attempts,
        ]);

        try {
            $response = Http::withToken($token)
                ->timeout(config('sync.remote.timeout', 30))
                ->post("{$endpoint}/api/v1/sync/records", $requestPayload);

            if ($response->successful()) {
                $remoteId = $response->json('remote_id');
                $this->syncOperation->markSynced($remoteId);

                Log::info('Sync successful', [
                    'sync_uuid' => $this->syncOperation->sync_uuid,
                    'model_type' => $this->syncOperation->model_type,
                    'operation' => $operation,
                ]);

                return;
            }

            $errorMessage = $response->json('message') ?? $response->body();
            Log::warning('Sync failed with response error', [
                'sync_uuid' => $this->syncOperation->sync_uuid,
                'model_type' => $this->syncOperation->model_type,
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);

            $this->retryOrFail($errorMessage);
        } catch (Throwable $exception) {
            Log::error('Sync exception', [
                'sync_uuid' => $this->syncOperation->sync_uuid,
                'model_type' => $this->syncOperation->model_type,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->retryOrFail($exception->getMessage());
        }
    }

    private function resolveRemoteOperation(): ?string
    {
        if ($this->syncOperation->operation === 'delete') {
            return 'delete';
        }

        $remoteExists = $this->remoteRecordExists(
            $this->syncOperation->sync_uuid,
            $this->syncOperation->model_type
        );

        if ($remoteExists === null) {
            return null;
        }

        return $remoteExists ? 'update' : 'create';
    }

    private function dependenciesReady(array $payload): bool
    {
        $rules = $this->dependencyRules();
        $modelType = $this->syncOperation->model_type;
        $dependencies = $rules[$modelType] ?? [];

        foreach ($dependencies as $payloadKey => $dependencyClass) {
            if (empty($payload[$payloadKey])) {
                $this->retryLater("Dependency payload missing: {$payloadKey}");
                return false;
            }

            $exists = $this->remoteRecordExists($payload[$payloadKey], $dependencyClass);

            if ($exists === null) {
                return false;
            }

            if (! $exists) {
                $this->retryLater("Dependency not ready remotely: {$payloadKey}");
                return false;
            }
        }

        return true;
    }

    private function dependencyRules(): array
    {
        return [
            \App\Models\PatientVisit::class => [
                'patient_sync_uuid' => \App\Models\Patient::class,
            ],
            \App\Models\Bill::class => [
                'patient_visit_sync_uuid' => \App\Models\PatientVisit::class,
            ],
            \App\Models\Payment::class => [
                'bill_sync_uuid' => \App\Models\Bill::class,
            ],
        ];
    }

    private function remoteRecordExists(string $syncUuid, ?string $modelType = null): ?bool
    {
        $endpoint = config('sync.remote.endpoint');
        $token = config('sync.remote.token');

        if (!$endpoint || !$token) {
            Log::warning('Sync not configured', ['sync_uuid' => $syncUuid]);
            return null;
        }

        try {
            $request = Http::withToken($token)
                ->timeout(config('sync.remote.timeout', 30))
                ->acceptJson();

            $response = $request->get("{$endpoint}/api/v1/sync/status/{$syncUuid}", array_filter([
                'model_type' => $modelType,
            ]));

            if ($response->successful()) {
                return true;
            }

            if ($response->status() === 404) {
                return false;
            }

            Log::warning('Remote record existence check returned unexpected status', [
                'sync_uuid' => $syncUuid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $this->retryLater('Unable to determine remote record existence');
            return null;
        } catch (Throwable $exception) {
            Log::warning('Remote record existence check failed', [
                'sync_uuid' => $syncUuid,
                'error' => $exception->getMessage(),
            ]);

            $this->retryLater('Remote status check failed');
            return null;
        }
    }

    private function retryLater(string $reason): void
    {
        if ($this->syncOperation->shouldRetry()) {
            $delay = config('sync.queue.dependency_delay', 10);

            Log::info('Sync dependency deferred', [
                'sync_uuid' => $this->syncOperation->sync_uuid,
                'model_type' => $this->syncOperation->model_type,
                'reason' => $reason,
                'attempts' => $this->syncOperation->attempts,
            ]);

            $this->release($delay);
            return;
        }

        $this->syncOperation->markFailed($reason);
        Log::warning('Sync failed: max retries exceeded', [
            'sync_uuid' => $this->syncOperation->sync_uuid,
            'model_type' => $this->syncOperation->model_type,
            'reason' => $reason,
            'attempts' => $this->syncOperation->attempts,
        ]);
    }

    private function retryOrFail(string $message): void
    {
        if ($this->syncOperation->shouldRetry()) {
            $delay = $this->retryDelay();
            Log::info('Sync retry scheduled', [
                'sync_uuid' => $this->syncOperation->sync_uuid,
                'model_type' => $this->syncOperation->model_type,
                'reason' => $message,
                'delay_seconds' => $delay,
                'attempts' => $this->syncOperation->attempts,
            ]);
            $this->release($delay);
            return;
        }

        $this->syncOperation->markFailed($message);
        Log::warning('Sync failed: max retries exceeded', [
            'sync_uuid' => $this->syncOperation->sync_uuid,
            'model_type' => $this->syncOperation->model_type,
            'error' => $message,
            'attempts' => $this->syncOperation->attempts,
        ]);
    }

    private function retryDelay(): int
    {
        $attempts = $this->syncOperation->attempts;
        $baseDelay = config('sync.queue.retry_after', 300);
        
        // Use exponential backoff: 10, 30, 60, 120, 300...
        return min($baseDelay, $baseDelay * max(1, $attempts - 1));
    }

    /**
     * Check if remote endpoint is reachable
     */
    private function isRemoteAvailable(): bool
    {
        $endpoint = config('sync.remote.endpoint');
        $token = config('sync.remote.token');

        if (!$endpoint || !$token) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->get("{$endpoint}/api/v1/sync/health");

            return $response->successful();
        } catch (Throwable $exception) {
            Log::warning('Remote server health check failed', [
                'endpoint' => $endpoint,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error('SyncRecordJob failed permanently', [
            'sync_uuid' => $this->syncOperation->sync_uuid,
            'model_type' => $this->syncOperation->model_type,
            'error' => $exception->getMessage(),
        ]);

        $this->syncOperation->markFailed($exception->getMessage());
    }
}
