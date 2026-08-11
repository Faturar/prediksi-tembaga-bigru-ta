<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ImportHistoryRepository
{
    public function latest(int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ih.*, u.name AS user_name
             FROM import_histories ih
             JOIN users u ON u.id = ih.user_id
             ORDER BY ih.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
