<?php

namespace App\Models\Traits;

trait Auditable
{
    /**
     * Attributes to exclude from audit logs.
     */
    public function getAuditExclude(): array
    {
        return property_exists($this, 'auditExclude') ? $this->auditExclude : [
            'password',
            'remember_token',
            'api_token',
            'two_factor_secret',
        ];
    }

    /**
     * Prepare model attributes for audit (remove sensitive fields).
     */
    public function serializeForAudit(array $attributes): array
    {
        $exclude = $this->getAuditExclude();
        foreach ($exclude as $key) {
            if (array_key_exists($key, $attributes)) {
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }
}
