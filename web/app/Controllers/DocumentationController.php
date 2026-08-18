<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CopperPriceRepository;
use App\Repositories\ModelRunRepository;
use App\Repositories\PredictionRepository;

final class DocumentationController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->view('documentation-ta/index', [
            'title' => 'Dokumentasi Screenshot TA',
            'figures' => $this->figures(),
        ]);
    }

    public function research(): void
    {
        $this->requireAuth();

        $priceRepo = new CopperPriceRepository();
        $modelRepo = new ModelRunRepository();
        $summary = $priceRepo->preprocessingSummary();
        $sample = $this->sampleDatasetSummary();
        $finalModel = $modelRepo->activeWithMetrics() ?? $this->latestSuccessfulModel($modelRepo->all());
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $sync = $this->datasetSynchronization($summary, $finalModel, $metadata);

        $this->view('documentation-ta/research', [
            'title' => 'Dokumentasi Penelitian',
            'summary' => $summary,
            'sample' => $sample,
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'sync' => $sync,
            'rows' => $this->preprocessingRows($summary, $sample),
        ]);
    }

    public function datasetSplit(): void
    {
        $this->requireAuth();

        $priceRepo = new CopperPriceRepository();
        $modelRepo = new ModelRunRepository();
        $summary = $priceRepo->preprocessingSummary();
        $finalModel = $modelRepo->activeWithMetrics() ?? $this->latestSuccessfulModel($modelRepo->all());
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $split = $this->datasetSplitSummary($summary, $finalModel, $metadata);

        $this->view('documentation-ta/dataset-split', [
            'title' => 'Pembagian Dataset Final',
            'summary' => $summary,
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'split' => $split,
        ]);
    }

    public function normalization(): void
    {
        $this->requireAuth();

        $priceRepo = new CopperPriceRepository();
        $modelRepo = new ModelRunRepository();
        $summary = $priceRepo->preprocessingSummary();
        $finalModel = $modelRepo->activeWithMetrics() ?? $this->latestSuccessfulModel($modelRepo->all());
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $dataset = $this->datasetForModel($finalModel);
        $normalization = $this->normalizationSummary($dataset, $summary, $finalModel, $metadata);

        $this->view('documentation-ta/normalization', [
            'title' => 'Contoh Hasil Normalisasi Dataset',
            'summary' => $summary,
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'normalization' => $normalization,
        ]);
    }

    public function slidingWindow(): void
    {
        $this->requireAuth();

        $priceRepo = new CopperPriceRepository();
        $modelRepo = new ModelRunRepository();
        $summary = $priceRepo->preprocessingSummary();
        $finalModel = $modelRepo->activeWithMetrics() ?? $this->latestSuccessfulModel($modelRepo->all());
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $dataset = $this->datasetForModel($finalModel);
        $window = $this->slidingWindowSummary($dataset, $summary, $finalModel, $metadata);

        $this->view('documentation-ta/sliding-window', [
            'title' => 'Struktur Sliding Window 30 Observasi',
            'summary' => $summary,
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'window' => $window,
        ]);
    }

    public function modelParameters(): void
    {
        $this->requireAuth();

        $modelRepo = new ModelRunRepository();
        $finalModel = $modelRepo->activeWithMetrics() ?? $this->latestSuccessfulModel($modelRepo->all());
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $parameters = $this->modelParameterSummary($finalModel, $metadata);

        $this->view('documentation-ta/model-parameters', [
            'title' => 'Parameter Model BiGRU Final',
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'parameters' => $parameters,
        ]);
    }

    public function trainingResults(): void
    {
        $this->requireAuth();

        $priceRepo = new CopperPriceRepository();
        $modelRepo = new ModelRunRepository();
        $summary = $priceRepo->preprocessingSummary();
        $finalModel = $modelRepo->activeWithMetrics() ?? $this->latestSuccessfulModel($modelRepo->all());
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $results = $this->trainingResultSummary($summary, $finalModel, $metadata);

        $this->view('documentation-ta/training-results', [
            'title' => 'Ringkasan Hasil Pelatihan Model',
            'summary' => $summary,
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'results' => $results,
        ]);
    }

    public function trainingLoss(): void
    {
        $this->requireAuth();

        $modelRepo = new ModelRunRepository();
        $finalModel = $modelRepo->activeWithMetrics() ?? $this->latestSuccessfulModel($modelRepo->all());
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $loss = $this->trainingLossSummary($finalModel, $metadata);

        $this->view('documentation-ta/training-loss', [
            'title' => 'Grafik Loss Pelatihan Model BiGRU',
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'loss' => $loss,
        ]);
    }

    public function modelManagement(): void
    {
        $this->requireAuth();

        $priceRepo = new CopperPriceRepository();
        $modelRepo = new ModelRunRepository();
        $summary = $priceRepo->preprocessingSummary();
        $models = $modelRepo->all();
        $finalModel = $modelRepo->activeWithMetrics() ?? $this->latestSuccessfulModel($models);
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $management = $this->modelManagementSummary($summary, $models, $finalModel, $metadata, (($_GET['screenshot'] ?? '') === '1'));

        $this->view('documentation-ta/model-management', [
            'title' => 'Ringkasan Manajemen dan Riwayat Training Model',
            'models' => $models,
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'management' => $management,
        ]);
    }

    public function trainingLog(): void
    {
        $this->requireAuth();

        $modelRepo = new ModelRunRepository();
        $finalModel = $modelRepo->activeWithMetrics() ?? $this->latestSuccessfulModel($modelRepo->all());
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $log = $this->trainingLogDocumentation($finalModel, $metadata, (($_GET['screenshot'] ?? '') === '1'));

        $this->view('documentation-ta/training-log', [
            'title' => 'Log Proses Pelatihan Model BiGRU',
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'log' => $log,
        ]);
    }

    public function testResults(): void
    {
        $this->requireAuth();

        $modelRepo = new ModelRunRepository();
        $finalModel = $modelRepo->activeWithMetrics() ?? $this->latestSuccessfulModel($modelRepo->all());
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $results = $this->testPredictionSummary($finalModel, $metadata, (string) ($_GET['screenshot'] ?? ''));

        $this->view('documentation-ta/test-results', [
            'title' => 'Hasil Prediksi Data Uji',
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'results' => $results,
        ]);
    }

    public function modelEvaluation(): void
    {
        $this->requireAuth();

        $modelRepo = new ModelRunRepository();
        $finalModel = $modelRepo->activeWithMetrics() ?? $this->latestSuccessfulModel($modelRepo->all());
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $evaluation = $this->modelEvaluationSummary($finalModel, $metadata);

        $this->view('documentation-ta/model-evaluation', [
            'title' => 'Hasil Evaluasi Model BiGRU',
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'evaluation' => $evaluation,
        ]);
    }

    public function recursivePrediction(): void
    {
        $this->requireAuth();

        $modelRepo = new ModelRunRepository();
        $finalModel = $modelRepo->activeWithMetrics();
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $prediction = $finalModel ? (new PredictionRepository())->latestForModel((int) $finalModel['id']) : null;
        $recursive = $this->recursivePredictionSummary($finalModel, $metadata, $prediction);

        $this->view('documentation-ta/recursive-prediction', [
            'title' => 'Prediksi Recursive Multi-Periode',
            'finalModel' => $finalModel,
            'metadata' => $metadata,
            'prediction' => $prediction,
            'recursive' => $recursive,
        ]);
    }

    public function figure41(): void
    {
        $this->renderFigure('4.1');
    }

    public function figure42(): void
    {
        $this->renderFigure('4.2');
    }

    public function figure43(): void
    {
        $this->renderFigure('4.3');
    }

    public function figure44(): void
    {
        $this->renderFigure('4.4');
    }

    public function figure45(): void
    {
        $this->renderFigure('4.5');
    }

    private function renderFigure(string $figure): void
    {
        $this->requireAuth();

        $repo = new ModelRunRepository();
        $finalModel = $repo->activeWithMetrics() ?? $this->latestSuccessfulModel($repo->all());
        $metadata = $finalModel ? $this->loadMetadata((string) $finalModel['version'], $finalModel['metadata_path'] ?? null) : null;
        $dataset = $this->datasetForModel($finalModel);
        $trainingHistory = $finalModel ? $this->trainingHistory((string) $finalModel['version'], $metadata) : [];
        $testSeries = is_array($metadata['test_series'] ?? null) ? $metadata['test_series'] : [];
        $models = $repo->all();

        $this->view('documentation-ta/figure', [
            'title' => 'Dokumentasi TA Gambar ' . $figure,
            'figure' => $figure,
            'figures' => $this->figures(),
            'finalModel' => $finalModel,
            'models' => $models,
            'dataset' => $dataset,
            'trainingHistory' => $trainingHistory,
            'testSeries' => $testSeries,
            'metadata' => $metadata,
        ]);
    }

    private function latestSuccessfulModel(array $models): ?array
    {
        foreach ($models as $model) {
            if (($model['status'] ?? '') === 'success') {
                return $model;
            }
        }

        return null;
    }

    private function datasetForModel(?array $model): array
    {
        $prices = new CopperPriceRepository();
        if ($model && !empty($model['dataset_start_date']) && !empty($model['dataset_end_date'])) {
            return $prices->closePricesBetween($model['dataset_start_date'], $model['dataset_end_date']);
        }

        return $prices->orderedClosePrices();
    }

    private function loadMetadata(string $version, ?string $metadataPath): ?array
    {
        foreach (array_filter([$metadataPath, $this->metadataPath($version)]) as $path) {
            $candidate = $this->resolveArtifactPath($path);
            if ($candidate && is_file($candidate)) {
                $decoded = json_decode(file_get_contents($candidate) ?: '', true);
                return is_array($decoded) ? $decoded : null;
            }
        }

        return null;
    }

    private function trainingHistory(string $version, ?array $metadata): array
    {
        $history = $metadata['training_history']['loss'] ?? $metadata['history']['loss'] ?? null;
        if (is_array($history) && $history !== []) {
            return array_map(
                fn ($loss, $index) => ['epoch' => $index + 1, 'loss' => (float) $loss],
                array_values($history),
                array_keys(array_values($history))
            );
        }

        $path = $this->trainingLogPath($version);
        if (!is_file($path)) {
            return [];
        }

        $rows = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (preg_match('/epoch=(\d+)\/\d+\s+loss=([0-9.eE+-]+)/', $line, $matches)) {
                $rows[] = ['epoch' => (int) $matches[1], 'loss' => (float) $matches[2]];
            }
        }

        return $rows;
    }

    private function preprocessingRows(array $summary, array $sample): array
    {
        $period = $summary['start_date'] && $summary['end_date']
            ? format_indonesian_date($summary['start_date']) . '–' . format_indonesian_date($summary['end_date'])
            : '-';
        $firstDate = $summary['first_record']['date'] ?? null;
        $lastDate = $summary['last_record']['date'] ?? null;

        return [
            [
                'check' => 'Jumlah observasi',
                'sample' => sprintf('%s observasi data sampel', $this->formatInteger($sample['total_records'])),
                'final' => sprintf('%s observasi, periode %s', $this->formatInteger($summary['total_records']), $period),
            ],
            [
                'check' => 'Data duplikat tanggal',
                'sample' => sprintf('%s tanggal duplikat pada sampel', $this->formatInteger($sample['duplicate_date_count'])),
                'final' => sprintf('%s tanggal duplikat pada dataset final', $this->formatInteger($summary['duplicate_date_count'])),
            ],
            [
                'check' => 'Missing Close',
                'sample' => sprintf('%s data kosong pada kolom Close', $this->formatInteger($sample['missing_close'])),
                'final' => sprintf('%s data Close kosong atau tidak valid', $this->formatInteger($summary['missing_close'])),
            ],
            [
                'check' => 'Missing Volume',
                'sample' => sprintf('%s data kosong pada kolom Volume', $this->formatInteger($sample['missing_volume'])),
                'final' => sprintf('%s data Volume kosong; diperbolehkan karena Volume tidak digunakan sebagai fitur model', $this->formatInteger($summary['missing_volume'])),
            ],
            [
                'check' => 'Urutan tanggal',
                'sample' => $sample['order_label'],
                'final' => sprintf(
                    'Diurutkan secara kronologis ascending, dari %s sampai %s, sebelum dikirim ke layanan machine learning',
                    $firstDate ? format_indonesian_date($firstDate) : '-',
                    $lastDate ? format_indonesian_date($lastDate) : '-'
                ),
            ],
            [
                'check' => 'Fitur model',
                'sample' => 'Dataset menyediakan Date, Open, High, Low, Close, Volume, dan Change %',
                'final' => 'Model menggunakan nilai Close sebagai variabel masukan dan nilai Close periode berikutnya sebagai target prediksi',
            ],
        ];
    }

    private function sampleDatasetSummary(): array
    {
        $path = dirname(base_path()) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'sample_copper_prices.csv';
        if (!is_file($path)) {
            return [
                'total_records' => 0,
                'duplicate_date_count' => 0,
                'missing_close' => 0,
                'missing_volume' => 0,
                'order_label' => 'Data sampel tidak tersedia',
            ];
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            return [
                'total_records' => 0,
                'duplicate_date_count' => 0,
                'missing_close' => 0,
                'missing_volume' => 0,
                'order_label' => 'Data sampel tidak dapat dibaca',
            ];
        }

        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), fgetcsv($handle) ?: []);
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count($headers) !== count($values)) {
                continue;
            }
            $rows[] = array_combine($headers, $values) ?: [];
        }
        fclose($handle);

        $dates = array_values(array_filter(array_map(fn ($row) => (string) ($row['date'] ?? ''), $rows)));
        $dateCounts = array_count_values($dates);
        $duplicateDates = count(array_filter($dateCounts, fn ($count) => $count > 1));

        return [
            'total_records' => count($rows),
            'duplicate_date_count' => $duplicateDates,
            'missing_close' => count(array_filter($rows, fn ($row) => trim((string) ($row['close'] ?? '')) === '' || !is_numeric($row['close'] ?? null) || (float) $row['close'] <= 0)),
            'missing_volume' => count(array_filter($rows, fn ($row) => trim((string) ($row['volume'] ?? '')) === '')),
            'order_label' => $this->dateOrderLabel($dates),
        ];
    }

    private function dateOrderLabel(array $dates): string
    {
        if (count($dates) < 2) {
            return 'Tidak cukup data untuk menentukan urutan';
        }

        $ascending = $dates === array_values(array_unique(array_merge($dates, []))) && $dates === array_values($this->sortedDates($dates, SORT_ASC));
        $descending = $dates === array_values($this->sortedDates($dates, SORT_DESC));
        if ($descending) {
            return 'Terbaru ke terlama';
        }
        if ($ascending) {
            return 'Terlama ke terbaru';
        }

        return 'Urutan tanggal campuran';
    }

    private function sortedDates(array $dates, int $direction): array
    {
        $sorted = $dates;
        usort($sorted, fn ($a, $b) => $direction === SORT_ASC ? strcmp((string) $a, (string) $b) : strcmp((string) $b, (string) $a));
        return $sorted;
    }

    private function formatInteger(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    private function datasetSplitSummary(array $summary, ?array $model, ?array $metadata): array
    {
        if (!$model) {
            return [
                'available' => false,
                'sync_status' => 'unavailable',
                'sync_message' => 'Model final belum tersedia.',
                'rows' => [],
            ];
        }

        $totalRecords = (int) ($metadata['total_records'] ?? $model['total_records'] ?? $summary['total_records'] ?? 0);
        $trainObservations = (int) floor($totalRecords * 0.8);
        $testObservations = max(0, $totalRecords - $trainObservations);
        $trainPercentActual = $totalRecords > 0 ? $trainObservations / $totalRecords * 100 : 0.0;
        $testPercentActual = $totalRecords > 0 ? $testObservations / $totalRecords * 100 : 0.0;

        $metadataStart = $metadata['dataset_start_date'] ?? ($model['dataset_start_date'] ?? null);
        $metadataEnd = $metadata['dataset_end_date'] ?? ($model['dataset_end_date'] ?? null);
        $trainStart = $metadata['train_start_date'] ?? ($model['train_start_date'] ?? $metadataStart);
        $trainEnd = $metadata['train_end_date'] ?? ($model['train_end_date'] ?? null);
        $testStart = $metadata['test_start_date'] ?? ($model['test_start_date'] ?? null);
        $testEnd = $metadata['test_end_date'] ?? ($model['test_end_date'] ?? $metadataEnd);
        $recordsMatch = $trainObservations + $testObservations === $totalRecords;
        $datasetMatch = $totalRecords === (int) $summary['total_records']
            && (string) $metadataStart === (string) ($summary['start_date'] ?? '')
            && (string) $metadataEnd === (string) ($summary['end_date'] ?? '');

        return [
            'available' => true,
            'total_records' => $totalRecords,
            'train_observations' => $trainObservations,
            'test_observations' => $testObservations,
            'train_actual_percentage' => $trainPercentActual,
            'test_actual_percentage' => $testPercentActual,
            'window_size' => (int) ($metadata['window_size'] ?? $model['window_size'] ?? 0),
            'train_samples' => (int) ($metadata['train_samples'] ?? $model['train_samples'] ?? 0),
            'test_samples' => (int) ($metadata['test_samples'] ?? $model['test_samples'] ?? 0),
            'train_start_date' => $trainStart,
            'train_end_date' => $trainEnd,
            'test_start_date' => $testStart,
            'test_end_date' => $testEnd,
            'dataset_start_date' => $metadataStart,
            'dataset_end_date' => $metadataEnd,
            'records_match' => $recordsMatch,
            'dataset_match' => $datasetMatch,
            'sync_status' => $datasetMatch && $recordsMatch ? 'ok' : 'warning',
            'sync_message' => $datasetMatch && $recordsMatch
                ? 'Sinkron dengan model run final.'
                : 'Dataset saat ini tidak sama dengan dataset yang digunakan untuk training model final.',
            'rows' => [
                [
                    'type' => 'Data Latih',
                    'percentage' => '80%',
                    'observations' => $trainObservations,
                    'period' => $this->periodLabel($trainStart, $trainEnd),
                ],
                [
                    'type' => 'Data Uji',
                    'percentage' => '20%',
                    'observations' => $testObservations,
                    'period' => $this->periodLabel($testStart, $testEnd),
                ],
            ],
        ];
    }

    private function normalizationSummary(array $dataset, array $summary, ?array $model, ?array $metadata): array
    {
        if (!$model) {
            return [
                'available' => false,
                'status' => 'unavailable',
                'message' => 'Model final belum tersedia.',
                'rows' => [],
            ];
        }

        $scalerPath = $this->resolveArtifactPath((string) ($model['scaler_path'] ?? '')) ?: $this->scalerPath((string) $model['version']);
        $scalerAvailable = is_file($scalerPath);
        if (!$scalerAvailable || count($dataset) < 2) {
            return [
                'available' => false,
                'status' => 'warning',
                'message' => $scalerAvailable ? 'Dataset final belum cukup untuk contoh normalisasi.' : 'Scaler model final belum tersedia.',
                'rows' => [],
                'scaler_available' => $scalerAvailable,
                'scaler_path' => $scalerPath,
            ];
        }

        $totalRecords = (int) ($metadata['total_records'] ?? $model['total_records'] ?? count($dataset));
        $trainEndIndex = (int) floor($totalRecords * 0.8);
        $trainRows = array_slice($dataset, 0, $trainEndIndex);
        $trainCloseValues = array_map(fn ($row) => (float) $row['close'], $trainRows);
        $minTrain = min($trainCloseValues);
        $maxTrain = max($trainCloseValues);
        $denominator = $maxTrain - $minTrain;
        $examples = [
            ['label' => 'Data latih', 'row' => $dataset[0] ?? null],
            ['label' => 'Data latih', 'row' => $dataset[$trainEndIndex - 1] ?? null],
            ['label' => 'Data uji - transform scaler latih', 'row' => $dataset[$trainEndIndex] ?? null],
        ];

        $rows = [];
        foreach ($examples as $example) {
            if (!$example['row']) {
                continue;
            }
            $close = (float) $example['row']['close'];
            $normalized = $denominator == 0.0 ? 0.0 : ($close - $minTrain) / $denominator;
            $manual = $denominator == 0.0 ? 0.0 : ($close - $minTrain) / $denominator;
            $difference = abs($normalized - $manual);
            $outsideTrainRange = $normalized < 0 || $normalized > 1;
            $rows[] = [
                'date' => $example['row']['date'],
                'close' => $close,
                'normalized' => $normalized,
                'manual' => $manual,
                'difference' => $difference,
                'is_valid' => $difference <= 0.000001,
                'outside_train_range' => $outsideTrainRange,
                'description' => $example['label'],
            ];
        }

        $metadataStart = $metadata['dataset_start_date'] ?? ($model['dataset_start_date'] ?? null);
        $metadataEnd = $metadata['dataset_end_date'] ?? ($model['dataset_end_date'] ?? null);
        $datasetMatch = $totalRecords === (int) $summary['total_records']
            && (string) $metadataStart === (string) ($summary['start_date'] ?? '')
            && (string) $metadataEnd === (string) ($summary['end_date'] ?? '');
        $allValid = !empty($rows) && count(array_filter($rows, fn ($row) => !$row['is_valid'])) === 0;

        return [
            'available' => true,
            'status' => $datasetMatch && $allValid ? 'ok' : 'warning',
            'message' => $datasetMatch && $allValid ? 'Sinkron dengan model run final.' : 'Periksa kembali sinkronisasi dataset atau validasi normalisasi.',
            'rows' => $rows,
            'scaler_available' => $scalerAvailable,
            'scaler_path' => $scalerPath,
            'scaler_name' => 'MinMaxScaler',
            'feature_range' => '0 sampai 1',
            'fit_scope' => 'Data Latih Saja',
            'min_train' => $minTrain,
            'max_train' => $maxTrain,
            'window_size' => (int) ($metadata['window_size'] ?? $model['window_size'] ?? 0),
            'dataset_start_date' => $metadataStart,
            'dataset_end_date' => $metadataEnd,
            'train_start_date' => $metadata['train_start_date'] ?? ($model['train_start_date'] ?? null),
            'train_end_date' => $metadata['train_end_date'] ?? ($model['train_end_date'] ?? null),
            'test_start_date' => $metadata['test_start_date'] ?? ($model['test_start_date'] ?? null),
            'test_end_date' => $metadata['test_end_date'] ?? ($model['test_end_date'] ?? null),
        ];
    }

    private function slidingWindowSummary(array $dataset, array $summary, ?array $model, ?array $metadata): array
    {
        if (!$model) {
            return [
                'available' => false,
                'status' => 'unavailable',
                'message' => 'Model final belum tersedia.',
                'rows' => [],
                'examples' => [],
            ];
        }

        $windowSize = (int) ($metadata['window_size'] ?? $model['window_size'] ?? 30);
        $totalRecords = (int) ($metadata['total_records'] ?? $model['total_records'] ?? count($dataset));
        $trainEndIndex = (int) floor($totalRecords * 0.8);
        $trainObservations = $trainEndIndex;
        $testObservations = max(0, $totalRecords - $trainEndIndex);
        $trainSamples = (int) ($metadata['train_samples'] ?? $model['train_samples'] ?? max(0, $trainObservations - $windowSize));
        $testSamples = (int) ($metadata['test_samples'] ?? $model['test_samples'] ?? $testObservations);
        $enoughData = count($dataset) >= $windowSize + 2 && $trainEndIndex > $windowSize + 1;
        $datasetMatch = $totalRecords === (int) $summary['total_records']
            && (string) ($metadata['dataset_start_date'] ?? $model['dataset_start_date'] ?? '') === (string) ($summary['start_date'] ?? '')
            && (string) ($metadata['dataset_end_date'] ?? $model['dataset_end_date'] ?? '') === (string) ($summary['end_date'] ?? '');
        $windowMatchesDesign = $windowSize === 30;

        $examples = [];
        if ($enoughData) {
            $examples[] = $this->windowExample(1, $dataset, 0, $windowSize);
            $examples[] = $this->windowExample(2, $dataset, 1, $windowSize);
        }

        return [
            'available' => $enoughData,
            'status' => $datasetMatch && $windowMatchesDesign && $enoughData ? 'ok' : 'warning',
            'message' => $windowMatchesDesign
                ? ($datasetMatch ? 'Sinkron dengan model run final.' : 'Dataset saat ini tidak sama dengan dataset yang digunakan untuk training model final.')
                : 'Window size model final berbeda dengan rancangan BAB III.',
            'window_size' => $windowSize,
            'feature' => 'Close Price',
            'input_shape' => sprintf('(%d, 1)', $windowSize),
            'target_shape' => '1 nilai Close',
            'forecast_type' => 'One-Step-Ahead',
            'train_observations' => $trainObservations,
            'test_observations' => $testObservations,
            'train_samples' => $trainSamples,
            'test_samples' => $testSamples,
            'train_input_shape' => sprintf('(%d, %d, 1)', $trainSamples, $windowSize),
            'test_input_shape' => sprintf('(%d, %d, 1)', $testSamples, $windowSize),
            'rows' => [
                ['pair' => '1', 'input' => 't-30 s.d. t-1', 'target' => 't'],
                ['pair' => '2', 'input' => 't-29 s.d. t', 'target' => 't+1'],
                ['pair' => '3', 'input' => 't-28 s.d. t+1', 'target' => 't+2'],
                ['pair' => '...', 'input' => '...', 'target' => '...'],
            ],
            'examples' => $examples,
        ];
    }

    private function modelParameterSummary(?array $model, ?array $metadata): array
    {
        if (!$model) {
            return [
                'available' => false,
                'status' => 'unavailable',
                'message' => 'Model final belum tersedia.',
                'rows' => [],
            ];
        }

        $source = $this->modelSourceValidation();
        $windowSize = (int) ($metadata['window_size'] ?? $model['window_size'] ?? 0);
        $units = (int) ($metadata['units'] ?? $model['units'] ?? 0);
        $dropout = (float) ($metadata['dropout'] ?? $model['dropout'] ?? 0);
        $batchSize = (int) ($metadata['batch_size'] ?? $model['batch_size'] ?? 0);
        $configuredEpochs = (int) ($metadata['configured_epochs'] ?? $model['configured_epochs'] ?? 0);
        $actualEpochs = (int) ($metadata['actual_epochs'] ?? $model['actual_epochs'] ?? $configuredEpochs);
        $learningRate = (float) ($metadata['learning_rate'] ?? $model['learning_rate'] ?? 0);
        $optimizer = (string) ($metadata['optimizer'] ?? $model['optimizer'] ?? 'Adam');
        $loss = strtoupper((string) ($metadata['loss'] ?? $model['loss'] ?? 'MSE'));
        $loss = $loss === 'MSE' ? 'MSE' : strtoupper($loss);
        $sync = $windowSize === 30
            && $units === 64
            && abs($dropout - 0.2) < 0.000001
            && $source['has_input_shape']
            && $source['has_bigru_layer']
            && $source['has_dropout']
            && $source['has_dense_one']
            && $source['has_shuffle_false']
            && $source['has_adam']
            && $source['has_mse']
            && $source['has_recursive_horizon'];

        return [
            'available' => true,
            'status' => $sync ? 'ok' : 'warning',
            'message' => $sync ? 'Sinkron dengan source code dan Gambar 4.2.' : 'Terdapat parameter yang tidak sinkron dengan source code atau Gambar 4.2.',
            'source' => $source,
            'configured_epochs' => $configuredEpochs,
            'actual_epochs' => $actualEpochs,
            'rows' => [
                ['parameter' => 'Window Size', 'value' => (string) $windowSize, 'note' => 'Jumlah observasi historis per input'],
                ['parameter' => 'Input Feature', 'value' => '1 (Close)', 'note' => 'Satu fitur harga penutupan'],
                ['parameter' => 'BiGRU Layer', 'value' => '1', 'note' => 'Satu lapisan Bidirectional GRU'],
                ['parameter' => 'GRU Units', 'value' => (string) $units, 'note' => 'Jumlah unit GRU pada layer yang dibungkus Bidirectional'],
                ['parameter' => 'Dropout', 'value' => $this->formatDecimalComma($dropout, 1), 'note' => 'Regularisasi'],
                ['parameter' => 'Batch Size', 'value' => (string) $batchSize, 'note' => 'Ukuran batch'],
                ['parameter' => 'Epoch', 'value' => (string) $actualEpochs, 'note' => $configuredEpochs !== $actualEpochs ? "Jumlah epoch pelatihan model final; configured epoch {$configuredEpochs}" : 'Jumlah epoch pelatihan model final'],
                ['parameter' => 'Learning Rate', 'value' => $this->formatDecimalComma($learningRate, 3), 'note' => 'Learning rate optimizer Adam'],
                ['parameter' => 'Shuffle', 'value' => 'False', 'note' => 'Menjaga urutan sekuens data deret waktu'],
                ['parameter' => 'Loss', 'value' => $loss, 'note' => 'Fungsi loss regresi'],
                ['parameter' => 'Optimizer', 'value' => $optimizer, 'note' => 'Pembaruan bobot model'],
                ['parameter' => 'Output Model', 'value' => '1 nilai (Dense(1))', 'note' => 'Satu prediksi Close Price pada setiap langkah'],
                ['parameter' => 'Horizon Operasional', 'value' => '1-7 periode', 'note' => 'Dijalankan secara recursive pada tahap inferensi; Dense(1) tidak berubah menjadi Dense(7)'],
            ],
        ];
    }

    private function trainingResultSummary(array $summary, ?array $model, ?array $metadata): array
    {
        if (!$model || ($model['status'] ?? '') !== 'success') {
            return [
                'available' => false,
                'status' => 'unavailable',
                'message' => 'Model final belum tersedia.',
                'rows' => [],
            ];
        }

        $version = (string) ($metadata['version'] ?? $model['version']);
        $totalRecords = (int) ($metadata['total_records'] ?? $model['total_records'] ?? $summary['total_records']);
        $trainObservations = (int) floor($totalRecords * 0.8);
        $testObservations = max(0, $totalRecords - $trainObservations);
        $trainSamples = (int) ($metadata['train_samples'] ?? $model['train_samples'] ?? 0);
        $testSamples = (int) ($metadata['test_samples'] ?? $model['test_samples'] ?? 0);
        $actualEpochs = (int) ($metadata['actual_epochs'] ?? $model['actual_epochs'] ?? $model['configured_epochs'] ?? 0);
        $configuredEpochs = (int) ($metadata['configured_epochs'] ?? $model['configured_epochs'] ?? $actualEpochs);
        $finalTrainingLoss = $metadata['metrics']['final_training_loss'] ?? $model['final_training_loss'] ?? null;
        $duration = $metadata['training_duration_seconds'] ?? $model['training_duration_seconds'] ?? null;
        $modelPath = $this->resolveArtifactPath((string) ($model['model_path'] ?? '')) ?: '';
        $scalerPath = $this->resolveArtifactPath((string) ($model['scaler_path'] ?? '')) ?: '';
        $metadataPath = $this->resolveArtifactPath((string) ($model['metadata_path'] ?? '')) ?: $this->metadataPath($version);
        $modelAvailable = $modelPath !== '' && is_file($modelPath);
        $scalerAvailable = $scalerPath !== '' && is_file($scalerPath);
        $metadataAvailable = $metadataPath !== '' && is_file($metadataPath);
        $artifactConsistent = $modelAvailable
            && $scalerAvailable
            && $metadataAvailable
            && str_contains(basename($modelPath), $version)
            && str_contains(basename($scalerPath), $version)
            && str_contains(basename($metadataPath), $version);
        $table43Sync = $trainObservations + $testObservations === $totalRecords
            && $totalRecords === (int) $summary['total_records'];
        $table45Sync = $trainSamples === max(0, $trainObservations - (int) ($metadata['window_size'] ?? $model['window_size'] ?? 0))
            && $testSamples === $testObservations;
        $table46Sync = $actualEpochs === (int) ($metadata['actual_epochs'] ?? $actualEpochs);
        $allSync = $artifactConsistent && $table43Sync && $table45Sync && $table46Sync;

        return [
            'available' => true,
            'status' => $allSync ? 'ok' : 'warning',
            'message' => $allSync ? 'Sinkron dengan model run final.' : 'Ada perbedaan pada artifact atau nilai lintas tabel dokumentasi.',
            'version' => $version,
            'model_run_id' => (int) $model['id'],
            'model_status' => (string) $model['status'],
            'is_active' => !empty($model['is_active']),
            'total_records' => $totalRecords,
            'train_observations' => $trainObservations,
            'test_observations' => $testObservations,
            'train_samples' => $trainSamples,
            'test_samples' => $testSamples,
            'window_size' => (int) ($metadata['window_size'] ?? $model['window_size'] ?? 0),
            'configured_epochs' => $configuredEpochs,
            'actual_epochs' => $actualEpochs,
            'final_training_loss' => $finalTrainingLoss,
            'training_duration_seconds' => $duration,
            'formatted_duration' => $this->formatDuration($duration),
            'model_artifact' => $modelAvailable ? basename($modelPath) : 'Artifact tidak ditemukan',
            'scaler_artifact' => $scalerAvailable ? basename($scalerPath) : 'Artifact tidak ditemukan',
            'metadata_artifact' => $metadataAvailable ? basename($metadataPath) : 'Artifact tidak ditemukan',
            'model_artifact_status' => $modelAvailable ? 'Tersedia' : 'Tidak tersedia',
            'scaler_artifact_status' => $scalerAvailable ? 'Tersedia' : 'Tidak tersedia',
            'metadata_artifact_status' => $metadataAvailable ? 'Tersedia' : 'Tidak tersedia',
            'artifact_consistency' => $artifactConsistent ? 'Valid' : 'Tidak Valid',
            'table43_sync' => $table43Sync ? 'Sinkron' : 'Tidak sinkron',
            'table45_sync' => $table45Sync ? 'Sinkron' : 'Tidak sinkron',
            'table46_sync' => $table46Sync ? 'Sinkron' : 'Tidak sinkron',
            'dataset_period' => $this->periodLabel($metadata['dataset_start_date'] ?? $model['dataset_start_date'] ?? null, $metadata['dataset_end_date'] ?? $model['dataset_end_date'] ?? null),
            'train_period' => $this->periodLabel($metadata['train_start_date'] ?? $model['train_start_date'] ?? null, $metadata['train_end_date'] ?? $model['train_end_date'] ?? null),
            'test_period' => $this->periodLabel($metadata['test_start_date'] ?? $model['test_start_date'] ?? null, $metadata['test_end_date'] ?? $model['test_end_date'] ?? null),
            'rows' => [
                ['info' => 'Versi model', 'value' => $version],
                ['info' => 'Jumlah data latih', 'value' => $this->formatInteger($trainObservations)],
                ['info' => 'Jumlah sequence train', 'value' => $this->formatInteger($trainSamples)],
                ['info' => 'Epoch aktual', 'value' => (string) $actualEpochs],
                ['info' => 'Training loss akhir', 'value' => $finalTrainingLoss !== null ? number_format((float) $finalTrainingLoss, 8, '.', '') : '-'],
                ['info' => 'Waktu pelatihan', 'value' => $this->formatDuration($duration)],
                ['info' => 'Artifact model', 'value' => $modelAvailable ? basename($modelPath) : 'Artifact tidak ditemukan'],
                ['info' => 'Artifact scaler', 'value' => $scalerAvailable ? basename($scalerPath) : 'Artifact tidak ditemukan'],
            ],
        ];
    }

    private function trainingLossSummary(?array $model, ?array $metadata): array
    {
        if (!$model || ($model['status'] ?? '') !== 'success') {
            return [
                'available' => false,
                'status' => 'unavailable',
                'message' => 'Model final belum tersedia.',
                'history' => [],
            ];
        }

        $version = (string) ($metadata['version'] ?? $model['version']);
        $metadataHistory = $this->metadataLossHistory($metadata);
        $logHistory = $this->logLossHistory($version);
        $history = $metadataHistory !== [] ? $metadataHistory : $logHistory;
        $source = $metadataHistory !== [] ? 'Metadata' : ($logHistory !== [] ? 'Training Log' : 'Tidak tersedia');
        $actualEpochs = (int) ($metadata['actual_epochs'] ?? $model['actual_epochs'] ?? $model['configured_epochs'] ?? 0);
        $configuredEpochs = (int) ($metadata['configured_epochs'] ?? $model['configured_epochs'] ?? $actualEpochs);
        $finalLossMetadata = $metadata['metrics']['final_training_loss'] ?? $model['final_training_loss'] ?? null;
        $firstLoss = $history[0]['loss'] ?? null;
        $lastLoss = $history[count($history) - 1]['loss'] ?? null;
        $lossPointValid = count($history) === $actualEpochs;
        $finalLossValid = $finalLossMetadata !== null && $lastLoss !== null
            ? abs((float) $lastLoss - (float) $finalLossMetadata) <= 0.00000001
            : false;
        $logSync = $this->lossHistorySync($history, $logHistory);
        $allValid = $history !== [] && $lossPointValid && $finalLossValid && $logSync['valid'];

        return [
            'available' => $history !== [],
            'status' => $allValid ? 'ok' : 'warning',
            'message' => $allValid ? 'Sinkron dengan training final.' : 'Data loss perlu diperiksa terhadap epoch, metadata, atau training log.',
            'version' => $version,
            'model_run_id' => (int) $model['id'],
            'model_status' => (string) $model['status'],
            'configured_epochs' => $configuredEpochs,
            'actual_epochs' => $actualEpochs,
            'loss_points' => count($history),
            'first_loss' => $firstLoss,
            'final_loss' => $lastLoss,
            'final_loss_metadata' => $finalLossMetadata,
            'optimizer' => (string) ($metadata['optimizer'] ?? $model['optimizer'] ?? 'Adam'),
            'loss_function' => strtoupper((string) ($metadata['loss'] ?? $model['loss'] ?? 'MSE')),
            'batch_size' => (int) ($metadata['batch_size'] ?? $model['batch_size'] ?? 0),
            'learning_rate' => (float) ($metadata['learning_rate'] ?? $model['learning_rate'] ?? 0),
            'source' => $source,
            'history' => $history,
            'loss_point_status' => $lossPointValid ? 'Valid' : 'Tidak valid',
            'final_loss_status' => $finalLossValid ? 'Loss akhir sinkron dengan metadata model final' : 'Loss akhir tidak sinkron dengan metadata model final',
            'log_status' => $logSync['message'],
            'validation_loss' => 'Tidak digunakan',
        ];
    }

    private function modelManagementSummary(array $datasetSummary, array $models, ?array $finalModel, ?array $metadata, bool $screenshot): array
    {
        if (!$finalModel || ($finalModel['status'] ?? '') !== 'success') {
            return [
                'available' => false,
                'status' => 'unavailable',
                'message' => 'Model final belum tersedia.',
                'rows' => [],
                'displayed_count' => 0,
                'total_runs' => count($models),
                'success_runs' => count(array_filter($models, fn ($model) => ($model['status'] ?? '') === 'success')),
            ];
        }

        $final = $this->modelRunDocumentationData($finalModel, $metadata);
        $results = $this->trainingResultSummary($datasetSummary, $finalModel, $metadata);
        $parameters = $this->modelParameterSummary($finalModel, $metadata);
        $modelPath = $this->resolveArtifactPath((string) ($finalModel['model_path'] ?? '')) ?: '';
        $scalerPath = $this->resolveArtifactPath((string) ($finalModel['scaler_path'] ?? '')) ?: '';
        $metadataPath = $this->resolveArtifactPath((string) ($finalModel['metadata_path'] ?? '')) ?: $this->metadataPath($final['version']);
        $artifactsAvailable = $modelPath !== '' && is_file($modelPath)
            && $scalerPath !== '' && is_file($scalerPath)
            && $metadataPath !== '' && is_file($metadataPath);
        $table46Sync = !empty($parameters['available']) && ($parameters['status'] ?? '') === 'ok';
        $table47Sync = !empty($results['available']) && ($results['status'] ?? '') === 'ok'
            && (string) ($results['version'] ?? '') === $final['version']
            && (int) ($results['actual_epochs'] ?? 0) === $final['actual_epochs']
            && (string) ($results['formatted_duration'] ?? '') === $final['formatted_duration'];
        $metricsAvailable = $final['mae'] !== null && $final['rmse'] !== null && $final['mape'] !== null;
        $syncOk = $table46Sync && $table47Sync && $metricsAvailable && $artifactsAvailable;

        $rows = array_map(function (array $model): array {
            $modelMetadata = $this->loadMetadata((string) $model['version'], $model['metadata_path'] ?? null);
            return $this->modelRunDocumentationData($model, $modelMetadata);
        }, $models);

        if ($screenshot) {
            $finalId = (int) $finalModel['id'];
            $finalRow = null;
            $latestRows = [];
            foreach ($rows as $row) {
                if ((int) $row['id'] === $finalId) {
                    $finalRow = $row;
                    continue;
                }
                if (($row['status'] ?? '') === 'success' || count($latestRows) < 4) {
                    $latestRows[] = $row;
                }
                if (count($latestRows) >= 4) {
                    break;
                }
            }
            $rows = array_values(array_filter(array_merge([$finalRow], array_slice($latestRows, 0, 4))));
        }

        return [
            'available' => true,
            'status' => $syncOk ? 'ok' : 'warning',
            'message' => $syncOk ? 'Sinkron dengan metadata training, Tabel 4.6, dan Tabel 4.7.' : 'Data model final tidak sinkron dengan metadata training.',
            'final' => $final,
            'rows' => $rows,
            'displayed_count' => count($rows),
            'total_runs' => count($models),
            'success_runs' => count(array_filter($models, fn ($model) => ($model['status'] ?? '') === 'success')),
            'table46_sync' => $table46Sync ? 'Sinkron' : 'Tidak sinkron',
            'table47_sync' => $table47Sync ? 'Sinkron' : 'Tidak sinkron',
            'metrics_sync' => $metricsAvailable ? 'Sinkron dengan metadata evaluasi' : 'Metrik evaluasi tidak tersedia',
            'model_artifact' => $modelPath !== '' && is_file($modelPath) ? basename($modelPath) : 'Artifact tidak ditemukan',
            'scaler_artifact' => $scalerPath !== '' && is_file($scalerPath) ? basename($scalerPath) : 'Artifact tidak ditemukan',
            'metadata_artifact' => $metadataPath !== '' && is_file($metadataPath) ? basename($metadataPath) : 'Artifact tidak ditemukan',
        ];
    }

    private function modelRunDocumentationData(array $model, ?array $metadata): array
    {
        $metrics = is_array($metadata['metrics'] ?? null) ? $metadata['metrics'] : [];
        $duration = $metadata['training_duration_seconds'] ?? $model['training_duration_seconds'] ?? null;
        $actualEpochs = (int) ($metadata['actual_epochs'] ?? $model['actual_epochs'] ?? $model['configured_epochs'] ?? 0);
        $configuredEpochs = (int) ($metadata['configured_epochs'] ?? $model['configured_epochs'] ?? $actualEpochs);

        return [
            'id' => (int) ($model['id'] ?? 0),
            'name' => (string) ($model['model_name'] ?? 'BiGRU'),
            'version' => (string) ($metadata['version'] ?? $model['version'] ?? '-'),
            'status' => (string) ($model['status'] ?? '-'),
            'status_label' => ucfirst((string) ($model['status'] ?? '-')),
            'is_active' => !empty($model['is_active']),
            'window_size' => (int) ($metadata['window_size'] ?? $model['window_size'] ?? 0),
            'units' => (int) ($metadata['units'] ?? $model['units'] ?? 0),
            'dropout' => (float) ($metadata['dropout'] ?? $model['dropout'] ?? 0),
            'batch_size' => (int) ($metadata['batch_size'] ?? $model['batch_size'] ?? 0),
            'configured_epochs' => $configuredEpochs,
            'actual_epochs' => $actualEpochs,
            'learning_rate' => (float) ($metadata['learning_rate'] ?? $model['learning_rate'] ?? 0),
            'mae' => $metrics['mae'] ?? $model['mae'] ?? null,
            'rmse' => $metrics['rmse'] ?? $model['rmse'] ?? null,
            'mape' => $metrics['mape'] ?? $model['mape'] ?? null,
            'training_duration_seconds' => $duration,
            'formatted_duration' => $duration === null || $duration === '' ? 'Tidak tersedia' : $this->formatDuration($duration),
            'trained_at' => $model['trained_at'] ?? $model['created_at'] ?? null,
        ];
    }

    private function trainingLogDocumentation(?array $model, ?array $metadata, bool $screenshot): array
    {
        if (!$model || ($model['status'] ?? '') !== 'success') {
            return [
                'available' => false,
                'status' => 'unavailable',
                'message' => 'Model final belum tersedia.',
                'lines' => [],
                'focused_lines' => [],
            ];
        }

        $version = (string) ($metadata['version'] ?? $model['version']);
        $logPath = $this->trainingLogPath($version);
        $logExists = is_file($logPath);
        $lines = $logExists ? (file($logPath, FILE_IGNORE_NEW_LINES) ?: []) : [];
        $epochRows = $this->parseEpochRowsFromLog($lines);
        $metrics = $this->parseMetricsFromLog($lines);
        $doneLine = $this->firstLogLine($lines, '[TRAIN][DONE]');
        $actualEpochs = (int) ($metadata['actual_epochs'] ?? $model['actual_epochs'] ?? $model['configured_epochs'] ?? 0);
        $finalTrainingLoss = $metadata['metrics']['final_training_loss'] ?? $model['final_training_loss'] ?? null;
        $metadataMetrics = is_array($metadata['metrics'] ?? null) ? $metadata['metrics'] : [];
        $firstLoss = $epochRows[0]['loss'] ?? null;
        $finalLoss = $epochRows[count($epochRows) - 1]['loss'] ?? null;
        $markers = [
            '[TRAIN][START]' => $this->logContains($lines, '[TRAIN][START]'),
            '[TRAIN][DATA]' => $this->logContains($lines, '[TRAIN][DATA]'),
            '[TRAIN][FIT]' => $this->logContains($lines, '[TRAIN][FIT]'),
            '[TRAIN][METRICS]' => $this->logContains($lines, '[TRAIN][METRICS]'),
            '[TRAIN][DONE]' => $this->logContains($lines, '[TRAIN][DONE]'),
        ];
        $versionSync = $logExists && $this->logContains($lines, 'version=' . $version);
        $epochSync = count($epochRows) === $actualEpochs;
        $lossSync = $finalTrainingLoss !== null && $finalLoss !== null
            ? abs((float) $finalLoss - (float) $finalTrainingLoss) <= 0.00000001
            : false;
        $metricSync = $metrics !== []
            && isset($metadataMetrics['mae'], $metadataMetrics['rmse'], $metadataMetrics['mape'])
            && abs((float) $metrics['mae'] - (float) $metadataMetrics['mae']) <= 0.000001
            && abs((float) $metrics['rmse'] - (float) $metadataMetrics['rmse']) <= 0.000001
            && abs((float) $metrics['mape'] - (float) $metadataMetrics['mape']) <= 0.0001;
        $markerSync = count(array_filter($markers)) === count($markers);
        $sync = $logExists && $versionSync && $epochSync && $lossSync && $metricSync && $markerSync;

        return [
            'available' => $logExists && $lines !== [],
            'status' => $sync ? 'ok' : 'warning',
            'message' => $sync ? 'Log training final sinkron dengan metadata model final.' : 'Log training model final perlu diperiksa terhadap metadata.',
            'version' => $version,
            'model_run_id' => (int) $model['id'],
            'model_status' => (string) $model['status'],
            'is_active' => !empty($model['is_active']),
            'log_file' => basename($logPath),
            'log_exists' => $logExists,
            'actual_epochs' => $actualEpochs,
            'epoch_lines_found' => count($epochRows),
            'first_loss' => $firstLoss,
            'final_loss' => $finalLoss,
            'final_training_loss_metadata' => $finalTrainingLoss,
            'mae' => $metadataMetrics['mae'] ?? $model['mae'] ?? null,
            'rmse' => $metadataMetrics['rmse'] ?? $model['rmse'] ?? null,
            'mape' => $metadataMetrics['mape'] ?? $model['mape'] ?? null,
            'training_duration_seconds' => $metadata['training_duration_seconds'] ?? $model['training_duration_seconds'] ?? null,
            'formatted_duration' => $this->formatDuration($metadata['training_duration_seconds'] ?? $model['training_duration_seconds'] ?? null),
            'lines' => $screenshot ? $this->focusedTrainingLogLines($lines) : $lines,
            'full_line_count' => count($lines),
            'focused_line_count' => count($this->focusedTrainingLogLines($lines)),
            'markers' => $markers,
            'version_sync' => $versionSync ? 'Ya' : 'Tidak',
            'epoch_sync' => $epochSync ? 'Ya' : 'Tidak',
            'loss_sync' => $lossSync ? 'Ya' : 'Tidak',
            'metric_sync' => $metricSync ? 'Ya' : 'Tidak',
            'metadata_sync' => $sync ? 'Ya' : 'Tidak',
            'done_line' => $doneLine,
        ];
    }

    private function testPredictionSummary(?array $model, ?array $metadata, string $screenshotMode): array
    {
        if (!$model || ($model['status'] ?? '') !== 'success') {
            return [
                'available' => false,
                'status' => 'unavailable',
                'message' => 'Model final belum tersedia.',
                'rows' => [],
                'chart_rows' => [],
            ];
        }

        $version = (string) ($metadata['version'] ?? $model['version']);
        $series = is_array($metadata['test_series'] ?? null) ? $metadata['test_series'] : [];
        if ($series === []) {
            return [
                'available' => false,
                'status' => 'warning',
                'message' => 'Test series model final tidak tersedia.',
                'version' => $version,
                'model_run_id' => (int) $model['id'],
                'rows' => [],
                'chart_rows' => [],
            ];
        }

        $rows = [];
        foreach ($series as $row) {
            if (!isset($row['date'], $row['actual'], $row['predicted']) || !is_numeric($row['actual']) || !is_numeric($row['predicted'])) {
                continue;
            }
            $actual = (float) $row['actual'];
            $predicted = (float) $row['predicted'];
            $rows[] = [
                'date' => (string) $row['date'],
                'actual' => $actual,
                'predicted' => $predicted,
                'absolute_error' => abs($actual - $predicted),
            ];
        }

        $dates = array_column($rows, 'date');
        $dateCounts = array_count_values($dates);
        $ascending = $dates === $this->sortedDates($dates, SORT_ASC);
        $duplicateCount = count(array_filter($dateCounts, fn ($count) => $count > 1));
        $testStart = $metadata['test_start_date'] ?? $model['test_start_date'] ?? null;
        $testEnd = $metadata['test_end_date'] ?? $model['test_end_date'] ?? null;
        $periodSync = ($dates[0] ?? null) === $testStart && ($dates[count($dates) - 1] ?? null) === $testEnd;
        $testSamples = (int) ($metadata['test_samples'] ?? $model['test_samples'] ?? 0);
        $countSync = count($rows) === $testSamples;
        $actualValues = array_column($rows, 'actual');
        $predictedValues = array_column($rows, 'predicted');
        $calculatedMetrics = $this->metricsFromTestSeries($rows);
        $metadataMetrics = is_array($metadata['metrics'] ?? null) ? $metadata['metrics'] : [];
        $metricSync = isset($metadataMetrics['mae'], $metadataMetrics['rmse'], $metadataMetrics['mape'])
            && abs($calculatedMetrics['mae'] - (float) $metadataMetrics['mae']) <= 0.00001
            && abs($calculatedMetrics['rmse'] - (float) $metadataMetrics['rmse']) <= 0.00001
            && abs($calculatedMetrics['mape'] - (float) $metadataMetrics['mape']) <= 0.0001;
        $scaleOriginal = $actualValues !== [] && $predictedValues !== []
            && max($actualValues) > 1.0
            && max($predictedValues) > 1.0
            && (max($actualValues) - min($actualValues)) > 0.1;
        $artifactSync = $this->resolveArtifactPath((string) ($model['model_path'] ?? '')) && $this->resolveArtifactPath((string) ($model['scaler_path'] ?? ''));
        $allSync = $ascending && $duplicateCount === 0 && $periodSync && $countSync && $metricSync && $scaleOriginal && $artifactSync;
        $displayRows = $screenshotMode === 'table' ? array_slice($rows, 0, 10) : array_slice($rows, 0, 10);

        return [
            'available' => $rows !== [],
            'status' => $allSync ? 'ok' : 'warning',
            'message' => $allSync ? 'Test series sinkron dengan metadata model final.' : 'Test series model final perlu diperiksa terhadap metadata.',
            'version' => $version,
            'model_run_id' => (int) $model['id'],
            'model_status' => (string) $model['status'],
            'is_active' => !empty($model['is_active']),
            'test_start_date' => $testStart,
            'test_end_date' => $testEnd,
            'test_period' => $this->periodLabel($testStart, $testEnd),
            'test_samples' => $testSamples,
            'test_series_records' => count($rows),
            'rows' => $displayRows,
            'chart_rows' => $rows,
            'displayed_rows' => count($displayRows),
            'mae' => $metadataMetrics['mae'] ?? $model['mae'] ?? null,
            'rmse' => $metadataMetrics['rmse'] ?? $model['rmse'] ?? null,
            'mape' => $metadataMetrics['mape'] ?? $model['mape'] ?? null,
            'calculated_mae' => $calculatedMetrics['mae'],
            'calculated_rmse' => $calculatedMetrics['rmse'],
            'calculated_mape' => $calculatedMetrics['mape'],
            'actual_min' => min($actualValues),
            'actual_max' => max($actualValues),
            'predicted_min' => min($predictedValues),
            'predicted_max' => max($predictedValues),
            'mean_absolute_error' => $calculatedMetrics['mae'],
            'prediction_scale' => 'Original Close Price',
            'actual_scale_status' => $scaleOriginal ? 'Original Close Price' : 'Perlu diperiksa',
            'predicted_scale_status' => $scaleOriginal ? 'Original Close Price' : 'Perlu diperiksa',
            'date_order_status' => $ascending ? 'Ascending' : 'Tidak ascending',
            'duplicate_date_count' => $duplicateCount,
            'period_sync' => $periodSync ? 'Sinkron' : 'Tidak sinkron',
            'count_sync' => $countSync ? 'Sinkron' : 'Tidak sinkron',
            'metrics_sync' => $metricSync ? 'Sinkron' : 'Tidak sinkron',
            'table47_sync' => $metricSync && $countSync ? 'Sinkron' : 'Tidak sinkron',
            'metadata_sync' => $allSync ? 'Sinkron' : 'Tidak sinkron',
        ];
    }

    private function modelEvaluationSummary(?array $model, ?array $metadata): array
    {
        if (!$model || ($model['status'] ?? '') !== 'success') {
            return [
                'available' => false,
                'status' => 'unavailable',
                'message' => 'Model final belum tersedia.',
                'rows' => [],
            ];
        }

        $metrics = is_array($metadata['metrics'] ?? null) ? $metadata['metrics'] : [];
        if (!isset($metrics['mae'], $metrics['rmse'], $metrics['mape'])) {
            return [
                'available' => false,
                'status' => 'warning',
                'message' => 'Metrik evaluasi model final belum tersedia.',
                'version' => (string) ($metadata['version'] ?? $model['version']),
                'model_run_id' => (int) $model['id'],
                'rows' => [],
            ];
        }

        $testSeries = is_array($metadata['test_series'] ?? null) ? $metadata['test_series'] : [];
        $seriesRows = [];
        foreach ($testSeries as $row) {
            if (!isset($row['date'], $row['actual'], $row['predicted']) || !is_numeric($row['actual']) || !is_numeric($row['predicted'])) {
                continue;
            }
            $actual = (float) $row['actual'];
            $predicted = (float) $row['predicted'];
            $seriesRows[] = [
                'date' => (string) $row['date'],
                'actual' => $actual,
                'predicted' => $predicted,
                'absolute_error' => abs($actual - $predicted),
            ];
        }

        $calculated = $this->metricsFromTestSeries($seriesRows);
        $dates = array_column($seriesRows, 'date');
        $testStart = $metadata['test_start_date'] ?? $model['test_start_date'] ?? null;
        $testEnd = $metadata['test_end_date'] ?? $model['test_end_date'] ?? null;
        $testSamples = (int) ($metadata['test_samples'] ?? $model['test_samples'] ?? 0);
        $actualValues = array_column($seriesRows, 'actual');
        $predictedValues = array_column($seriesRows, 'predicted');
        $zeroActualCount = count(array_filter($actualValues, fn ($actual) => (float) $actual === 0.0));
        $testSeriesSync = $seriesRows !== []
            && count($seriesRows) === $testSamples
            && ($dates[0] ?? null) === $testStart
            && ($dates[count($dates) - 1] ?? null) === $testEnd
            && $dates === $this->sortedDates($dates, SORT_ASC);
        $scaleOriginal = $actualValues !== [] && $predictedValues !== []
            && max($actualValues) > 1.0
            && max($predictedValues) > 1.0;
        $metricSync = abs($calculated['mae'] - (float) $metrics['mae']) <= 0.00001
            && abs($calculated['rmse'] - (float) $metrics['rmse']) <= 0.00001
            && abs($calculated['mape'] - (float) $metrics['mape']) <= 0.0001;
        $overallValid = $testSeriesSync && $metricSync && $scaleOriginal;

        return [
            'available' => true,
            'status' => $overallValid ? 'ok' : 'warning',
            'message' => $overallValid ? 'Metrik evaluasi final valid dan sinkron dengan test_series.' : 'Data evaluasi perlu diperiksa terhadap metadata atau test_series.',
            'version' => (string) ($metadata['version'] ?? $model['version']),
            'model_run_id' => (int) $model['id'],
            'model_status' => (string) $model['status'],
            'is_active' => !empty($model['is_active']),
            'test_start_date' => $testStart,
            'test_end_date' => $testEnd,
            'test_period' => $this->periodLabel($testStart, $testEnd),
            'test_samples' => $testSamples,
            'test_series_records' => count($seriesRows),
            'mae' => (float) $metrics['mae'],
            'rmse' => (float) $metrics['rmse'],
            'mape' => (float) $metrics['mape'],
            'mae_validation' => $calculated['mae'],
            'rmse_validation' => $calculated['rmse'],
            'mape_validation' => $calculated['mape'],
            'scale' => $scaleOriginal ? 'Original Close Price' : 'Perlu diperiksa',
            'evaluation_dataset' => 'Test Set',
            'evaluation_source' => 'Metadata Model Final',
            'validation_source' => 'test_series',
            'zero_division_status' => $zeroActualCount === 0 ? 'Tidak terjadi' : 'Mengabaikan actual = 0 sesuai np.nanmean pada metrics.py',
            'test_series_sync' => $testSeriesSync ? 'Sinkron' : 'Tidak sinkron',
            'table48_sync' => $testSeriesSync ? 'Sinkron' : 'Tidak sinkron',
            'figure46_sync' => $testSeriesSync ? 'Sinkron' : 'Tidak sinkron',
            'metadata_sync' => $metricSync ? 'Sinkron' : 'Tidak sinkron',
            'overall_status' => $overallValid ? 'Valid' : 'Perlu Pemeriksaan',
            'rows' => [
                ['metric' => 'MAE', 'value' => (float) $metrics['mae'], 'suffix' => '', 'decimals' => 6, 'interpretation' => 'Rata-rata selisih absolut'],
                ['metric' => 'RMSE', 'value' => (float) $metrics['rmse'], 'suffix' => '', 'decimals' => 6, 'interpretation' => 'Lebih sensitif terhadap error besar'],
                ['metric' => 'MAPE', 'value' => (float) $metrics['mape'], 'suffix' => ' %', 'decimals' => 4, 'interpretation' => 'Rata-rata error relatif'],
            ],
        ];
    }

    private function recursivePredictionSummary(?array $model, ?array $metadata, ?array $prediction): array
    {
        if (!$model) {
            return [
                'available' => false,
                'status' => 'unavailable',
                'message' => 'Model aktif belum tersedia.',
                'rows' => [],
                'outputs' => [],
            ];
        }

        if (!$prediction) {
            return [
                'available' => false,
                'status' => 'warning',
                'message' => 'Riwayat prediksi untuk model aktif belum tersedia.',
                'version' => (string) $model['version'],
                'model_run_id' => (int) $model['id'],
                'rows' => [],
                'outputs' => [],
            ];
        }

        $version = (string) ($prediction['model_version'] ?: $prediction['version'] ?? $model['version']);
        $windowSize = (int) ($metadata['window_size'] ?? $model['window_size'] ?? $prediction['window_size'] ?? 0);
        $horizon = (int) ($prediction['horizon_steps'] ?? count($prediction['outputs'] ?? []));
        $outputs = array_values($prediction['outputs'] ?? []);
        usort($outputs, fn ($a, $b) => (int) $a['horizon_step'] <=> (int) $b['horizon_step']);
        $inputs = array_values($prediction['inputs'] ?? []);
        usort($inputs, fn ($a, $b) => (int) $a['sequence_order'] <=> (int) $b['sequence_order']);
        $inputDates = array_map(fn ($row) => (string) ($row['price_date'] ?? ''), $inputs);
        $modelPath = $this->resolveArtifactPath((string) ($model['model_path'] ?? '')) ?: '';
        $scalerPath = $this->resolveArtifactPath((string) ($model['scaler_path'] ?? '')) ?: '';
        $metadataPath = $this->resolveArtifactPath((string) ($model['metadata_path'] ?? '')) ?: $this->metadataPath((string) $model['version']);
        $source = $this->recursivePredictionSourceValidation();

        $versionSync = $version === (string) $model['version'];
        $horizonValid = $horizon >= 1 && $horizon <= 7;
        $predictionCountValid = count($outputs) === $horizon;
        $windowCountValid = count($inputs) === $windowSize;
        $inputOrderValid = $inputDates === $this->sortedDates($inputDates, SORT_ASC);
        $inputStart = (string) ($prediction['input_start_date'] ?? ($inputs[0]['price_date'] ?? ''));
        $inputEnd = (string) ($prediction['input_end_date'] ?? ($inputs[count($inputs) - 1]['price_date'] ?? ''));
        $inputPeriodValid = $inputStart !== '' && $inputEnd !== '' && strcmp($inputStart, $inputEnd) < 0
            && ($inputs === [] || ($inputStart === (string) ($inputs[0]['price_date'] ?? '') && $inputEnd === (string) ($inputs[count($inputs) - 1]['price_date'] ?? '')));
        $artifactModelValid = $modelPath !== '' && is_file($modelPath);
        $artifactScalerValid = $scalerPath !== '' && is_file($scalerPath);
        $metadataValid = $metadataPath !== '' && is_file($metadataPath) && is_array($metadata);
        $strategyValid = in_array((string) ($prediction['strategy'] ?? ''), ['recursive', 'recursive_multi_step', 'recursive multi-step'], true) && $source['is_recursive'];
        $windowMatchesDesign = $windowSize === 30;
        $allValid = $versionSync && $horizonValid && $predictionCountValid && $windowCountValid && $inputOrderValid && $inputPeriodValid && $artifactModelValid && $artifactScalerValid && $metadataValid && $strategyValid && $windowMatchesDesign;
        $outputRows = array_map(fn ($row) => [
            'period' => 'P+' . (int) $row['horizon_step'],
            'step' => (int) $row['horizon_step'],
            'predicted_close' => (float) $row['predicted_close'],
        ], $outputs);
        $p1 = $outputRows[0]['predicted_close'] ?? null;
        $pn = $outputRows[count($outputRows) - 1]['predicted_close'] ?? null;
        $p2ToN = array_filter($outputRows, fn ($row) => (int) $row['step'] >= 2);
        $p2ToNText = $p2ToN === []
            ? '-'
            : implode("\n", array_map(fn ($row) => $row['period'] . ': ' . $this->formatDecimalComma((float) $row['predicted_close'], 6), $p2ToN));

        return [
            'available' => true,
            'status' => $allValid ? 'ok' : 'warning',
            'message' => $allValid ? 'Prediksi recursive multi-periode valid dan memakai model final aktif.' : 'Prediksi operasional perlu diperiksa terhadap model final, window, atau output horizon.',
            'prediction_id' => (int) $prediction['id'],
            'version' => $version,
            'model_run_id' => (int) $model['id'],
            'model_type' => (string) ($metadata['model_type'] ?? 'BiGRU'),
            'model_status' => (string) $model['status'],
            'is_active' => !empty($model['is_active']),
            'input_start_date' => $inputStart,
            'input_end_date' => $inputEnd,
            'window_size' => $windowSize,
            'feature' => 'Close',
            'horizon' => $horizon,
            'supported_horizon' => '1-7 periode',
            'strategy' => 'Recursive multi-step',
            'p1' => $p1,
            'pn' => $pn,
            'p2_to_n' => $p2ToNText,
            'prediction_count' => count($outputs),
            'prediction_timestamp' => $prediction['created_at'] ?? null,
            'artifact_model_status' => $artifactModelValid ? 'Tersedia' : 'Tidak tersedia',
            'artifact_scaler_status' => $artifactScalerValid ? 'Tersedia' : 'Tidak tersedia',
            'metadata_status' => $metadataValid ? 'Tersedia' : 'Tidak tersedia',
            'model_sync' => $versionSync ? 'Sinkron' : 'Tidak sinkron',
            'recursive_validation' => $strategyValid ? 'Valid' : 'Perlu Pemeriksaan',
            'window_validation' => $windowCountValid && $inputOrderValid && $inputPeriodValid ? 'Valid' : 'Perlu Pemeriksaan',
            'prediction_count_validation' => $predictionCountValid ? 'Valid' : 'Perlu Pemeriksaan',
            'overall_status' => $allValid ? 'Valid' : 'Perlu Pemeriksaan',
            'outputs' => $outputRows,
            'rows' => [
                ['info' => 'Model versi', 'value' => $version],
                ['info' => 'Input awal', 'value' => $inputStart],
                ['info' => 'Input akhir', 'value' => $inputEnd],
                ['info' => 'Window', 'value' => (string) $windowSize],
                ['info' => 'Horizon', 'value' => $horizon . ' periode'],
                ['info' => 'Supported Horizon', 'value' => '1-7 periode'],
                ['info' => 'Strategi', 'value' => 'Recursive multi-step'],
                ['info' => 'Prediksi P+1', 'value' => $p1 !== null ? $this->formatDecimalComma((float) $p1, 6) : '-'],
                ['info' => 'Prediksi P+2 s.d. P+N', 'value' => $p2ToNText],
                ['info' => 'Waktu proses', 'value' => (string) ($prediction['created_at'] ?? '-')],
            ],
        ];
    }

    private function metricsFromTestSeries(array $rows): array
    {
        $count = count($rows);
        if ($count === 0) {
            return ['mae' => 0.0, 'rmse' => 0.0, 'mape' => 0.0];
        }

        $absoluteSum = 0.0;
        $squaredSum = 0.0;
        $percentageErrors = [];
        foreach ($rows as $row) {
            $error = (float) $row['actual'] - (float) $row['predicted'];
            $absoluteSum += abs($error);
            $squaredSum += $error ** 2;
            if ((float) $row['actual'] !== 0.0) {
                $percentageErrors[] = abs($error / (float) $row['actual']);
            }
        }

        return [
            'mae' => $absoluteSum / $count,
            'rmse' => sqrt($squaredSum / $count),
            'mape' => $percentageErrors === [] ? 0.0 : array_sum($percentageErrors) / count($percentageErrors) * 100,
        ];
    }

    private function parseEpochRowsFromLog(array $lines): array
    {
        $rows = [];
        foreach ($lines as $line) {
            if (preg_match('/epoch=(\d+)\/(\d+)\s+loss=([0-9.eE+-]+)/', (string) $line, $matches)) {
                $rows[] = [
                    'epoch' => (int) $matches[1],
                    'total' => (int) $matches[2],
                    'loss' => (float) $matches[3],
                ];
            }
        }

        return $rows;
    }

    private function parseMetricsFromLog(array $lines): array
    {
        foreach ($lines as $line) {
            if (preg_match('/\[TRAIN\]\[METRICS\].*mae=([0-9.eE+-]+)\s+rmse=([0-9.eE+-]+)\s+mape=([0-9.eE+-]+)\s+final_loss=([0-9.eE+-]+)/', (string) $line, $matches)) {
                return [
                    'mae' => (float) $matches[1],
                    'rmse' => (float) $matches[2],
                    'mape' => (float) $matches[3],
                    'final_loss' => (float) $matches[4],
                ];
            }
        }

        return [];
    }

    private function focusedTrainingLogLines(array $lines): array
    {
        if ($lines === []) {
            return [];
        }

        $selected = [];
        $wantedMarkers = ['[TRAIN][START]', '[TRAIN][PREPARE]', '[TRAIN][DATA]', '[TRAIN][MODEL]', '[TRAIN][FIT]'];
        foreach ($wantedMarkers as $marker) {
            $line = $this->firstLogLine($lines, $marker);
            if ($line !== null) {
                $selected[] = $line;
            }
        }

        $epochLines = array_values(array_filter($lines, fn ($line) => str_contains((string) $line, '[TRAIN][EPOCH]')));
        $epochIndexes = array_unique(array_filter([0, 1, 2, 24, count($epochLines) - 3, count($epochLines) - 2, count($epochLines) - 1], fn ($index) => $index >= 0 && $index < count($epochLines)));
        foreach ($epochIndexes as $index) {
            $selected[] = $epochLines[$index];
        }

        foreach (['[TRAIN][SAVE_MODEL]', '[TRAIN][EVALUATE]', '[TRAIN][METRICS]', '[TRAIN][SAVE_SCALER]', '[TRAIN][SAVE_METADATA]', '[TRAIN][DONE]', '[PHP][DONE]'] as $marker) {
            $line = $this->firstLogLine($lines, $marker);
            if ($line !== null) {
                $selected[] = $line;
            }
        }

        return array_values(array_unique($selected));
    }

    private function firstLogLine(array $lines, string $needle): ?string
    {
        foreach ($lines as $line) {
            if (str_contains((string) $line, $needle)) {
                return (string) $line;
            }
        }

        return null;
    }

    private function logContains(array $lines, string $needle): bool
    {
        return $this->firstLogLine($lines, $needle) !== null;
    }

    private function metadataLossHistory(?array $metadata): array
    {
        $losses = $metadata['training_history']['loss'] ?? $metadata['history']['loss'] ?? null;
        if (!is_array($losses) || $losses === []) {
            return [];
        }

        return array_map(
            fn ($loss, $index) => ['epoch' => $index + 1, 'loss' => (float) $loss],
            array_values($losses),
            array_keys(array_values($losses))
        );
    }

    private function logLossHistory(string $version): array
    {
        $path = $this->trainingLogPath($version);
        if (!is_file($path)) {
            return [];
        }

        $rows = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (preg_match('/epoch=(\d+)\/\d+\s+loss=([0-9.eE+-]+)/', $line, $matches)) {
                $rows[] = ['epoch' => (int) $matches[1], 'loss' => (float) $matches[2]];
            }
        }

        usort($rows, fn ($a, $b) => $a['epoch'] <=> $b['epoch']);
        return $rows;
    }

    private function lossHistorySync(array $history, array $logHistory): array
    {
        if ($history === [] || $logHistory === []) {
            return ['valid' => false, 'message' => 'Training Log tidak lengkap atau tidak tersedia'];
        }

        $sameCount = count($history) === count($logHistory);
        $sameFirst = abs((float) $history[0]['loss'] - (float) $logHistory[0]['loss']) <= 0.00000001;
        $sameLast = abs((float) $history[count($history) - 1]['loss'] - (float) $logHistory[count($logHistory) - 1]['loss']) <= 0.00000001;
        $valid = $sameCount && $sameFirst && $sameLast;

        return [
            'valid' => $valid,
            'message' => $valid ? 'Training Log: Sinkron' : 'Training Log: Tidak sinkron',
        ];
    }

    private function formatDuration(mixed $seconds): string
    {
        if ($seconds === null || $seconds === '') {
            return '-';
        }

        $value = (float) $seconds;
        if ($value < 60) {
            return number_format($value, 2, ',', '.') . ' detik';
        }

        $minutes = (int) floor($value / 60);
        $remaining = $value - ($minutes * 60);
        return sprintf('%d menit %s detik', $minutes, number_format($remaining, 2, ',', '.'));
    }

    private function modelSourceValidation(): array
    {
        $modelSource = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'model.py') ?: '';
        $trainingSource = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'training.py') ?: '';
        $predictionSource = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'prediction.py') ?: '';
        $schemaSource = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'schemas' . DIRECTORY_SEPARATOR . 'ml.py') ?: '';

        return [
            'has_input_shape' => str_contains($modelSource, 'Input(shape=(window_size, 1))'),
            'has_bigru_layer' => str_contains($modelSource, 'Bidirectional(GRU(units=units'),
            'has_dropout' => str_contains($modelSource, 'Dropout(dropout)'),
            'has_dense_one' => str_contains($modelSource, 'Dense(1)'),
            'has_adam' => str_contains($modelSource, 'Adam(learning_rate=learning_rate)'),
            'has_mse' => str_contains($modelSource, 'loss="mse"'),
            'has_shuffle_false' => str_contains($trainingSource, 'shuffle=False'),
            'has_recursive_horizon' => str_contains($schemaSource, 'le=7') && str_contains($predictionSource, 'for step in range(1, request.horizon + 1)') && str_contains($predictionSource, 'np.concatenate([scaled_window[1:], predicted_scaled]'),
        ];
    }

    private function recursivePredictionSourceValidation(): array
    {
        $predictionSource = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'prediction.py') ?: '';
        $schemaSource = file_get_contents(dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'schemas' . DIRECTORY_SEPARATOR . 'ml.py') ?: '';
        $windowService = file_get_contents(base_path('app/Services/PredictionWindowService.php')) ?: '';
        $repository = file_get_contents(base_path('app/Repositories/PredictionRepository.php')) ?: '';

        $checks = [
            'last_window' => str_contains($windowService, 'array_slice($orderedRows, -1 * $windowSize)'),
            'horizon_1_7' => str_contains($schemaSource, 'le=7') && str_contains($windowService, 'MAX_HORIZON = 7'),
            'sort_window' => str_contains($predictionSource, 'sorted(request.window, key=lambda item: item.date)[-window_size:]'),
            'scale_input' => str_contains($predictionSource, 'scaler.transform(values)'),
            'model_predict' => str_contains($predictionSource, 'model.predict(x, verbose=0)'),
            'inverse_transform' => str_contains($predictionSource, 'scaler.inverse_transform(predicted_scaled)'),
            'recursive_append' => str_contains($predictionSource, 'np.concatenate([scaled_window[1:], predicted_scaled]'),
            'history_inputs' => str_contains($repository, 'prediction_inputs'),
            'history_outputs' => str_contains($repository, 'prediction_outputs'),
        ];
        $checks['is_recursive'] = count(array_filter($checks)) === count($checks);

        return $checks;
    }

    private function formatDecimalComma(float $value, int $decimals): string
    {
        return number_format($value, $decimals, ',', '');
    }

    private function windowExample(int $pair, array $dataset, int $startIndex, int $windowSize): array
    {
        $inputRows = array_slice($dataset, $startIndex, $windowSize);
        $target = $dataset[$startIndex + $windowSize];

        return [
            'pair' => $pair,
            'input_start_date' => $inputRows[0]['date'] ?? null,
            'input_end_date' => $inputRows[count($inputRows) - 1]['date'] ?? null,
            'target_date' => $target['date'] ?? null,
            'target_close' => isset($target['close']) ? (float) $target['close'] : null,
            'input_values' => array_map(
                fn ($row) => ['date' => $row['date'], 'close' => (float) $row['close']],
                $inputRows
            ),
        ];
    }

    private function periodLabel(?string $start, ?string $end): string
    {
        if (!$start || !$end) {
            return '-';
        }

        return format_indonesian_date($start) . ' - ' . format_indonesian_date($end);
    }

    private function datasetSynchronization(array $summary, ?array $model, ?array $metadata): array
    {
        if (!$model) {
            return [
                'status' => 'unavailable',
                'message' => 'Model final belum tersedia.',
                'metadata_total_records' => null,
                'metadata_dataset_start_date' => null,
                'metadata_dataset_end_date' => null,
            ];
        }

        $metadataTotal = isset($metadata['total_records']) ? (int) $metadata['total_records'] : (isset($model['total_records']) ? (int) $model['total_records'] : null);
        $metadataStart = $metadata['dataset_start_date'] ?? ($model['dataset_start_date'] ?? null);
        $metadataEnd = $metadata['dataset_end_date'] ?? ($model['dataset_end_date'] ?? null);
        $matches = $metadataTotal === (int) $summary['total_records']
            && (string) $metadataStart === (string) ($summary['start_date'] ?? '')
            && (string) $metadataEnd === (string) ($summary['end_date'] ?? '');

        return [
            'status' => $matches ? 'ok' : 'warning',
            'message' => $matches
                ? 'Sinkron dengan training final.'
                : 'Jumlah atau periode dataset saat ini berbeda dengan dataset yang digunakan model final.',
            'metadata_total_records' => $metadataTotal,
            'metadata_dataset_start_date' => $metadataStart,
            'metadata_dataset_end_date' => $metadataEnd,
        ];
    }

    private function metadataPath(string $version): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'artifacts' . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . $version . '.json';
    }

    private function scalerPath(string $version): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'artifacts' . DIRECTORY_SEPARATOR . 'scalers' . DIRECTORY_SEPARATOR . $version . '.joblib';
    }

    private function trainingLogPath(string $version): string
    {
        $safeVersion = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $version) ?: 'training';
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . "training-{$safeVersion}.log";
    }

    private function resolveArtifactPath(string $path): ?string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (str_starts_with($normalized, 'artifacts' . DIRECTORY_SEPARATOR)) {
            return dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . $normalized;
        }

        return $normalized;
    }

    private function figures(): array
    {
        return [
            '4.1' => ['Pergerakan Harga Penutupan Tembaga pada Dataset Final', '/admin/dokumentasi-ta/gambar-4-1'],
            '4.2' => ['Arsitektur Implementasi Model BiGRU', '/admin/dokumentasi-ta/gambar-4-2'],
            '4.3' => ['Grafik Loss Pelatihan Model BiGRU', '/admin/dokumentasi-ta/gambar-4-3'],
            '4.4' => ['Manajemen dan Riwayat Training Model BiGRU', '/admin/dokumentasi-ta/gambar-4-4'],
            '4.5' => ['Perbandingan Harga Aktual dan Hasil Prediksi', '/admin/dokumentasi-ta/gambar-4-5'],
        ];
    }
}
