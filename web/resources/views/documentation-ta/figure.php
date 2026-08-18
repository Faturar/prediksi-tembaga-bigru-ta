<?php
$screenshot = (($_GET['screenshot'] ?? '') === '1');
$current = $figures[$figure] ?? ['', ''];
$datasetCount = count($dataset);
$datasetStart = $dataset[0]['date'] ?? null;
$datasetEnd = $dataset[$datasetCount - 1]['date'] ?? null;
$modelVersion = $finalModel['version'] ?? null;
$formatNumber = fn ($value, int $decimals = 4) => $value !== null && $value !== '' ? number_format((float) $value, $decimals, '.', '') : '-';
$metric = fn ($key, int $decimals = 4) => isset($finalModel[$key]) ? number_format((float) $finalModel[$key], $decimals, '.', '') : '-';
$testCount = count($testSeries);
$testStart = $testSeries[0]['date'] ?? ($finalModel['test_start_date'] ?? null);
$testEnd = $testSeries[$testCount - 1]['date'] ?? ($finalModel['test_end_date'] ?? null);
$modelName = $finalModel['model_name'] ?? null;
?>

<div class="thesis-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-ta">Daftar Gambar</a>
        <?php foreach ($figures as $number => [$label, $path]): ?>
            <a class="button-secondary <?= $figure === $number ? 'active' : '' ?>" href="<?= e($path) ?>">4.<?= e(substr($number, -1)) ?></a>
        <?php endforeach; ?>
        <a class="button-secondary" href="<?= e($current[1]) ?>?screenshot=1">Mode Screenshot</a>
    </div>

    <section class="panel thesis-capture-panel thesis-figure-<?= e(str_replace('.', '-', $figure)) ?>">
        <div class="thesis-title-block">
            <p class="eyebrow">Gambar <?= e($figure) ?></p>
            <h2><?= e($current[0]) ?></h2>
            <?php if ($modelVersion): ?>
                <span class="thesis-model-chip">Model final: <?= e($modelName ?: $modelVersion) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($figure === '4.1'): ?>
            <div class="thesis-meta-grid">
                <div><span>Periode Dataset</span><strong><?= $datasetStart && $datasetEnd ? e(format_indonesian_date($datasetStart) . ' - ' . format_indonesian_date($datasetEnd)) : '-' ?></strong></div>
                <div><span>Jumlah Observasi</span><strong><?= e((string) $datasetCount) ?></strong></div>
                <div><span>Sumber Data</span><strong>copper_prices</strong></div>
            </div>
            <?php if ($datasetCount > 0): ?>
                <div class="thesis-chart-box">
                    <canvas id="figure41Chart"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-chart historical-empty"><strong>Data dataset final belum tersedia.</strong><small>Import data harga tembaga terlebih dahulu.</small></div>
            <?php endif; ?>
            <p class="thesis-caption">Gambar 4.1 Pergerakan Harga Penutupan Tembaga pada Dataset Final</p>

        <?php elseif ($figure === '4.2'): ?>
            <div class="bigru-architecture" aria-label="Arsitektur model BiGRU">
                <div class="arch-node">
                    <span>Input Sequence</span>
                    <strong>Shape: (30, 1)</strong>
                    <small>30 historical Close prices</small>
                </div>
                <div class="arch-arrow">-></div>
                <div class="arch-node arch-bigru">
                    <span>Bidirectional GRU</span>
                    <strong>64 Units</strong>
                    <div class="gru-directions">
                        <small>Forward GRU -></small>
                        <small><- Backward GRU</small>
                    </div>
                </div>
                <div class="arch-arrow">-></div>
                <div class="arch-node">
                    <span>Dropout</span>
                    <strong>Rate = 0.2</strong>
                </div>
                <div class="arch-arrow">-></div>
                <div class="arch-node">
                    <span>Dense</span>
                    <strong>1 Neuron</strong>
                </div>
                <div class="arch-arrow">-></div>
                <div class="arch-node">
                    <span>Predicted Close Price</span>
                    <strong>One-Step-Ahead</strong>
                </div>
            </div>
            <p class="architecture-note">Horizon 1-7 periode dihasilkan menggunakan recursive inference, bukan dengan mengubah Dense(1).</p>
            <p class="thesis-caption">Gambar 4.2 Arsitektur Implementasi Model BiGRU</p>

        <?php elseif ($figure === '4.3'): ?>
            <div class="thesis-meta-grid">
                <div><span>Nama Model</span><strong><?= e($modelName ?? '-') ?></strong></div>
                <div><span>Versi Model</span><strong><?= e($modelVersion ?? '-') ?></strong></div>
                <div><span>Epoch Aktual</span><strong><?= e((string) ($finalModel['actual_epochs'] ?? $finalModel['configured_epochs'] ?? '-')) ?></strong></div>
                <div><span>Batch Size</span><strong><?= e((string) ($finalModel['batch_size'] ?? '-')) ?></strong></div>
                <div><span>Optimizer</span><strong><?= e($finalModel['optimizer'] ?? 'Adam') ?></strong></div>
                <div><span>Loss Function</span><strong><?= e(strtoupper((string) ($finalModel['loss'] ?? 'MSE'))) ?></strong></div>
            </div>
            <?php if (!empty($trainingHistory)): ?>
                <div class="thesis-chart-box">
                    <canvas id="figure43Chart"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-chart historical-empty"><strong>Data hasil training final belum tersedia.</strong><small>Riwayat loss per epoch belum ditemukan pada metadata atau log training model final.</small></div>
            <?php endif; ?>
            <p class="thesis-caption">Gambar 4.3 Grafik Loss Pelatihan Model BiGRU</p>

        <?php elseif ($figure === '4.4'): ?>
            <?php if (!empty($models)): ?>
                <div class="thesis-training-table-wrap">
                    <table class="thesis-training-table">
                        <thead>
                            <tr>
                                <th>Nama Model</th>
                                <th>Versi Model</th>
                                <th>Status</th>
                                <th>Konfigurasi</th>
                                <th>Metrik Evaluasi</th>
                                <th>Dataset</th>
                                <th>Training</th>
                                <th>Aktif</th>
                                <th class="no-screenshot">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($models as $model): ?>
                            <tr class="<?= !empty($model['is_active']) ? 'active-model-row' : '' ?>">
                                <td data-label="Nama Model"><strong><?= e($model['model_name'] ?? 'BiGRU') ?></strong></td>
                                <td data-label="Versi"><?= e($model['version']) ?></td>
                                <td data-label="Status"><span class="status-pill <?= $model['status'] === 'success' ? 'ok' : ($model['status'] === 'running' ? 'warn' : '') ?>"><?= e($model['status']) ?></span></td>
                                <td data-label="Konfigurasi">
                                    W<?= e((string) $model['window_size']) ?>, GRU <?= e((string) $model['units']) ?>, Dropout <?= e((string) $model['dropout']) ?><br>
                                    Batch <?= e((string) $model['batch_size']) ?>, Epoch <?= e((string) ($model['actual_epochs'] ?? $model['configured_epochs'])) ?>, LR <?= e((string) $model['learning_rate']) ?>
                                </td>
                                <td data-label="Metrik">
                                    MAE <?= isset($model['mae']) ? e(number_format((float) $model['mae'], 6, '.', '')) : '-' ?><br>
                                    RMSE <?= isset($model['rmse']) ? e(number_format((float) $model['rmse'], 6, '.', '')) : '-' ?><br>
                                    MAPE <?= isset($model['mape']) ? e(number_format((float) $model['mape'], 4, '.', '')) . '%' : '-' ?>
                                </td>
                                <td data-label="Dataset">
                                    <?= !empty($model['dataset_start_date']) && !empty($model['dataset_end_date']) ? e(format_indonesian_date($model['dataset_start_date']) . ' - ' . format_indonesian_date($model['dataset_end_date'])) : '-' ?><br>
                                    <?= !empty($model['total_records']) ? e((string) $model['total_records']) . ' observasi' : '-' ?>
                                </td>
                                <td data-label="Training">
                                    <?= e(format_indonesian_date($model['trained_at'] ?? null, true)) ?><br>
                                    Train <?= isset($model['train_samples']) ? e((string) $model['train_samples']) : '-' ?> / Test <?= isset($model['test_samples']) ? e((string) $model['test_samples']) : '-' ?>
                                </td>
                                <td data-label="Aktif"><?= !empty($model['is_active']) ? '<span class="model-active-chip">MODEL AKTIF</span>' : '-' ?></td>
                                <td class="no-screenshot" data-label="Aksi"><a class="button-secondary table-action-button" href="/models/detail?id=<?= e($model['id']) ?>">Detail</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-chart historical-empty"><strong>Data hasil training final belum tersedia.</strong><small>Belum ada riwayat model pada tabel model_runs.</small></div>
            <?php endif; ?>
            <p class="thesis-caption">Gambar 4.4 Halaman Manajemen dan Riwayat Training Model</p>

        <?php elseif ($figure === '4.5'): ?>
            <div class="thesis-meta-grid">
                <div><span>Data Uji</span><strong><?= $testStart && $testEnd ? e(format_indonesian_date($testStart) . ' - ' . format_indonesian_date($testEnd)) : '-' ?></strong></div>
                <div><span>Jumlah Sampel Uji</span><strong><?= e((string) $testCount) ?></strong></div>
                <div><span>MAE</span><strong><?= e($metric('mae', 6)) ?></strong></div>
                <div><span>RMSE</span><strong><?= e($metric('rmse', 6)) ?></strong></div>
                <div><span>MAPE</span><strong><?= isset($finalModel['mape']) ? e(number_format((float) $finalModel['mape'], 4, '.', '')) . '%' : '-' ?></strong></div>
            </div>
            <?php if ($testCount > 0): ?>
                <div class="thesis-chart-box">
                    <canvas id="figure45Chart"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-chart historical-empty"><strong>Data hasil training final belum tersedia.</strong><small>Test series one-step-ahead belum ditemukan pada metadata model final.</small></div>
            <?php endif; ?>
            <p class="thesis-caption">Gambar 4.5 Perbandingan Harga Aktual dan Hasil Prediksi</p>
        <?php endif; ?>
    </section>
