<?php

return [
    'paths' => ['api/*', 'docs/*', 'up', '/'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-Request-Id',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
        'X-Saldo-Centavos',
        'X-Idempotent-Replay',
        'Location',
    ],

    'max_age' => 86400,

    'supports_credentials' => false,
];
