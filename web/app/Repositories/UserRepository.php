<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}
