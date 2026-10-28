<?php

return [
    'verification_ttl_days' => max(1, (int) env('CORRESPONDENCE_VERIFICATION_TTL_DAYS', 365)),
    'bsre' => [
        'enabled' => (bool) env('BSRE_ENABLED', false),
        'endpoint' => env('BSRE_ENDPOINT'),
        'token' => env('BSRE_TOKEN'),
        'timeout' => max(5, (int) env('BSRE_TIMEOUT', 30)),
    ],
];
