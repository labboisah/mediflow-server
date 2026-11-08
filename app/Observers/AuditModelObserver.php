<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use App\Models\AuditLog;

class AuditModelObserver
{
    protected array $beforeSnapshots = [];

    protected function key(Model $model): string
    {
        return spl_object_hash($model);
    }

    protected function isAuditable(Model $model): bool
    {
        $uses = [];
        $class = get_class($model);
        do {
            $uses = array_merge($uses, class_uses($class) ?: []);
        } while ($class = get_parent_class($class));

        return in_array('App\\Models\\Traits\\Auditable', $uses, true);
    }

    protected function sanitize(Model $model, array $attributes): array
    {
        if (method_exists($model, 'serializeForAudit')) {
            return $model->serializeForAudit($attributes);
        }

        $exclude = ['password', 'remember_token', 'api_token', 'two_factor_secret'];
        foreach ($exclude as $key) {
            if (array_key_exists($key, $attributes)) {
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }

    public function creating(Model $model): void
    {
        // noop
    }

    public function created(Model $model): void
    {
        if (! $this->isAuditable($model)) {
            return;
        }

        $after = $this->sanitize($model, $model->getAttributes());
        AuditLog::record(auth()->user(), 'model.created', $model, null, $after);
    }

    public function updating(Model $model): void
    {
        if (! $this->isAuditable($model)) {
            return;
        }

        $this->beforeSnapshots[$this->key($model)] = $this->sanitize($model, $model->getOriginal());
    }

    public function updated(Model $model): void
    {
        if (! $this->isAuditable($model)) {
            return;
        }

        $key = $this->key($model);
        $before = $this->beforeSnapshots[$key] ?? null;
        $after = $this->sanitize($model, $model->getAttributes());

        AuditLog::record(auth()->user(), 'model.updated', $model, $before, $after);

        unset($this->beforeSnapshots[$key]);
    }

    public function deleted(Model $model): void
    {
        if (! $this->isAuditable($model)) {
            return;
        }

        $before = $this->sanitize($model, $model->getOriginal());
        AuditLog::record(auth()->user(), 'model.deleted', $model, $before, null);
    }

    public function restored(Model $model): void
    {
        if (! $this->isAuditable($model)) {
            return;
        }

        $before = $this->sanitize($model, $model->getOriginal());
        $after = $this->sanitize($model, $model->getAttributes());
        AuditLog::record(auth()->user(), 'model.restored', $model, $before, $after);
    }

    public function forceDeleted(Model $model): void
    {
        if (! $this->isAuditable($model)) {
            return;
        }

        $before = $this->sanitize($model, $model->getOriginal());
        AuditLog::record(auth()->user(), 'model.force_deleted', $model, $before, null);
    }
}