</div>

<script>
const thesisFormatNumber = value => Number(value).toLocaleString('id-ID', { minimumFractionDigits: 4, maximumFractionDigits: 6 });
const sparseTicks = {
    autoSkip: true,
    maxTicksLimit: 10,
    maxRotation: 0,
};

<?php if ($figure === '4.1' && $datasetCount > 0): ?>
const figure41Data = <?= json_encode($dataset, JSON_THROW_ON_ERROR) ?>;
new Chart(document.getElementById('figure41Chart'), {
    type: 'line',
    data: {
        labels: figure41Data.map(item => item.date),
        datasets: [{
            label: 'Harga Penutupan',
            data: figure41Data.map(item => Number(item.close)),
            borderColor: '#b85c25',
            backgroundColor: 'rgba(184, 92, 37, 0.10)',
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.16,
            fill: true,
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: { callbacks: { label: context => `Close: ${thesisFormatNumber(context.parsed.y)}` } }
        },
        scales: {
            x: { title: { display: true, text: 'Tanggal' }, ticks: sparseTicks, grid: { display: false } },
            y: { title: { display: true, text: 'Close Price' }, grid: { color: 'rgba(17, 24, 39, 0.08)' } }
        }
    }
});
<?php endif; ?>

<?php if ($figure === '4.3' && !empty($trainingHistory)): ?>
const figure43Data = <?= json_encode($trainingHistory, JSON_THROW_ON_ERROR) ?>;
new Chart(document.getElementById('figure43Chart'), {
    type: 'line',
    data: {
        labels: figure43Data.map(item => `Epoch ${item.epoch}`),
        datasets: [{
            label: 'Training Loss',
            data: figure43Data.map(item => Number(item.loss)),
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.10)',
            borderWidth: 2,
            pointRadius: 2,
            tension: 0.18,
            fill: false,
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'top' } },
        scales: {
            x: { title: { display: true, text: 'Epoch' }, ticks: sparseTicks, grid: { display: false } },
            y: { title: { display: true, text: 'Training Loss / MSE' }, grid: { color: 'rgba(17, 24, 39, 0.08)' } }
        }
    }
});
<?php endif; ?>

