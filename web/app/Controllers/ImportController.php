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
        $file = $_FILES['csv'] ?? null;
        $uploadError = $this->validateUpload($file);
        if ($uploadError !== null) {
            $this->view('imports/show', ['title' => 'Import CSV', 'error' => $uploadError, 'histories' => (new ImportHistoryRepository())->latest()]);
            return;
        }

        try {
            $stats = (new CsvImportService())->import($file['tmp_name']);
            $stmt = Database::connection()->prepare('INSERT INTO import_histories (user_id, original_filename, total_rows, valid_rows, imported_rows, updated_rows, skipped_rows, duplicate_rows, invalid_rows, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, "success", NOW(), NOW())');
            $stmt->execute([$_SESSION['user']['id'], $file['name'], $stats['total_rows'], $stats['valid_rows'], $stats['imported_rows'], $stats['updated_rows'], $stats['invalid_rows']]);
            $this->view('imports/show', ['title' => 'Import CSV', 'stats' => $stats, 'histories' => (new ImportHistoryRepository())->latest()]);
        } catch (\Throwable $e) {
            $this->view('imports/show', ['title' => 'Import CSV', 'error' => $e->getMessage(), 'histories' => (new ImportHistoryRepository())->latest()]);
        }
    }

    private function validateUpload(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'File CSV wajib dipilih dan berhasil diunggah.';
        }
        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            return 'Upload CSV tidak valid.';
        }
        if ((int) ($file['size'] ?? 0) <= 0) {
            return 'File CSV kosong.';
        }
        if ((int) $file['size'] > 5 * 1024 * 1024) {
            return 'Ukuran file CSV maksimal 5 MB.';
        }
        if (strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'csv') {
            return 'File harus berekstensi .csv.';
        }
        $handle = fopen($file['tmp_name'], 'rb');
        $sample = $handle ? fread($handle, 512) : false;
        if (is_resource($handle)) {
            fclose($handle);
        }
        if ($sample === false || !str_contains((string) $sample, ',')) {
            return 'File tidak terlihat seperti CSV yang valid.';
        }
        return null;
    }
}
