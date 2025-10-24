<?php

return [
    'base_url' => env('RXNORM_API_URL', 'https://rxnav.nlm.nih.gov/REST/'),
    'timeout' => env('RXNORM_API_TIMEOUT', 10),
    'cache_ttl' => env('RXNORM_CACHE_TTL', 86400),
    'retry' => [
        'attempts' => env('RXNORM_RETRY_ATTEMPTS', 3),
        'delay_ms' => env('RXNORM_RETRY_DELAY_MS', 200),
        'backoff_multiplier' => env('RXNORM_RETRY_BACKOFF', 2),
    ],
    'circuit_breaker' => [
        'failure_threshold' => env('RXNORM_CIRCUIT_FAILURE_THRESHOLD', 5),
        'cooldown_seconds' => env('RXNORM_CIRCUIT_COOLDOWN', 60),
    ],
];
