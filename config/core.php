<?php

return [
    'base_url' => env('CORE_BASE_URL', 'http://localhost:8000'),
    'system_code' => env('CORE_SYSTEM_CODE', 'shelf'),
    'timeout' => (int) env('CORE_TIMEOUT', 10),
    'service_token' => env('CORE_SERVICE_TOKEN'),
];
