<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ModelRunRepository
{
    public function all(): array
    {
        return Database::connection()->query(
            'SELECT m.*, mm.train_samples, mm.test_samples, mm.mae, mm.rmse, mm.mape
             FROM model_runs m
             LEFT JOIN model_metrics mm ON mm.model_run_id = m.id
             ORDER BY m.created_at DESC'
        )->fetchAll();
    }

    public function reset(): void
    {
        $pdo = Database::connection();
        $models = $pdo->query('SELECT model_path, scaler_path, metadata_path FROM model_runs')->fetchAll();

        $pdo->beginTransaction();
        $pdo->exec('DELETE pi FROM prediction_inputs pi JOIN predictions p ON p.id = pi.prediction_id');
        $pdo->exec('DELETE FROM predictions');
        $pdo->exec('DELETE FROM model_metrics');
        $pdo->exec('DELETE FROM model_runs');
        $pdo->commit();

        $pdo->exec('ALTER TABLE prediction_inputs AUTO_INCREMENT = 1');
        $pdo->exec('ALTER TABLE predictions AUTO_INCREMENT = 1');
        $pdo->exec('ALTER TABLE model_metrics AUTO_INCREMENT = 1');
        $pdo->exec('ALTER TABLE model_runs AUTO_INCREMENT = 1');

        foreach ($models as $model) {
            foreach (['model_path', 'scaler_path', 'metadata_path'] as $key) {
                $this->deleteArtifact($model[$key] ?? null);
            }
        }
    }

    public function active(): ?array
    {
        $row = Database::connection()->query('SELECT * FROM model_runs WHERE is_active = 1 AND status = "success" ORDER BY trained_at DESC LIMIT 1')->fetch();
        return $row ?: null;
    }

    public function successful(): array
    {
        return Database::connection()->query('SELECT * FROM model_runs WHERE status = "success" ORDER BY is_active DESC, trained_at DESC, created_at DESC')->fetchAll();
    }

    public function findSuccessful(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM model_runs WHERE id = ? AND status = "success" LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createPending(array $data): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO model_runs (version, status, requested_by, window_size, units, dropout, batch_size, configured_epochs, learning_rate, created_at, updated_at) VALUES (?, "running", ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$data['version'], $data['requested_by'], $data['window_size'], $data['units'], $data['dropout'], $data['batch_size'], $data['epochs'], $data['learning_rate']]);
        return (int) Database::connection()->lastInsertId();
    }

    public function markSuccess(int $id, array $result): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        $pdo->exec('UPDATE model_runs SET is_active = 0');
        $stmt = $pdo->prepare('UPDATE model_runs SET status = "success", is_active = 1, total_records = ?, dataset_start_date = ?, dataset_end_date = ?, train_start_date = ?, train_end_date = ?, test_start_date = ?, test_end_date = ?, actual_epochs = ?, best_epoch = ?, model_path = ?, scaler_path = ?, metadata_path = ?, trained_at = NOW(), updated_at = NOW() WHERE id = ?');
        $stmt->execute([
            $result['total_records'] ?? 0,
            $result['dataset_start_date'] ?? null,
            $result['dataset_end_date'] ?? null,
            $result['train_start_date'] ?? null,
            $result['train_end_date'] ?? null,
            $result['test_start_date'] ?? null,
            $result['test_end_date'] ?? null,
            $result['actual_epochs'] ?? null,
            $result['best_epoch'] ?? null,
            $result['model_path'] ?? null,
            $result['scaler_path'] ?? null,
            $result['metadata_path'] ?? null,
            $id,
        ]);
        $metrics = $result['metrics'] ?? [];
        $stmt = $pdo->prepare('INSERT INTO model_metrics (model_run_id, train_samples, test_samples, final_training_loss, final_validation_loss, mae, rmse, mape, training_duration_seconds, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$id, $result['train_samples'] ?? 0, $result['test_samples'] ?? 0, $metrics['final_training_loss'] ?? null, $metrics['final_validation_loss'] ?? null, $metrics['mae'] ?? 0, $metrics['rmse'] ?? 0, $metrics['mape'] ?? 0, $result['training_duration_seconds'] ?? null]);
        $pdo->commit();
    }

    public function markFailed(int $id, string $message): void
    {
        $stmt = Database::connection()->prepare('UPDATE model_runs SET status = "failed", error_message = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$message, $id]);
    }

    public function activate(int $id): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        $pdo->exec('UPDATE model_runs SET is_active = 0');
        $stmt = $pdo->prepare('UPDATE model_runs SET is_active = 1, updated_at = NOW() WHERE id = ? AND status = "success"');
        $stmt->execute([$id]);
        $pdo->commit();
    }

    private function deleteArtifact(?string $path): void
    {
        if (!$path) {
            return;
        }

        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $candidate = str_starts_with($normalizedPath, 'artifacts' . DIRECTORY_SEPARATOR)
            ? dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . $normalizedPath
            : $normalizedPath;
        $fullPath = realpath($candidate);
        $artifactRoot = realpath(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'artifacts');
        if ($fullPath && $artifactRoot && str_starts_with($fullPath, $artifactRoot) && is_file($fullPath)) {
            unlink($fullPath);
        }
    }
}
