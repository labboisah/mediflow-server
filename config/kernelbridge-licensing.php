<?php

return [
    'api_url' => env('KERNELBRIDGE_API_URL', 'https://kernelbridge.com/api/v1'),
    'allowed_servers' => array_filter(array_map('trim', explode(',', (string) env('KERNELBRIDGE_ALLOWED_SERVERS', '')))),
    'product_code' => 'MEDIFLOW',
    'api_token' => env('KERNELBRIDGE_API_TOKEN'),
    'signature_key' => env('KERNELBRIDGE_CACHE_SIGNING_KEY', env('APP_KEY')),
    // Public raw Ed25519 keys, base64-encoded and indexed by KernelBridge key_id.
    'entitlement_public_keys' => json_decode((string) env('KERNELBRIDGE_ENTITLEMENT_PUBLIC_KEYS', '{}'), true) ?: [],
    'legacy_activation_enabled' => false,
    'verification_interval_minutes' => 15,
    'offline_grace_hours' => 72,
    'protection' => ['web' => false, 'except' => ['login', 'logout', 'forgot-password', 'reset-password/*', 'up']],
    'routes' => [
        'enabled' => false, 'prefix' => 'license', 'name' => 'kernelbridge.license.',
        'middleware' => ['web', 'auth', 'installation.admin'],
    ],
    'redirects' => ['after_activation' => '/installation/setup'],
];
