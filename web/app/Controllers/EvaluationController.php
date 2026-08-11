<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\ModelRunRepository;
use App\Services\MlApiClient;

final class EvaluationController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $rows = Database::connection()->query(
            'SELECT m.version, m.window_size, m.units, m.dropout, m.batch_size, m.actual_epochs, m.configured_epochs, m.trained_at, m.is_active, mm.*
             FROM model_metrics mm
             JOIN model_runs m ON m.id = mm.model_run_id
             ORDER BY mm.created_at DESC'
        )->fetchAll();
        $selected = (new ModelRunRepository())->active();
        if (!$selected && !empty($rows)) {
            $selected = (new ModelRunRepository())->findSuccessful((int) $rows[0]['model_run_id']);
        }

        $testSeries = [];
        $metadataError = null;
        if ($selected) {
            try {
                $metadata = (new MlApiClient())->model($selected['version']);
                $testSeries = is_array($metadata['test_series'] ?? null) ? $metadata['test_series'] : [];
            } catch (\Throwable $e) {
                $metadataError = 'Metadata test-series belum dapat dibaca dari ML API: ' . $e->getMessage();
            }
        }

        $this->view('evaluation/index', [
            'title' => 'Evaluasi',
            'metrics' => $rows,
            'selectedModel' => $selected,
            'testSeries' => $testSeries,
            'metadataError' => $metadataError,
        ]);
    }
}
