<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class PredictionRepository
{
    public function latest(): array
    {
        $rows = Database::connection()->query('SELECT p.*, m.version, m.window_size FROM predictions p JOIN model_runs m ON m.id = p.model_run_id ORDER BY p.created_at DESC LIMIT 50')->fetchAll();
        return $this->attachChildren($rows);
    }

    public function reset(): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        $pdo->exec('DELETE po FROM prediction_outputs po JOIN predictions p ON p.id = po.prediction_id');
        $pdo->exec('DELETE pi FROM prediction_inputs pi JOIN predictions p ON p.id = pi.prediction_id');
        $pdo->exec('DELETE FROM predictions');
        $pdo->commit();

        $pdo->exec('ALTER TABLE prediction_outputs AUTO_INCREMENT = 1');
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
        $horizon = (int) ($result['horizon'] ?? 1);
        $strategy = (string) ($result['strategy'] ?? 'recursive');
        $outputs = $result['predictions'] ?? [];
        if (!is_array($outputs) || count($outputs) !== $horizon || $horizon < 1 || $horizon > 7) {
            throw new \InvalidArgumentException('Jumlah output prediksi tidak sesuai horizon.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO predictions (model_run_id, prediction_date, input_start_date, input_end_date, predicted_close, model_version, horizon_steps, strategy, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$modelRunId, $result['prediction_date'] ?? null, $window[0]['date'], end($window)['date'], $result['predicted_close'], $result['model_version'] ?? null, $horizon, $strategy]);
            $predictionId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO prediction_inputs (prediction_id, sequence_order, price_date, close_price, created_at) VALUES (?, ?, ?, ?, NOW())');
            foreach ($window as $i => $row) {
                $stmt->execute([$predictionId, $i + 1, $row['date'], $row['close']]);
            }

            $stmt = $pdo->prepare('INSERT INTO prediction_outputs (prediction_id, horizon_step, predicted_close, actual_close, created_at) VALUES (?, ?, ?, NULL, NOW())');
            foreach ($outputs as $output) {
                $step = (int) ($output['step'] ?? 0);
                if ($step < 1 || $step > $horizon || !isset($output['predicted_close'])) {
                    throw new \InvalidArgumentException('Output prediksi tidak lengkap.');
                }
                $stmt->execute([$predictionId, $step, $output['predicted_close']]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $predictionId;
    }

    public function attachChildren(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map(fn (array $row): int => (int) $row['id'], $rows);
        $outputs = $this->outputsForPredictionIds($ids);
        $inputs = $this->inputsForPredictionIds($ids);

        foreach ($rows as &$row) {
            $id = (int) $row['id'];
            $row['outputs'] = $outputs[$id] ?? [[
                'horizon_step' => 1,
                'predicted_close' => $row['predicted_close'],
            ]];
            $row['inputs'] = $inputs[$id] ?? [];
            $row['horizon_steps'] = (int) ($row['horizon_steps'] ?? count($row['outputs']));
            $row['strategy'] = $row['strategy'] ?? 'recursive';
        }

        return $rows;
    }

    public function outputsForPredictionIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare("SELECT prediction_id, horizon_step, predicted_close FROM prediction_outputs WHERE prediction_id IN ({$placeholders}) ORDER BY prediction_id ASC, horizon_step ASC");
        $stmt->execute($ids);
        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['prediction_id']][] = $row;
        }
        return $grouped;
    }

    public function inputsForPredictionIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare("SELECT prediction_id, sequence_order, price_date, close_price FROM prediction_inputs WHERE prediction_id IN ({$placeholders}) ORDER BY prediction_id ASC, sequence_order ASC");
        $stmt->execute($ids);
        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['prediction_id']][] = $row;
        }
        return $grouped;
    }
}
