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
    'public routes exist' => function (): bool {
        $routes = file_get_contents(base_path('routes/web.php')) ?: '';
        return str_contains($routes, '/historical')
            && str_contains($routes, '/forecast')
            && str_contains($routes, '/dashboard')
            && str_contains($routes, '/login');
    },
    'public pages have login admin link' => function (): bool {
        $layout = file_get_contents(base_path('resources/views/layouts/app.php')) ?: '';
        return str_contains($layout, 'Login Admin')
            && str_contains($layout, '/historical')
            && str_contains($layout, '/forecast');
    },
    'public views do not expose ML_API_KEY or artifact paths' => function (): bool {
        $views = file_get_contents(base_path('resources/views/public/index.php')) . file_get_contents(base_path('resources/views/public/historical.php')) . file_get_contents(base_path('resources/views/public/forecast.php'));
        return !str_contains($views, 'ML_API_KEY')
            && !str_contains($views, 'model_path')
            && !str_contains($views, 'scaler_path')
            && !str_contains($views, 'metadata_path');
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
    'dashboard historical chart is range based, not latest 30 only' => function (): bool {
        $controller = file_get_contents(base_path('app/Controllers/DashboardController.php')) ?: '';
        $repository = file_get_contents(base_path('app/Repositories/CopperPriceRepository.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/dashboard/index.php')) ?: '';

        return !str_contains($controller, 'array_slice($prices->orderedClosePrices(), -30)')
            && str_contains($controller, "'all' => ['label' => 'Semua Data'")
            && str_contains($controller, 'latestDate()')
            && str_contains($repository, 'SELECT date, close FROM copper_prices WHERE date >= ? ORDER BY date ASC')
            && str_contains($view, 'Pergerakan Harga Penutupan Tembaga')
            && str_contains($view, 'pointRadius: 0');
    },
];

$failed = 0;
foreach ($checks as $name => $check) {
    $ok = $check();
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    $failed += $ok ? 0 : 1;
}

exit($failed === 0 ? 0 : 1);
