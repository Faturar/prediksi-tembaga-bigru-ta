<?php

require __DIR__ . '/../app/Core/bootstrap.php';
require __DIR__ . '/../app/Helpers/security.php';

$router = new App\Core\Router();
require __DIR__ . '/../routes/web.php';

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Throwable $error) {
    App\Core\Logger::error($error);
    http_response_code(500);
    $debug = (bool) config('app')['debug'];
    echo $debug ? e($error->getMessage()) : 'Terjadi kesalahan pada aplikasi.';
}
