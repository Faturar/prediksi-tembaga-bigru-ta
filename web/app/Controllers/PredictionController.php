<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CopperPriceRepository;
use App\Repositories\ModelRunRepository;
use App\Repositories\PredictionRepository;
use App\Services\MlApiClient;
use App\Services\PredictionWindowService;

final class PredictionController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $models = new ModelRunRepository();
        $successfulModels = $models->successful();
        $activeModel = $models->active();
        $selectedModel = $activeModel ?: ($successfulModels[0] ?? null);
        $orderedPrices = (new CopperPriceRepository())->orderedClosePrices();
        $windowService = new PredictionWindowService();
        $modelContexts = [];
        foreach ($successfulModels as $model) {
            $modelContexts[(int) $model['id']] = $windowService->context($orderedPrices, $model);
        }

        $this->view('predictions/index', [
            'title' => 'Prediksi',
            'activeModel' => $activeModel,
            'models' => $successfulModels,
            'selectedModel' => $selectedModel,
            'targetContext' => $windowService->context($orderedPrices, $selectedModel),
            'modelContexts' => $modelContexts,
            'predictions' => (new PredictionRepository())->latest(),
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        verify_csrf();
        $models = new ModelRunRepository();
        $modelId = (int) ($_POST['model_run_id'] ?? 0);
        $model = $modelId > 0 ? $models->findSuccessful($modelId) : $models->active();
        if (!$model) {
            $_SESSION['flash_error'] = 'Pilih model training yang sudah sukses untuk menjalankan prediksi.';
            $this->redirect('/predictions');
        }
        $windowService = new PredictionWindowService();
        try {
            $horizon = $windowService->validateHorizon($_POST['horizon'] ?? 1);
        } catch (\InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('/predictions');
        }
        $orderedRows = (new CopperPriceRepository())->orderedClosePrices();
        try {
            $window = $windowService->build($orderedRows, (int) $model['window_size']);
        } catch (\LengthException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('/predictions');
        }

        try {
            $result = (new MlApiClient())->predict(['model_version' => $model['version'], 'window' => $window, 'horizon' => $horizon]);
            (new PredictionRepository())->create((int) $model['id'], $result, $window, (int) $model['window_size']);
            $_SESSION['flash_success'] = "Hasil prediksi {$horizon} periode perdagangan berhasil disimpan.";
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Prediksi gagal: ' . $this->predictionError($e->getMessage());
        }
        $this->redirect('/predictions');
    }

    public function reset(): void
    {
        $this->requireAuth();
        verify_csrf();
        (new PredictionRepository())->reset();
        $_SESSION['flash_success'] = 'Semua data prediksi berhasil direset.';
        $this->redirect('/predictions');
    }

    private function predictionError(string $message): string
    {
        $lower = strtolower($message);
        if (str_contains($lower, 'could not resolve host') || str_contains($lower, 'failed to connect') || str_contains($lower, 'timed out')) {
            return 'Layanan machine learning tidak dapat diakses.';
        }
        if (str_contains($lower, 'not found') || str_contains($lower, 'artifact') || str_contains($lower, 'keras bigru model')) {
            return 'Artifact model BiGRU tidak ditemukan.';
        }
        return $message;
    }
}
