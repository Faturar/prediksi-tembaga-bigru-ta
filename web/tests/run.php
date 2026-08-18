<?php

declare(strict_types=1);

require __DIR__ . '/../app/Core/bootstrap.php';
require __DIR__ . '/../app/Helpers/security.php';

$checks = [
    'bootstrap loads' => function (): bool {
        return function_exists('base_path') && class_exists(App\Core\Router::class);
    },
    'routes file exists' => function (): bool {
        return is_file(base_path('routes/web.php'));
    },
    'schema exists' => function (): bool {
        return is_file(base_path('database/schema.sql'));
    },
    'csv service loads' => function (): bool {
        return class_exists(App\Services\CsvImportService::class);
    },
    'csrf token helper works' => function (): bool {
        $token = csrf_token();
        return is_string($token) && strlen($token) === 64 && hash_equals($token, csrf_token());
    },
    'price validator rejects impossible OHLC' => function (): bool {
        [$row, $errors] = (new App\Services\CopperPriceValidator())->validate([
            'date' => '2026-08-11',
            'open' => '100',
            'high' => '90',
            'low' => '80',
            'close' => '95',
        ]);
        return $row['date'] === '2026-08-11' && $errors !== [];
    },
    'price CRUD routes exist' => function (): bool {
        $routes = file_get_contents(base_path('routes/web.php')) ?: '';
        return str_contains($routes, '/prices/edit')
            && str_contains($routes, '/prices/update')
            && str_contains($routes, '/prices/delete');
    },
    'public routes exist' => function (): bool {
        $routes = file_get_contents(base_path('routes/web.php')) ?: '';
        return str_contains($routes, '/historical')
            && str_contains($routes, '/forecast')
            && str_contains($routes, '/dashboard')
            && str_contains($routes, '/login');
    },
    'public pages have login admin link' => function (): bool {
        $layout = file_get_contents(base_path('resources/views/layouts/app.php')) ?: '';
        return str_contains($layout, 'Login Admin')
            && str_contains($layout, '/historical')
            && str_contains($layout, '/forecast');
    },
    'public views do not expose ML_API_KEY or artifact paths' => function (): bool {
        $views = file_get_contents(base_path('resources/views/public/index.php')) . file_get_contents(base_path('resources/views/public/historical.php')) . file_get_contents(base_path('resources/views/public/forecast.php'));
        return !str_contains($views, 'ML_API_KEY')
            && !str_contains($views, 'model_path')
            && !str_contains($views, 'scaler_path')
            && !str_contains($views, 'metadata_path');
    },
    'ML API client exposes train and predict' => function (): bool {
        return method_exists(App\Services\MlApiClient::class, 'train')
            && method_exists(App\Services\MlApiClient::class, 'predict')
            && method_exists(App\Services\MlApiClient::class, 'model');
    },
    'prediction window uses exact model window ascending' => function (): bool {
        $rows = [
            ['date' => '2026-08-09', 'close' => 104],
            ['date' => '2026-08-07', 'close' => 102],
            ['date' => '2026-08-08', 'close' => 103],
            ['date' => '2026-08-06', 'close' => 101],
        ];
        $window = (new App\Services\PredictionWindowService())->build($rows, 3);
        return count($window) === 3
            && array_column($window, 'date') === ['2026-08-07', '2026-08-08', '2026-08-09'];
    },
    'prediction window rejects insufficient data' => function (): bool {
        try {
            (new App\Services\PredictionWindowService())->build([['date' => '2026-08-11', 'close' => 100]], 2);
        } catch (LengthException $e) {
            return str_contains($e->getMessage(), 'minimal 2 observasi harga penutupan');
        }
        return false;
    },
    'prediction UI states one-step target and has no date picker' => function (): bool {
        $view = file_get_contents(base_path('resources/views/predictions/index.php')) ?: '';
        return str_contains($view, 'Periode Perdagangan Berikutnya')
            && str_contains($view, 'satu observasi perdagangan berikutnya')
            && !str_contains($view, 'type="date"');
    },
    'prediction create keeps traceability guard' => function (): bool {
        $source = file_get_contents(base_path('app/Repositories/PredictionRepository.php')) ?: '';
        return str_contains($source, 'count($window) !== $expectedWindowSize')
            && str_contains($source, 'prediction_inputs');
    },
    'dashboard historical chart is range based, not latest 30 only' => function (): bool {
        $controller = file_get_contents(base_path('app/Controllers/DashboardController.php')) ?: '';
        $repository = file_get_contents(base_path('app/Repositories/CopperPriceRepository.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/dashboard/index.php')) ?: '';

        return !str_contains($controller, 'array_slice($prices->orderedClosePrices(), -30)')
            && str_contains($controller, "'all' => ['label' => 'Semua Data'")
            && str_contains($controller, 'latestDate()')
            && str_contains($repository, 'SELECT date, close FROM copper_prices WHERE date >= ? ORDER BY date ASC')
            && str_contains($view, 'Pergerakan Harga Penutupan Tembaga')
            && str_contains($view, 'pointRadius: 0');
    },
    'TA documentation routes and screenshot mode exist' => function (): bool {
        $routes = file_get_contents(base_path('routes/web.php')) ?: '';
        $layout = file_get_contents(base_path('resources/views/layouts/app.php')) ?: '';
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/figure.php')) ?: '';
        $css = file_get_contents(base_path('public/assets/css/app.css')) ?: '';

        return str_contains($routes, '/admin/dokumentasi-ta/gambar-4-1')
            && str_contains($routes, '/admin/dokumentasi-ta/gambar-4-5')
            && str_contains($routes, '/admin/dokumentasi-penelitian')
            && str_contains($routes, '/admin/dokumentasi-penelitian/pembagian-dataset')
            && str_contains($routes, '/admin/dokumentasi-penelitian/normalisasi')
            && str_contains($routes, '/admin/dokumentasi-penelitian/sliding-window')
            && str_contains($routes, '/admin/dokumentasi-penelitian/parameter-model')
            && str_contains($routes, '/admin/dokumentasi-penelitian/hasil-training')
            && str_contains($routes, '/admin/dokumentasi-penelitian/training-loss')
            && str_contains($routes, '/admin/dokumentasi-penelitian/manajemen-model')
            && str_contains($routes, '/admin/dokumentasi-penelitian/log-training')
            && str_contains($routes, '/admin/dokumentasi-penelitian/hasil-test')
            && str_contains($routes, '/admin/dokumentasi-penelitian/evaluasi-model')
            && str_contains($routes, '/admin/dokumentasi-penelitian/prediksi-recursive')
            && str_contains($controller, 'trainingHistory')
            && str_contains($controller, 'preprocessingRows')
            && str_contains($controller, 'datasetSplitSummary')
            && str_contains($controller, 'normalizationSummary')
            && str_contains($controller, 'slidingWindowSummary')
            && str_contains($controller, 'modelParameterSummary')
            && str_contains($controller, 'trainingResultSummary')
            && str_contains($controller, 'trainingLossSummary')
            && str_contains($controller, 'modelManagementSummary')
            && str_contains($controller, 'trainingLogDocumentation')
            && str_contains($controller, 'testPredictionSummary')
            && str_contains($controller, 'modelEvaluationSummary')
            && str_contains($controller, 'recursivePredictionSummary')
            && str_contains($view, 'Data hasil training final belum tersedia.')
            && str_contains($layout, 'screenshot-mode')
            && str_contains($css, '.screenshot-mode .sidebar');
    },
    'dataset split documentation separates raw observations and sliding windows' => function (): bool {
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/dataset-split.php')) ?: '';
        $preprocessing = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'preprocessing.py') ?: '';

        return str_contains($preprocessing, 'train_end_index = int(len(prices) * train_ratio)')
            && str_contains($controller, 'floor($totalRecords * 0.8)')
            && str_contains($controller, 'train_observations')
            && str_contains($controller, 'train_samples')
            && str_contains($view, 'Pembagian Dataset Final')
            && str_contains($view, 'Jumlah Observasi')
            && str_contains($view, 'Setelah Sliding Window')
            && str_contains($view, 'copyDatasetSplitTable');
    },
    'normalization documentation uses train scaler parameters' => function (): bool {
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/normalization.php')) ?: '';
        $preprocessing = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'preprocessing.py') ?: '';

        return str_contains($preprocessing, 'scaler.fit_transform(train_prices)')
            && str_contains($preprocessing, 'scaler.transform(prices)')
            && str_contains($controller, 'min($trainCloseValues)')
            && str_contains($controller, 'max($trainCloseValues)')
            && str_contains($controller, 'scalerPath')
            && str_contains($view, 'Contoh Format Hasil Normalisasi')
            && str_contains($view, 'Min Close Train')
            && str_contains($view, 'Valid - sesuai scaler training')
            && str_contains($view, 'copyNormalizationTable');
    },
    'sliding window documentation follows make_sequences shape' => function (): bool {
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/sliding-window.php')) ?: '';
        $preprocessing = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'preprocessing.py') ?: '';

        return str_contains($preprocessing, 'for target_index in range(start_target, end_target)')
            && str_contains($preprocessing, 'values[target_index - window_size:target_index]')
            && str_contains($controller, 'windowExample(1')
            && str_contains($controller, "'input_shape' => sprintf('(%d, 1)'")
            && str_contains($controller, 'train_input_shape')
            && str_contains($controller, 'test_input_shape')
            && str_contains($controller, 't-30 s.d. t-1')
            && str_contains($view, 'Struktur Sliding Window 30 Observasi')
            && str_contains($view, 'slidingWindowPatternTable')
            && str_contains($controller, 't-30 s.d. t-1')
            && str_contains($view, 'Observasi Ke')
            && str_contains($view, 'Close Input')
            && str_contains($view, 'input_values')
            && str_contains($view, 'Input Shape Train')
            && str_contains($view, 'Input Shape Test')
            && str_contains($view, 'copySlidingWindowTable');
    },
    'model parameter documentation validates BiGRU source configuration' => function (): bool {
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/model-parameters.php')) ?: '';
        $modelSource = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'model.py') ?: '';
        $trainingSource = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'training.py') ?: '';
        $schemaSource = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'schemas' . DIRECTORY_SEPARATOR . 'ml.py') ?: '';

        return str_contains($modelSource, 'Bidirectional(GRU(units=units')
            && str_contains($modelSource, 'Dense(1)')
            && str_contains($modelSource, 'loss="mse"')
            && str_contains($trainingSource, 'shuffle=False')
            && str_contains($schemaSource, 'le=7')
            && str_contains($controller, 'modelSourceValidation')
            && str_contains($controller, 'Horizon Operasional')
            && str_contains($view, 'Parameter Model BiGRU')
            && str_contains($view, 'copyModelParametersTable');
    },
    'training results documentation uses final run metrics and artifacts' => function (): bool {
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $repository = file_get_contents(base_path('app/Repositories/ModelRunRepository.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/training-results.php')) ?: '';

        return str_contains($repository, 'training_duration_seconds')
            && str_contains($controller, 'final_training_loss')
            && str_contains($controller, 'artifact_consistency')
            && str_contains($controller, 'formatDuration')
            && str_contains($view, 'Ringkasan Hasil Pelatihan Model')
            && str_contains($view, 'Model Artifact')
            && str_contains($view, 'copyTrainingResultsTable');
    },
    'training loss documentation parses real final training loss history' => function (): bool {
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/training-loss.php')) ?: '';
        $training = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'training.py') ?: '';

        return str_contains($training, 'history.history.get("loss"')
            && str_contains($training, 'shuffle=False')
            && str_contains($controller, 'logLossHistory')
            && str_contains($controller, 'final_loss_status')
            && str_contains($view, 'Grafik Loss Pelatihan Model BiGRU')
            && str_contains($view, 'Training Loss')
            && str_contains($view, 'Loss (MSE)');
    },
    'model management documentation uses final run metrics and active status' => function (): bool {
        $routes = file_get_contents(base_path('routes/web.php')) ?: '';
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/model-management.php')) ?: '';
        $css = file_get_contents(base_path('public/assets/css/app.css')) ?: '';

        return str_contains($routes, '/admin/dokumentasi-penelitian/manajemen-model')
            && str_contains($controller, 'modelManagementSummary')
            && str_contains($controller, 'modelRunDocumentationData')
            && str_contains($controller, "metrics_sync")
            && str_contains($controller, "table46_sync")
            && str_contains($controller, "table47_sync")
            && str_contains($view, 'Ringkasan Manajemen dan Riwayat Training Model')
            && str_contains($view, 'MAE')
            && str_contains($view, 'RMSE')
            && str_contains($view, 'MAPE')
            && str_contains($view, 'AKTIF')
            && str_contains($view, 'copyModelManagementTable')
            && str_contains($css, '.model-management-table')
            && str_contains($css, '.screenshot-mode .model-management-validation');
    },
    'training log documentation renders final log markers and metadata validation' => function (): bool {
        $routes = file_get_contents(base_path('routes/web.php')) ?: '';
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/training-log.php')) ?: '';
        $training = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'training.py') ?: '';
        $runner = file_get_contents(base_path('scripts/run-training.php')) ?: '';

        return str_contains($routes, '/admin/dokumentasi-penelitian/log-training')
            && str_contains($training, '[TRAIN][START]')
            && str_contains($training, '[TRAIN][DATA]')
            && str_contains($training, '[TRAIN][FIT]')
            && str_contains($training, '[TRAIN][METRICS]')
            && str_contains($training, '[TRAIN][DONE]')
            && str_contains($runner, 'training-{$safeVersion}.log')
            && str_contains($controller, 'trainingLogDocumentation')
            && str_contains($controller, 'parseEpochRowsFromLog')
            && str_contains($controller, 'focusedTrainingLogLines')
            && str_contains($controller, 'metadata_sync')
            && str_contains($view, 'Log Proses Pelatihan Model BiGRU')
            && str_contains($view, 'training-log-doc-box')
            && str_contains($view, 'Epoch Lines Found')
            && str_contains($view, 'Sinkron dengan Metadata')
            && str_contains($view, 'copyTrainingLog');
    },
    'test results documentation uses final test_series and validates metrics' => function (): bool {
        $routes = file_get_contents(base_path('routes/web.php')) ?: '';
        $layout = file_get_contents(base_path('resources/views/layouts/app.php')) ?: '';
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/test-results.php')) ?: '';
        $training = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'training.py') ?: '';
        $metrics = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'metrics.py') ?: '';

        return str_contains($routes, '/admin/dokumentasi-penelitian/hasil-test')
            && str_contains($layout, "['1', 'table', 'chart']")
            && str_contains($training, 'prepared.scaler.inverse_transform(predicted_scaled)')
            && str_contains($training, '"test_series"')
            && str_contains($metrics, 'np.mean(np.abs(actual - predicted))')
            && str_contains($controller, 'testPredictionSummary')
            && str_contains($controller, 'metricsFromTestSeries')
            && str_contains($controller, 'absolute_error')
            && str_contains($view, 'Format Hasil Prediksi pada Data Uji')
            && str_contains($view, 'Perbandingan Harga Aktual dan Hasil Prediksi')
            && str_contains($view, 'Screenshot Tabel')
            && str_contains($view, 'Screenshot Grafik')
            && str_contains($view, 'Selisih Absolut')
            && str_contains($view, 'Tanggal data uji')
            && str_contains($view, 'Close Price');
    },
    'model evaluation documentation uses metadata metrics validated by test_series' => function (): bool {
        $routes = file_get_contents(base_path('routes/web.php')) ?: '';
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/model-evaluation.php')) ?: '';
        $training = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'training.py') ?: '';
        $metrics = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'metrics.py') ?: '';

        return str_contains($routes, '/admin/dokumentasi-penelitian/evaluasi-model')
            && str_contains($training, '"metrics"')
            && str_contains($training, '"test_series"')
            && str_contains($metrics, 'safe_actual = np.where(actual == 0, np.nan, actual)')
            && str_contains($controller, 'modelEvaluationSummary')
            && str_contains($controller, 'zero_division_status')
            && str_contains($controller, 'table48_sync')
            && str_contains($controller, 'figure46_sync')
            && str_contains($view, 'Hasil Evaluasi Model BiGRU')
            && str_contains($controller, 'Rata-rata selisih absolut')
            && str_contains($controller, 'Lebih sensitif terhadap error besar')
            && str_contains($controller, 'Rata-rata error relatif')
            && str_contains($view, 'copyModelEvaluationTable');
    },
    'recursive prediction documentation uses operational forecast history' => function (): bool {
        $routes = file_get_contents(base_path('routes/web.php')) ?: '';
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $repository = file_get_contents(base_path('app/Repositories/PredictionRepository.php')) ?: '';
        $windowService = file_get_contents(base_path('app/Services/PredictionWindowService.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/recursive-prediction.php')) ?: '';
        $prediction = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'prediction.py') ?: '';
        $schema = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'schemas' . DIRECTORY_SEPARATOR . 'ml.py') ?: '';

        return str_contains($routes, '/admin/dokumentasi-penelitian/prediksi-recursive')
            && str_contains($repository, 'latestForModel')
            && str_contains($repository, 'prediction_inputs')
            && str_contains($repository, 'prediction_outputs')
            && str_contains($windowService, 'array_slice($orderedRows, -1 * $windowSize)')
            && str_contains($schema, 'horizon: int = Field(default=1, ge=1, le=7)')
            && str_contains($prediction, 'scaler.transform(values)')
            && str_contains($prediction, 'model.predict(x, verbose=0)')
            && str_contains($prediction, 'scaler.inverse_transform(predicted_scaled)')
            && str_contains($prediction, 'np.concatenate([scaled_window[1:], predicted_scaled]')
            && str_contains($controller, 'recursivePredictionSummary')
            && str_contains($controller, 'recursivePredictionSourceValidation')
            && str_contains($controller, 'latestForModel((int) $finalModel')
            && str_contains($view, 'Prediksi Recursive Multi-Periode')
            && str_contains($controller, 'Prediksi P+2 s.d. P+N')
            && str_contains($view, 'copyRecursivePredictionTable');
    },
    'research documentation calculates preprocessing table from final data' => function (): bool {
        $repository = file_get_contents(base_path('app/Repositories/CopperPriceRepository.php')) ?: '';
        $controller = file_get_contents(base_path('app/Controllers/DocumentationController.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/documentation-ta/research.php')) ?: '';

        return str_contains($repository, 'preprocessingSummary')
            && str_contains($repository, 'GROUP BY date HAVING COUNT(*) > 1')
            && str_contains($repository, 'close IS NULL OR close <= 0')
            && str_contains($repository, 'volume IS NULL')
            && str_contains($controller, 'observasi data sampel')
            && str_contains($controller, 'Model menggunakan nilai Close sebagai variabel masukan')
            && str_contains($view, 'Ringkasan Prapemrosesan Data')
            && str_contains($view, 'Kondisi Sampel')
            && str_contains($view, 'Salin Tabel')
            && str_contains($view, 'Status sinkronisasi') === false;
    },
    'sample CSV supports expected preprocessing sample conditions' => function (): bool {
        $path = dirname(base_path()) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'sample_copper_prices.csv';
        $rows = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES) ?: []);
        $headers = array_map('strtolower', $rows[0] ?? []);
        $records = array_slice($rows, 1);
        $assoc = array_map(fn ($row) => array_combine($headers, $row), $records);
        $dates = array_column($assoc, 'date');
        $sortedDesc = $dates;
        rsort($sortedDesc);

        return count($records) === 15
            && count(array_filter(array_count_values($dates), fn ($count) => $count > 1)) === 0
            && count(array_filter($assoc, fn ($row) => trim((string) $row['close']) === '')) === 0
            && count(array_filter($assoc, fn ($row) => trim((string) $row['volume']) === '')) === 1
            && $dates === $sortedDesc;
    },
    'model training supports custom and automatic model names' => function (): bool {
        $controller = file_get_contents(base_path('app/Controllers/ModelController.php')) ?: '';
        $repository = file_get_contents(base_path('app/Repositories/ModelRunRepository.php')) ?: '';
        $view = file_get_contents(base_path('resources/views/models/index.php')) ?: '';

        return str_contains($controller, "'model_name' =>")
            && str_contains($controller, 'Kosongkan untuk nama otomatis') === false
            && str_contains($controller, 'BiGRU W%d U%d E%d')
            && str_contains($repository, 'version, model_name, status')
            && str_contains($view, 'name="model_name"')
            && str_contains($view, 'Kosongkan untuk nama otomatis');
    },
];

$failed = 0;
foreach ($checks as $name => $check) {
    $ok = $check();
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    $failed += $ok ? 0 : 1;
}

exit($failed === 0 ? 0 : 1);
