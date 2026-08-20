<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class ReportController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $types = [
            'dataset' => 'Laporan Dataset Harga',
            'training' => 'Laporan Training Model',
            'evaluation' => 'Laporan Evaluasi Model',
            'prediction' => 'Laporan Hasil Prediksi',
        ];
        $activeType = $_GET['type'] ?? 'dataset';
        if (!array_key_exists($activeType, $types)) {
            $activeType = 'dataset';
        }
        $printMode = ($_GET['print'] ?? '') === '1';
        $pdo = Database::connection();
        $filters = [
            'start_date' => $this->validDate($_GET['start_date'] ?? null),
            'end_date' => $this->validDate($_GET['end_date'] ?? null),
        ];
        $priceWhere = $this->dateWhere('date', $filters);
        $predictionWhere = $this->dateWhere('DATE(p.created_at)', $filters);
        $priceSummary = $this->fetchOne(
            'SELECT COUNT(*) AS total_rows, MIN(date) AS start_date, MAX(date) AS end_date, MIN(close) AS min_close, MAX(close) AS max_close, AVG(close) AS avg_close FROM copper_prices' . $priceWhere['sql'],
            $priceWhere['params']
        );
        $totalRows = match ($activeType) {
            'training' => (int) $pdo->query('SELECT COUNT(*) FROM model_runs')->fetchColumn(),
            'evaluation' => (int) $pdo->query('SELECT COUNT(*) FROM model_metrics')->fetchColumn(),
            'prediction' => (int) $this->fetchColumn('SELECT COUNT(*) FROM predictions p' . $predictionWhere['sql'], $predictionWhere['params']),
            default => (int) ($priceSummary['total_rows'] ?? 0),
        };
        $pagination = $this->pagination($totalRows);
        $trainingSummary = $this->trainingSummary($activeType);

        $this->view('reports/index', [
            'title' => 'Laporan',
            'reportTypes' => $types,
            'activeType' => $activeType,
            'activeTitle' => $types[$activeType],
            'filters' => $filters,
            'printMode' => $printMode,
            'reportPagination' => $pagination,
            'trainingSummary' => $trainingSummary,
            'priceSummary' => $priceSummary,
            'prices' => $activeType === 'dataset' ? $this->fetchAll('SELECT * FROM copper_prices' . $priceWhere['sql'] . ' ORDER BY date DESC' . ($printMode ? ' LIMIT 50' : ' LIMIT ? OFFSET ?'), $printMode ? null : $pagination, $priceWhere['params']) : [],
            'imports' => $activeType === 'dataset' ? $pdo->query('SELECT ih.*, u.name AS user_name FROM import_histories ih LEFT JOIN users u ON u.id = ih.user_id ORDER BY ih.created_at DESC LIMIT 50')->fetchAll() : [],
            'models' => $activeType === 'training' ? $this->fetchAll(
                'SELECT m.*, mm.train_samples, mm.test_samples, mm.final_training_loss, mm.final_validation_loss, mm.mae, mm.rmse, mm.mape, mm.training_duration_seconds
                 FROM model_runs m
                 LEFT JOIN model_metrics mm ON mm.model_run_id = m.id
                 ORDER BY m.created_at DESC' . ($printMode ? '' : ' LIMIT ? OFFSET ?'),
                $printMode ? null : $pagination
            ) : [],
            'metrics' => $activeType === 'evaluation' ? $this->fetchAll('SELECT m.version, m.window_size, m.units, m.dropout, m.batch_size, m.configured_epochs, m.actual_epochs, m.trained_at, mm.* FROM model_metrics mm JOIN model_runs m ON m.id = mm.model_run_id ORDER BY mm.created_at DESC' . ($printMode ? '' : ' LIMIT ? OFFSET ?'), $printMode ? null : $pagination) : [],
            'predictions' => $activeType === 'prediction' ? $this->fetchAll('SELECT p.*, m.version, m.window_size FROM predictions p JOIN model_runs m ON m.id = p.model_run_id' . $predictionWhere['sql'] . ' ORDER BY p.created_at DESC' . ($printMode ? '' : ' LIMIT ? OFFSET ?'), $printMode ? null : $pagination, $predictionWhere['params']) : [],
        ]);
    }

    private function trainingSummary(string $activeType): array
    {
        if ($activeType !== 'training') {
            return [];
        }

        $summary = $this->fetchOne(
            'SELECT COUNT(*) AS total_runs,
                    COALESCE(SUM(status = "success"), 0) AS success_runs,
                    COALESCE(SUM(status = "failed"), 0) AS failed_runs,
                    COALESCE(SUM(status = "running"), 0) AS running_runs,
                    COALESCE(SUM(is_active = 1 AND status = "success"), 0) AS active_runs,
                    MAX(trained_at) AS last_trained_at
             FROM model_runs',
            []
        );
        $summary['active_version'] = $this->fetchColumn('SELECT version FROM model_runs WHERE is_active = 1 AND status = "success" ORDER BY trained_at DESC LIMIT 1', []) ?: null;
        return $summary;
    }

    private function pagination(int $totalRows): array
    {
        $allowedPerPage = [20, 50, 100];
        $perPage = (int) ($_GET['per_page'] ?? 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $page = max(1, min((int) ($_GET['page'] ?? 1), $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
            'allowed_per_page' => $allowedPerPage,
            'start' => $totalRows === 0 ? 0 : $offset + 1,
            'end' => min($offset + $perPage, $totalRows),
            'offset' => $offset,
        ];
    }

    private function fetchAll(string $sql, ?array $pagination = null, array $params = []): array
    {
        $stmt = Database::connection()->prepare($sql);
        $index = 1;
        foreach ($params as $value) {
            $stmt->bindValue($index++, $value);
        }
        if ($pagination !== null) {
            $stmt->bindValue($index++, $pagination['per_page'], \PDO::PARAM_INT);
            $stmt->bindValue($index, $pagination['offset'], \PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function fetchOne(string $sql, array $params): array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: [];
    }

    private function fetchColumn(string $sql, array $params): mixed
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function dateWhere(string $column, array $filters): array
    {
        $conditions = [];
        $params = [];
        if ($filters['start_date']) {
            $conditions[] = "{$column} >= ?";
            $params[] = $filters['start_date'];
        }
        if ($filters['end_date']) {
            $conditions[] = "{$column} <= ?";
            $params[] = $filters['end_date'];
        }
        return [
            'sql' => $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '',
            'params' => $params,
        ];
    }

    private function validDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);
        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value ? (string) $value : null;
    }
}
