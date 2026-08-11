<?php

declare(strict_types=1);

session_start();

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $path = __DIR__ . '/../' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

function base_path(string $path = ''): string
{
    return dirname(__DIR__, 2) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
}

function env(string $key, mixed $default = null): mixed
{
    static $loaded = false;
    if (!$loaded) {
        $envFile = base_path('.env');
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$name, $value] = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value, " \t\n\r\0\x0B\"");
            }
        }
        $loaded = true;
    }

    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function config(string $file): array
{
    return require base_path("config/{$file}.php");
}
