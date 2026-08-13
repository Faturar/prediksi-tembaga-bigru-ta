<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CopperPriceRepository;
use App\Repositories\ModelRunRepository;
use App\Repositories\PredictionRepository;
use App\Services\MlApiClient;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $status = 'unavailable';
        try {
            $status = (new MlApiClient())->health()['status'] ?? 'unknown';
        } catch (\Throwable) {
        }
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
        $priceSeries = $prices->historicalClose($rangeStartDate);

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'priceCount' => $prices->count(),
            'priceSummary' => $prices->summary(),
            'priceSeries' => $priceSeries,
            'priceRangeOptions' => $rangeOptions,
            'selectedPriceRange' => $selectedRange,
            'selectedPriceRangeLabel' => $rangeOptions[$selectedRange]['display'],
            'selectedPriceRangeStart' => $rangeStartDate,
            'selectedPriceRangeEnd' => $latestDate,
            'activeModel' => (new ModelRunRepository())->active(),
            'predictions' => (new PredictionRepository())->latest(),
            'mlStatus' => $status,
        ]);
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
