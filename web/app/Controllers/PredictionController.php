<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CopperPriceRepository;
use App\Repositories\ModelRunRepository;
use App\Repositories\PredictionRepository;
use App\Services\MlApiClient;

final class PredictionController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $models = new ModelRunRepository();
        $this->view('predictions/index', [
            'title' => 'Prediksi',
            'activeModel' => $models->active(),
            'models' => $models->successful(),
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
        $all = (new CopperPriceRepository())->orderedClosePrices();
        $window = array_slice($all, -1 * (int) $model['window_size']);
        if (count($window) < (int) $model['window_size']) {
            $_SESSION['flash_error'] = 'Data harga belum cukup untuk prediksi model ' . $model['version'] . '.';
            $this->redirect('/predictions');
        }

        try {
            $result = (new MlApiClient())->predict(['model_version' => $model['version'], 'window' => $window]);
            (new PredictionRepository())->create((int) $model['id'], $result, $window);
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Prediksi gagal: ' . $e->getMessage();
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
}
