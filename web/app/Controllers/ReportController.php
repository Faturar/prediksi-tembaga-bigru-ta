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
        $pdo = Database::connection();
        $priceSummary = $pdo->query('SELECT COUNT(*) AS total_rows, MIN(date) AS start_date, MAX(date) AS end_date, MIN(close) AS min_close, MAX(close) AS max_close, AVG(close) AS avg_close FROM copper_prices')->fetch() ?: [];
        $totalRows = match ($activeType) {
            'training' => (int) $pdo->query('SELECT COUNT(*) FROM model_runs')->fetchColumn(),
            'evaluation' => (int) $pdo->query('SELECT COUNT(*) FROM model_metrics')->fetchColumn(),
            'prediction' => (int) $pdo->query('SELECT COUNT(*) FROM predictions')->fetchColumn(),
            default => (int) ($priceSummary['total_rows'] ?? 0),
        };
        $pagination = $this->pagination($totalRows);

        $this->view('reports/index', [
            'title' => 'Laporan',
            'reportTypes' => $types,
            'activeType' => $activeType,
            'activeTitle' => $types[$activeType],
            'reportPagination' => $pagination,
            'priceSummary' => $priceSummary,
            'prices' => $activeType === 'dataset' ? $this->fetchAll('SELECT * FROM copper_prices ORDER BY date DESC LIMIT ? OFFSET ?', $pagination) : [],
            'imports' => $activeType === 'dataset' ? $pdo->query('SELECT ih.*, u.name AS user_name FROM import_histories ih LEFT JOIN users u ON u.id = ih.user_id ORDER BY ih.created_at DESC LIMIT 50')->fetchAll() : [],
            'models' => $activeType === 'training' ? $this->fetchAll('SELECT * FROM model_runs ORDER BY created_at DESC LIMIT ? OFFSET ?', $pagination) : [],
            'metrics' => $activeType === 'evaluation' ? $this->fetchAll('SELECT m.version, m.window_size, m.units, m.dropout, m.batch_size, m.configured_epochs, m.actual_epochs, m.trained_at, mm.* FROM model_metrics mm JOIN model_runs m ON m.id = mm.model_run_id ORDER BY mm.created_at DESC LIMIT ? OFFSET ?', $pagination) : [],
            'predictions' => $activeType === 'prediction' ? $this->fetchAll('SELECT p.*, m.version, m.window_size FROM predictions p JOIN model_runs m ON m.id = p.model_run_id ORDER BY p.created_at DESC LIMIT ? OFFSET ?', $pagination) : [],
        ]);
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

    private function fetchAll(string $sql, array $pagination): array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue(1, $pagination['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue(2, $pagination['offset'], \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
