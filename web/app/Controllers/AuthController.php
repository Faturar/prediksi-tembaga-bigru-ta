<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Login']);
    }

    public function login(): void
    {
        verify_csrf();
        $user = (new UserRepository())->findByEmail($_POST['email'] ?? '');
        if ($user && password_verify($_POST['password'] ?? '', $user['password'])) {
            $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
            $this->redirect('/');
        }
        $this->view('auth/login', ['title' => 'Login', 'error' => 'Email atau password salah.']);
    }

    public function logout(): void
    {
        verify_csrf();
        session_destroy();
        $this->redirect('/login');
    }
}
