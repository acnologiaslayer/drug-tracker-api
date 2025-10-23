<?php

return [
    'public_search' => [
        'attempts' => (int) env('RATE_LIMIT_PUBLIC', 60),
        'per_minutes' => 1,
    ],
    'authenticated' => [
        'attempts' => (int) env('RATE_LIMIT_AUTHENTICATED', 120),
        'per_minutes' => 1,
    ],
];