<?php if ($figure === '4.5' && $testCount > 0): ?>
const figure45Data = <?= json_encode($testSeries, JSON_THROW_ON_ERROR) ?>;
new Chart(document.getElementById('figure45Chart'), {
    type: 'line',
    data: {
        labels: figure45Data.map(item => item.date),
        datasets: [
            {
                label: 'Aktual',
                data: figure45Data.map(item => Number(item.actual)),
                borderColor: '#1d4ed8',
                backgroundColor: 'rgba(29, 78, 216, 0.08)',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.16,
            },
            {
                label: 'Prediksi BiGRU',
                data: figure45Data.map(item => Number(item.predicted)),
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22, 163, 74, 0.08)',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.16,
            }
        ]
    },
    options: {
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: {
                callbacks: {
                    afterBody(items) {
                        const index = items[0]?.dataIndex ?? 0;
                        const row = figure45Data[index];
                        const diff = Math.abs(Number(row.actual) - Number(row.predicted));
                        return `Selisih absolut: ${thesisFormatNumber(diff)}`;
                    }
                }
            }
        },
        scales: {
            x: { title: { display: true, text: 'Tanggal Data Uji' }, ticks: sparseTicks, grid: { display: false } },
            y: { title: { display: true, text: 'Close Price' }, grid: { color: 'rgba(17, 24, 39, 0.08)' } }
        }
    }
});
<?php endif; ?>
</script>
