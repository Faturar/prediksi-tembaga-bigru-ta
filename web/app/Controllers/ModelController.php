<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CopperPriceRepository;
use App\Repositories\ModelRunRepository;
use App\Services\MlApiClient;

final class ModelController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->view('models/index', ['title' => 'Model', 'models' => (new ModelRunRepository())->all()]);
    }

    public function train(): void
    {
        $this->requireAuth();
        verify_csrf();
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
            $result = (new MlApiClient())->train([
                ...$params,
                'data' => $data,
            ]);
            $repo->markSuccess($runId, $result);
        } catch (\Throwable $e) {
            $repo->markFailed($runId, $e->getMessage());
            $_SESSION['flash_error'] = 'Training gagal: ' . $e->getMessage();
        }
        $this->redirect('/models');
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
}
