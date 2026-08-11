<?php

return [
    'queue_connection' => env('DEMO_ANALYSIS_QUEUE_CONNECTION', 'redis'),
    'queue' => env('DEMO_ANALYSIS_QUEUE', 'demo-analysis'),
    'process_timeout' => (int) env('DEMO_ANALYSIS_PROCESS_TIMEOUT', 900),
    'uv_binary' => env('DEMO_ANALYSIS_UV_BINARY', 'uv'),
];
