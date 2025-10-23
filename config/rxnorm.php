<?php

return [
    'base_url' => env('RXNORM_API_URL', 'https://rxnav.nlm.nih.gov/REST/'),
    'timeout' => env('RXNORM_API_TIMEOUT', 10),
    'cache_ttl' => env('RXNORM_CACHE_TTL', 86400),
];
