<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CopperPriceRepository;

final class CopperPriceController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $repo = new CopperPriceRepository();
        $allowedPerPage = [20, 50, 100];
        $perPage = (int) ($_GET['per_page'] ?? 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $totalRows = $repo->count();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $page = max(1, min((int) ($_GET['page'] ?? 1), $totalPages));
        $offset = ($page - 1) * $perPage;

        $this->view('prices/index', [
            'title' => 'Data Harga',
            'rows' => $repo->paginatedLatest($perPage, $offset),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_rows' => $totalRows,
                'total_pages' => $totalPages,
                'allowed_per_page' => $allowedPerPage,
                'start' => $totalRows === 0 ? 0 : $offset + 1,
                'end' => min($offset + $perPage, $totalRows),
            ],
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        verify_csrf();
        (new CopperPriceRepository())->upsert([
            'date' => $_POST['date'],
            'open' => $_POST['open'] ?: null,
            'high' => $_POST['high'] ?: null,
            'low' => $_POST['low'] ?: null,
            'close' => $_POST['close'],
            'volume' => $_POST['volume'] ?: null,
            'change_percent' => $_POST['change_percent'] ?: null,
        ]);
        $this->redirect('/prices');
    }
}
