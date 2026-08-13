<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CopperPriceRepository;
use App\Repositories\ModelRunRepository;

final class ModelController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $models = (new ModelRunRepository())->all();
        foreach ($models as &$model) {
            $model['has_log'] = is_file($this->trainingLogPath($model['version']));
        }
        unset($model);

        $this->view('models/index', ['title' => 'Model', 'models' => $models]);
    }

    public function detail(): void
    {
        $this->requireAuth();
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID model tidak valid.';
            $this->redirect('/models');
        }

        $model = (new ModelRunRepository())->findWithMetrics($id);
        if (!$model) {
            $_SESSION['flash_error'] = 'Data model tidak ditemukan.';
            $this->redirect('/models');
        }

        $testSeries = [];
        $metadataError = null;
        if ($model['status'] === 'success') {
            try {
                $metadata = (new MlApiClient())->model($model['version']);
                $testSeries = is_array($metadata['test_series'] ?? null) ? $metadata['test_series'] : [];
            } catch (\Throwable $e) {
                $metadataError = 'Metadata test-series belum dapat dibaca dari ML API: ' . $e->getMessage();
            }
        }

        $hasLog = is_file($this->trainingLogPath($model['version']));

        $this->view('models/detail', [
            'title' => 'Detail Model',
            'model' => $model,
            'testSeries' => $testSeries,
            'metadataError' => $metadataError,
            'hasLog' => $hasLog,
        ]);
    }

    public function train(): void
    {
        $this->requireAuth();
        verify_csrf();
        @set_time_limit(0);
        ignore_user_abort(true);

        $repo = new ModelRunRepository();
        $params = [
            'requested_by' => $_SESSION['user']['id'],
            'window_size' => (int) ($_POST['window_size'] ?? 30),
            'units' => (int) ($_POST['units'] ?? 64),
            'dropout' => (float) ($_POST['dropout'] ?? 0.2),
            'batch_size' => (int) ($_POST['batch_size'] ?? 32),
            'epochs' => (int) ($_POST['epochs'] ?? 50),
            'learning_rate' => (float) ($_POST['learning_rate'] ?? 0.001),
        ];
        $params['version'] = $this->modelVersion($params);
        $data = (new CopperPriceRepository())->orderedClosePrices();
        $minimumRows = $params['window_size'] + 10;
        if (count($data) < $minimumRows) {
            $_SESSION['flash_error'] = "Data harga belum cukup untuk training. Minimal {$minimumRows} baris untuk window {$params['window_size']}, saat ini baru " . count($data) . ' baris.';
            $this->redirect('/models');
        }

        $runId = $repo->createPending($params);
        try {
            $this->dispatchTrainingWorker($runId);
            $_SESSION['flash_success'] = 'Training dimulai di background. Log akan diperbarui otomatis selama proses berjalan.';
            $this->redirect('/models/log?id=' . $runId);
        } catch (\Throwable $e) {
            $repo->markFailed($runId, $e->getMessage());
            $_SESSION['flash_error'] = 'Training gagal dijalankan: ' . $e->getMessage();
        }
        $this->redirect('/models');
    }

    public function log(): void
    {
        $this->requireAuth();
        $id = (int) ($_GET['id'] ?? 0);
        $model = (new ModelRunRepository())->find($id);
        if (!$model) {
            $_SESSION['flash_error'] = 'Data model tidak ditemukan.';
            $this->redirect('/models');
        }

        $logPath = $this->trainingLogPath($model['version']);
        $logContent = is_file($logPath) ? (file_get_contents($logPath) ?: '') : '';

        $this->view('models/log', [
            'title' => 'Log Training',
            'model' => $model,
            'logContent' => $logContent,
            'logPath' => $logPath,
        ]);
    }

    public function activate(): void
    {
        $this->requireAuth();
        verify_csrf();
        (new ModelRunRepository())->activate((int) $_POST['id']);
        $this->redirect('/models');
    }

    public function reset(): void
    {
        $this->requireAuth();
        verify_csrf();
        (new ModelRunRepository())->reset();
        $_SESSION['flash_success'] = 'Semua data model, metrik, prediksi terkait, dan artifact berhasil direset.';
        $this->redirect('/models');
    }

    private function modelVersion(array $params): string
    {
        $dropout = (int) round((float) $params['dropout'] * 100);
        $version = sprintf(
            'b_w%du%dd%02db%de%d_%s',
            $params['window_size'],
            $params['units'],
            $dropout,
            $params['batch_size'],
            $params['epochs'],
            date('ymdHis')
        );
        if (strlen($version) <= 30) {
            return $version;
        }

        return sprintf(
            'b_w%du%dd%02de%d_%s',
            $params['window_size'],
            $params['units'],
            $dropout,
            $params['epochs'],
            date('ymdHis')
        );
    }

    private function dispatchTrainingWorker(int $runId): void
    {
        $script = base_path('scripts/run-training.php');
        $php = PHP_BINARY;

        if (PHP_OS_FAMILY === 'Windows') {
            $command = 'start "" /B ' . escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . $runId . ' >NUL 2>&1';
            $handle = popen($command, 'r');
            if ($handle === false) {
                throw new \RuntimeException('Tidak dapat menjalankan proses training background.');
            }
            pclose($handle);
            return;
        }

        $command = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . $runId . ' >/dev/null 2>&1 &';
        exec($command);
    }

    private function trainingLogPath(string $version): string
    {
        $safeVersion = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $version) ?: 'training';
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . "training-{$safeVersion}.log";
    }
}
