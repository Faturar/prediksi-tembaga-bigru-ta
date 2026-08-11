<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function error(\Throwable $error): void
    {
        $line = sprintf(
            "[%s] %s in %s:%d%s",
            date('Y-m-d H:i:s'),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
            PHP_EOL
        );
        $path = base_path('storage/logs/app.log');
        file_put_contents($path, $line, FILE_APPEND);
    }
}
