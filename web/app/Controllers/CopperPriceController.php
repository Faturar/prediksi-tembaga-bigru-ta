<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CopperPriceRepository;
use App\Services\CopperPriceValidator;

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
        $repo = new CopperPriceRepository();
        [$row, $errors] = (new CopperPriceValidator())->validate($_POST);
        if ($row['date'] !== '' && $repo->findByDate($row['date'])) {
            $errors[] = 'Tanggal sudah ada. Silakan edit data yang sudah tersimpan.';
        }
        if ($errors) {
            $_SESSION['flash_error'] = implode(' ', $errors);
            $this->redirect('/prices');
        }
        $repo->create($row);
        $_SESSION['flash_success'] = 'Data harga berhasil ditambahkan.';
        $this->redirect('/prices');
    }

    public function edit(): void
    {
        $this->requireAuth();
        $row = (new CopperPriceRepository())->find((int) ($_GET['id'] ?? 0));
        if (!$row) {
            $_SESSION['flash_error'] = 'Data harga tidak ditemukan.';
            $this->redirect('/prices');
        }
        $this->view('prices/edit', ['title' => 'Edit Data Harga', 'row' => $row]);
    }

    public function update(): void
    {
        $this->requireAuth();
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $repo = new CopperPriceRepository();
        $existing = $repo->find($id);
        if (!$existing) {
            $_SESSION['flash_error'] = 'Data harga tidak ditemukan.';
            $this->redirect('/prices');
        }

        [$row, $errors] = (new CopperPriceValidator())->validate($_POST);
        $duplicate = $row['date'] !== '' ? $repo->findByDate($row['date']) : null;
        if ($duplicate && (int) $duplicate['id'] !== $id) {
            $errors[] = 'Tanggal sudah digunakan data lain.';
        }
        if ($errors) {
            $_SESSION['flash_error'] = implode(' ', $errors);
            $this->redirect('/prices/edit?id=' . $id);
        }

        $repo->update($id, $row);
        $_SESSION['flash_success'] = 'Data harga berhasil diperbarui.';
        $this->redirect('/prices');
    }

    public function delete(): void
    {
        $this->requireAuth();
        verify_csrf();
        (new CopperPriceRepository())->delete((int) ($_POST['id'] ?? 0));
        $_SESSION['flash_success'] = 'Data harga berhasil dihapus.';
        $this->redirect('/prices');
    }
}
