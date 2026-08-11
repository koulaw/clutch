<?php

return [
    'daily_imports' => (int) env('QUOTA_DAILY_IMPORTS', 5),
    'stored_analyses' => (int) env('QUOTA_STORED_ANALYSES', 30),
];
