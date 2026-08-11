<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = base_path("resources/views/{$view}.php");
        require base_path('resources/views/layouts/app.php');
    }

    protected function redirect(string $path): never
    {
        header("Location: {$path}");
        exit;
    }

    protected function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            $this->redirect('/login');
        }
    }
}
