<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class PredictionRepository
{
    public function latest(): array
    {
        return Database::connection()->query('SELECT p.*, m.version, m.window_size FROM predictions p JOIN model_runs m ON m.id = p.model_run_id ORDER BY p.created_at DESC LIMIT 50')->fetchAll();
    }

    public function reset(): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        $pdo->exec('DELETE pi FROM prediction_inputs pi JOIN predictions p ON p.id = pi.prediction_id');
        $pdo->exec('DELETE FROM predictions');
        $pdo->commit();

        $pdo->exec('ALTER TABLE prediction_inputs AUTO_INCREMENT = 1');
        $pdo->exec('ALTER TABLE predictions AUTO_INCREMENT = 1');
    }

    public function create(int $modelRunId, array $result, array $window, int $expectedWindowSize): int
    {
        if (count($window) !== $expectedWindowSize) {
            throw new \InvalidArgumentException('Jumlah input prediksi tidak sesuai window size model.');
        }
        if (!isset($result['predicted_close'])) {
            throw new \InvalidArgumentException('Response prediksi tidak memiliki nilai predicted_close.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO predictions (model_run_id, prediction_date, input_start_date, input_end_date, predicted_close, model_version, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$modelRunId, $result['prediction_date'] ?? null, $window[0]['date'], end($window)['date'], $result['predicted_close'], $result['model_version'] ?? null]);
        $predictionId = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('INSERT INTO prediction_inputs (prediction_id, sequence_order, price_date, close_price, created_at) VALUES (?, ?, ?, ?, NOW())');
        foreach ($window as $i => $row) {
            $stmt->execute([$predictionId, $i + 1, $row['date'], $row['close']]);
        }
        $pdo->commit();
        return $predictionId;
    }
}
