<?php

use App\Controllers\AuthController;
use App\Controllers\CopperPriceController;
use App\Controllers\DashboardController;
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
