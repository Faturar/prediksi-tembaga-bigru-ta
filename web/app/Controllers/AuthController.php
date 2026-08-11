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
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
            $this->redirect('/');
        }
        $this->view('auth/login', ['title' => 'Login', 'error' => 'Email atau password salah.']);
    }

    public function logout(): void
    {
        verify_csrf();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        $this->redirect('/login');
    }
}
