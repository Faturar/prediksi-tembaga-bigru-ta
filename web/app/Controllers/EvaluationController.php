<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class EvaluationController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $rows = Database::connection()->query(
            'SELECT m.version, m.window_size, m.units, m.dropout, m.batch_size, m.actual_epochs, m.configured_epochs, m.trained_at, mm.*
             FROM model_metrics mm
             JOIN model_runs m ON m.id = mm.model_run_id
             ORDER BY mm.created_at DESC'
        )->fetchAll();
        $this->view('evaluation/index', ['title' => 'Evaluasi', 'metrics' => $rows]);
    }
}
