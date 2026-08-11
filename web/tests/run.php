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
    'prediction window uses exact model window ascending' => function (): bool {
        $rows = [
            ['date' => '2026-08-09', 'close' => 104],
            ['date' => '2026-08-07', 'close' => 102],
            ['date' => '2026-08-08', 'close' => 103],
            ['date' => '2026-08-06', 'close' => 101],
        ];
        $window = (new App\Services\PredictionWindowService())->build($rows, 3);
        return count($window) === 3
            && array_column($window, 'date') === ['2026-08-07', '2026-08-08', '2026-08-09'];
    },
    'prediction window rejects insufficient data' => function (): bool {
        try {
            (new App\Services\PredictionWindowService())->build([['date' => '2026-08-11', 'close' => 100]], 2);
        } catch (LengthException $e) {
            return str_contains($e->getMessage(), 'minimal 2 observasi harga penutupan');
        }
        return false;
    },
    'prediction UI states one-step target and has no date picker' => function (): bool {
        $view = file_get_contents(base_path('resources/views/predictions/index.php')) ?: '';
        return str_contains($view, 'Periode Perdagangan Berikutnya')
            && str_contains($view, 'satu observasi perdagangan berikutnya')
            && !str_contains($view, 'type="date"');
    },
    'prediction create keeps traceability guard' => function (): bool {
        $source = file_get_contents(base_path('app/Repositories/PredictionRepository.php')) ?: '';
        return str_contains($source, 'count($window) !== $expectedWindowSize')
            && str_contains($source, 'prediction_inputs');
    },
];

$failed = 0;
foreach ($checks as $name => $check) {
    $ok = $check();
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    $failed += $ok ? 0 : 1;
}

exit($failed === 0 ? 0 : 1);
