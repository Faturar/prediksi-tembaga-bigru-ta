<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CopperPriceRepository;
use App\Repositories\ModelRunRepository;
use App\Repositories\PredictionRepository;
use App\Services\MlApiClient;
use App\Services\PredictionWindowService;

final class PublicController extends Controller
{
    public function index(): void
    {
        $prices = new CopperPriceRepository();
        $activeModel = (new ModelRunRepository())->activeWithMetrics();
        $latestPrediction = (new PredictionRepository())->latest()[0] ?? null;

        $rangeOptions = [
            '1m' => ['label' => '1 Bulan', 'display' => '1 Bulan Terakhir', 'modify' => '-1 month'],
            '3m' => ['label' => '3 Bulan', 'display' => '3 Bulan Terakhir', 'modify' => '-3 months'],
            '6m' => ['label' => '6 Bulan', 'display' => '6 Bulan Terakhir', 'modify' => '-6 months'],
            '1y' => ['label' => '1 Tahun', 'display' => '1 Tahun Terakhir', 'modify' => '-1 year'],
            '5y' => ['label' => '5 Tahun', 'display' => '5 Tahun Terakhir', 'modify' => '-5 years'],
            'all' => ['label' => 'Semua Data', 'display' => 'Semua Data', 'modify' => null],
        ];

        $selectedRange = (string) ($_GET['range'] ?? '1y');
        if (!array_key_exists($selectedRange, $rangeOptions)) {
            $selectedRange = '1y';
        }

        $latestDate = $prices->latestDate();
        $rangeStartDate = $this->rangeStartDate($latestDate, $rangeOptions[$selectedRange]['modify']);
        $priceSeries = $prices->historicalClose($rangeStartDate);

        $this->view('public/index', [
            'title' => 'Sistem Prediksi Harga Tembaga',
            'priceCount' => $prices->count(),
            'priceSummary' => $prices->summary(),
            'priceSeries' => $priceSeries,
            'priceRangeOptions' => $rangeOptions,
            'selectedPriceRange' => $selectedRange,
            'selectedPriceRangeLabel' => $rangeOptions[$selectedRange]['display'],
            'selectedPriceRangeStart' => $rangeStartDate,
            'selectedPriceRangeEnd' => $latestDate,
            'activeModel' => $activeModel,
            'latestPrediction' => $latestPrediction,
        ]);
    }

    public function historical(): void
    {
        $prices = new CopperPriceRepository();

        $rangeOptions = [
            '1m' => ['label' => '1 Bulan', 'display' => '1 Bulan Terakhir', 'modify' => '-1 month'],
            '3m' => ['label' => '3 Bulan', 'display' => '3 Bulan Terakhir', 'modify' => '-3 months'],
            '6m' => ['label' => '6 Bulan', 'display' => '6 Bulan Terakhir', 'modify' => '-6 months'],
            '1y' => ['label' => '1 Tahun', 'display' => '1 Tahun Terakhir', 'modify' => '-1 year'],
            '5y' => ['label' => '5 Tahun', 'display' => '5 Tahun Terakhir', 'modify' => '-5 years'],
            'all' => ['label' => 'Semua Data', 'display' => 'Semua Data', 'modify' => null],
        ];

        $selectedRange = (string) ($_GET['range'] ?? '1y');
        if (!array_key_exists($selectedRange, $rangeOptions)) {
            $selectedRange = '1y';
        }

        $latestDate = $prices->latestDate();
        $rangeStartDate = $this->rangeStartDate($latestDate, $rangeOptions[$selectedRange]['modify']);
        $priceSeries = $prices->historicalRecords($rangeStartDate);

        $this->view('public/historical', [
            'title' => 'Data Historis',
            'priceSummary' => $prices->summary(),
            'priceSeries' => $priceSeries,
            'priceRangeOptions' => $rangeOptions,
            'selectedPriceRange' => $selectedRange,
            'selectedPriceRangeLabel' => $rangeOptions[$selectedRange]['display'],
            'selectedPriceRangeStart' => $rangeStartDate,
            'selectedPriceRangeEnd' => $latestDate,
        ]);
    }

    public function forecast(): void
    {
        $activeModel = (new ModelRunRepository())->activeWithMetrics();
        $forecastResult = $_SESSION['public_forecast_result'] ?? null;
        unset($_SESSION['public_forecast_result']);

        $this->view('public/forecast', [
            'title' => 'Prediksi Harga Tembaga',
            'activeModel' => $activeModel,
            'forecastResult' => $forecastResult,
        ]);
    }

    public function submitForecast(): void
    {
        verify_csrf();

        $lastForecastAt = $_SESSION['public_forecast_at'] ?? 0;
        if (time() - (int) $lastForecastAt < 8) {
            $_SESSION['flash_error'] = 'Silakan tunggu beberapa detik sebelum menjalankan prediksi lagi.';
            $this->redirect('/forecast');
        }

        $modelRepo = new ModelRunRepository();
        $activeModel = $modelRepo->activeWithMetrics();
        if (!$activeModel) {
            $_SESSION['flash_error'] = 'Model prediksi belum tersedia. Silakan mencoba kembali setelah model selesai disiapkan oleh Admin.';
            $this->redirect('/forecast');
        }

        $windowService = new PredictionWindowService();
        try {
            $horizon = $windowService->validateHorizon($_POST['horizon'] ?? null);
            $orderedRows = (new CopperPriceRepository())->orderedClosePrices();
            $window = $windowService->build($orderedRows, (int) $activeModel['window_size']);
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e instanceof \LengthException
                ? $e->getMessage()
                : 'Horizon prediksi tidak valid. Pilih antara 1 sampai 7 periode perdagangan.';
            $this->redirect('/forecast');
        }

        try {
            $result = (new MlApiClient())->predict([
                'model_version' => $activeModel['version'],
                'window' => $window,
                'horizon' => $horizon,
            ]);
            (new PredictionRepository())->create((int) $activeModel['id'], $result, $window, (int) $activeModel['window_size']);

            $_SESSION['public_forecast_result'] = [
                'model' => [
                    'version' => $activeModel['version'],
                    'window_size' => $activeModel['window_size'],
                ],
                'window' => $window,
                'result' => $result,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $_SESSION['public_forecast_at'] = time();
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Layanan prediksi sedang tidak tersedia. Silakan coba kembali.';
        }

        $this->redirect('/forecast');
    }

    private function rangeStartDate(?string $latestDate, ?string $modify): ?string
    {
        if ($latestDate === null || $modify === null) {
            return null;
        }

        $date = new \DateTimeImmutable($latestDate);
        return $date->modify($modify)->format('Y-m-d');
    }
}
