<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\ImportHistoryRepository;
use App\Services\CsvImportService;

final class ImportController extends Controller
{
    public function show(): void
    {
        $this->requireAuth();
        $this->view('imports/show', [
            'title' => 'Import CSV',
            'histories' => (new ImportHistoryRepository())->latest(),
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        verify_csrf();
        if (empty($_FILES['csv']['tmp_name'])) {
            $this->view('imports/show', ['title' => 'Import CSV', 'error' => 'File CSV wajib dipilih.', 'histories' => (new ImportHistoryRepository())->latest()]);
            return;
        }

        try {
            $stats = (new CsvImportService())->import($_FILES['csv']['tmp_name']);
            $stmt = Database::connection()->prepare('INSERT INTO import_histories (user_id, original_filename, total_rows, valid_rows, imported_rows, updated_rows, skipped_rows, duplicate_rows, invalid_rows, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, "success", NOW(), NOW())');
            $stmt->execute([$_SESSION['user']['id'], $_FILES['csv']['name'], $stats['total_rows'], $stats['valid_rows'], $stats['imported_rows'], $stats['updated_rows'], $stats['invalid_rows']]);
            $this->view('imports/show', ['title' => 'Import CSV', 'stats' => $stats, 'histories' => (new ImportHistoryRepository())->latest()]);
        } catch (\Throwable $e) {
            $this->view('imports/show', ['title' => 'Import CSV', 'error' => $e->getMessage(), 'histories' => (new ImportHistoryRepository())->latest()]);
        }
    }
}
