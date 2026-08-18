<?php

return [
    'name' => env('APP_NAME', 'PreTem'),
    'debug' => filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'ml_api_url' => env('ML_API_URL', 'http://127.0.0.1:8001'),
    'ml_api_key' => env('ML_API_KEY', 'change-me-local'),
    'ml_api_timeout' => (int) env('ML_API_TIMEOUT', 300),
    'ml_api_train_timeout' => (int) env('ML_API_TRAIN_TIMEOUT', 3600),
];
