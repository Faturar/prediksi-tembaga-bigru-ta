<?php

declare(strict_types=1);

require __DIR__ . '/../app/Core/bootstrap.php';
require __DIR__ . '/../app/Helpers/security.php';

$checks = [
    'bootstrap loads' => function (): bool {
        return function_exists('base_path') && class_exists(App\Core\Router::class);
    },
    'routes file exists' => function (): bool {
        return is_file(base_path('routes/web.php'));
    },
    'schema exists' => function (): bool {
        return is_file(base_path('database/schema.sql'));
    },
    'csv service loads' => function (): bool {
        return class_exists(App\Services\CsvImportService::class);
    },
    'csrf token helper works' => function (): bool {
        $token = csrf_token();
        return is_string($token) && strlen($token) === 64 && hash_equals($token, csrf_token());
    },
    'price validator rejects impossible OHLC' => function (): bool {
        [$row, $errors] = (new App\Services\CopperPriceValidator())->validate([
            'date' => '2026-08-11',
            'open' => '100',
            'high' => '90',
            'low' => '80',
            'close' => '95',
        ]);
        return $row['date'] === '2026-08-11' && $errors !== [];
    },
    'price CRUD routes exist' => function (): bool {
        $routes = file_get_contents(base_path('routes/web.php')) ?: '';
        return str_contains($routes, '/prices/edit')
            && str_contains($routes, '/prices/update')
            && str_contains($routes, '/prices/delete');
    },
    'ML API client exposes train and predict' => function (): bool {
        return method_exists(App\Services\MlApiClient::class, 'train')
            && method_exists(App\Services\MlApiClient::class, 'predict')
            && method_exists(App\Services\MlApiClient::class, 'model');
    },
];

$failed = 0;
foreach ($checks as $name => $check) {
    $ok = $check();
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    $failed += $ok ? 0 : 1;
}

exit($failed === 0 ? 0 : 1);
