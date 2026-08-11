<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CopperPriceRepository
{
    public function latest(int $limit = 100): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM copper_prices ORDER BY date DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function allLatest(): array
    {
        return Database::connection()
            ->query('SELECT * FROM copper_prices ORDER BY date DESC')
            ->fetchAll();
    }

    public function paginatedLatest(int $limit, int $offset): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM copper_prices ORDER BY date DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function orderedClosePrices(): array
    {
        return Database::connection()
            ->query('SELECT date, close FROM copper_prices ORDER BY date ASC')
            ->fetchAll();
    }

    public function upsert(array $row): string
    {
        $sql = 'INSERT INTO copper_prices (`date`, open, high, low, close, volume, change_percent, created_at, updated_at)
                VALUES (:date, :open, :high, :low, :close, :volume, :change_percent, NOW(), NOW())
                ON DUPLICATE KEY UPDATE open = VALUES(open), high = VALUES(high), low = VALUES(low),
                    close = VALUES(close), volume = VALUES(volume), change_percent = VALUES(change_percent), updated_at = NOW()';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($row);
        return $stmt->rowCount() === 1 ? 'inserted' : 'updated';
    }

    public function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM copper_prices')->fetchColumn();
    }

    public function summary(): array
    {
        $row = Database::connection()
            ->query('SELECT COUNT(*) AS total_rows, MIN(date) AS start_date, MAX(date) AS end_date, MIN(close) AS min_close, MAX(close) AS max_close, AVG(close) AS avg_close FROM copper_prices')
            ->fetch();

        return $row ?: [
            'total_rows' => 0,
            'start_date' => null,
            'end_date' => null,
            'min_close' => null,
            'max_close' => null,
            'avg_close' => null,
        ];
    }
}
