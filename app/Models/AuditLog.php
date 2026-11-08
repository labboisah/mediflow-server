<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Jobs\AuditLogJob;
use App\Models\User;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_id', 'action', 'model_type', 'model_id', 'before', 'after', 'meta', 'ip', 'user_agent'
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'meta' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record($actor, string $action, $model = null, $before = null, $after = null, array $meta = [])
    {
        $actorId = null;
        if ($actor instanceof \App\Models\User) {
            $actorId = $actor->id;
        } elseif (is_numeric($actor)) {
            $actorId = (int) $actor;
        } elseif (auth()->check()) {
            $actorId = auth()->id();
        }

        $modelType = null;
        $modelId = null;
        if ($model instanceof Model) {
            $modelType = get_class($model);
            $modelId = $model->getKey();
        } elseif (is_string($model)) {
            $modelType = $model;
        }

        $data = [
            'actor_id' => $actorId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'before' => $before ? (is_array($before) ? $before : (array) $before) : null,
            'after' => $after ? (is_array($after) ? $after : (array) $after) : null,
            'meta' => $meta ?: null,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ];

        // If configured to queue audit writes, dispatch a job; otherwise write synchronously.
        if (config('audit.queue', true)) {
            $job = new AuditLogJob($data);
            if ($connection = config('audit.connection')) {
                $job->onConnection($connection);
            }
            dispatch($job);

            return null;
        }

        return self::create($data);
    }
}
