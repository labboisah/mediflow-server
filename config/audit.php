<?php

return [
    // Whether to queue audit writes. If false, writes are synchronous.
    'queue' => env('AUDIT_QUEUE', true),

    // Queue connection to use for audit jobs (null = default connection)
    'connection' => env('AUDIT_QUEUE_CONNECTION', null),

    // Attributes to globally exclude from audits
    'exclude' => [
        'password',
        'remember_token',
        'api_token',
        'two_factor_secret',
    ],
];
