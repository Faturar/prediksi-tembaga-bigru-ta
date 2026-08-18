<?php

use App\Controllers\AuthController;
use App\Controllers\CopperPriceController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentationController;
use App\Controllers\EvaluationController;
use App\Controllers\ImportController;
use App\Controllers\ModelController;
use App\Controllers\PredictionController;
use App\Controllers\PublicController;
use App\Controllers\ReportController;

$router->get('/', [PublicController::class, 'index']);
$router->get('/historical', [PublicController::class, 'historical']);
$router->get('/forecast', [PublicController::class, 'forecast']);
$router->post('/forecast', [PublicController::class, 'submitForecast']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/admin/dokumentasi-ta', [DocumentationController::class, 'index']);
$router->get('/admin/dokumentasi-penelitian', [DocumentationController::class, 'research']);
$router->get('/admin/dokumentasi-penelitian/pembagian-dataset', [DocumentationController::class, 'datasetSplit']);
$router->get('/admin/dokumentasi-penelitian/normalisasi', [DocumentationController::class, 'normalization']);
$router->get('/admin/dokumentasi-penelitian/sliding-window', [DocumentationController::class, 'slidingWindow']);
$router->get('/admin/dokumentasi-penelitian/parameter-model', [DocumentationController::class, 'modelParameters']);
$router->get('/admin/dokumentasi-penelitian/hasil-training', [DocumentationController::class, 'trainingResults']);
$router->get('/admin/dokumentasi-penelitian/training-loss', [DocumentationController::class, 'trainingLoss']);
$router->get('/admin/dokumentasi-penelitian/manajemen-model', [DocumentationController::class, 'modelManagement']);
$router->get('/admin/dokumentasi-penelitian/log-training', [DocumentationController::class, 'trainingLog']);
$router->get('/admin/dokumentasi-penelitian/hasil-test', [DocumentationController::class, 'testResults']);
$router->get('/admin/dokumentasi-penelitian/evaluasi-model', [DocumentationController::class, 'modelEvaluation']);
$router->get('/admin/dokumentasi-penelitian/prediksi-recursive', [DocumentationController::class, 'recursivePrediction']);
$router->get('/admin/dokumentasi-ta/gambar-4-1', [DocumentationController::class, 'figure41']);
$router->get('/admin/dokumentasi-ta/gambar-4-2', [DocumentationController::class, 'figure42']);
$router->get('/admin/dokumentasi-ta/gambar-4-3', [DocumentationController::class, 'figure43']);
$router->get('/admin/dokumentasi-ta/gambar-4-4', [DocumentationController::class, 'figure44']);
$router->get('/admin/dokumentasi-ta/gambar-4-5', [DocumentationController::class, 'figure45']);

$router->get('/prices', [CopperPriceController::class, 'index']);
$router->post('/prices', [CopperPriceController::class, 'store']);
$router->get('/prices/edit', [CopperPriceController::class, 'edit']);
$router->post('/prices/update', [CopperPriceController::class, 'update']);
$router->post('/prices/delete', [CopperPriceController::class, 'delete']);
$router->get('/import', [ImportController::class, 'show']);
$router->post('/import', [ImportController::class, 'store']);

$router->get('/models', [ModelController::class, 'index']);
$router->get('/models/detail', [ModelController::class, 'detail']);
$router->get('/models/log', [ModelController::class, 'log']);
$router->post('/models/train', [ModelController::class, 'train']);
$router->post('/models/activate', [ModelController::class, 'activate']);
$router->post('/models/reset', [ModelController::class, 'reset']);

$router->get('/predictions', [PredictionController::class, 'index']);
$router->post('/predictions', [PredictionController::class, 'store']);
$router->post('/predictions/reset', [PredictionController::class, 'reset']);
$router->get('/evaluation', [EvaluationController::class, 'index']);
$router->get('/reports', [ReportController::class, 'index']);
