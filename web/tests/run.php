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
];

$failed = 0;
foreach ($checks as $name => $check) {
    $ok = $check();
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    $failed += $ok ? 0 : 1;
}

exit($failed === 0 ? 0 : 1);
