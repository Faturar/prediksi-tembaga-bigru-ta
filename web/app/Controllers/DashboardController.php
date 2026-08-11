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
        $priceSeries = array_slice($prices->orderedClosePrices(), -30);
        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'priceCount' => $prices->count(),
            'priceSummary' => $prices->summary(),
            'priceSeries' => $priceSeries,
            'activeModel' => (new ModelRunRepository())->active(),
            'predictions' => (new PredictionRepository())->latest(),
            'mlStatus' => $status,
        ]);
    }
}
