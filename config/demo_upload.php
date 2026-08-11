<?php

return [
    'disk' => env('DEMO_UPLOAD_DISK', 's3'),
    'max_size_bytes' => (int) env('DEMO_UPLOAD_MAX_SIZE_BYTES', 500 * 1024 * 1024),
    'url_ttl_minutes' => (int) env('DEMO_UPLOAD_URL_TTL_MINUTES', 10),
    'rate_limit_per_minute' => (int) env('DEMO_UPLOAD_RATE_LIMIT_PER_MINUTE', 10),
];
