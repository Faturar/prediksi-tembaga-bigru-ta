<?php
$version = $model['version'];
$modelName = $model['model_name'] ?? 'BiGRU';
$status = $model['status'];
$isActive = $model['is_active'];
$trainedAt = $model['trained_at'];
$windowSize = $model['window_size'];
$units = $model['units'];
$dropout = $model['dropout'];
$batchSize = $model['batch_size'];
$epochCount = $model['actual_epochs'] ?? $model['configured_epochs'];
$learningRate = $model['learning_rate'];
$optimizer = $model['optimizer'] ?? 'Adam';
$loss = $model['loss'] ?? 'MSE';
$datasetStart = $model['dataset_start_date'];
$datasetEnd = $model['dataset_end_date'];
$trainSamples = $model['train_samples'] ?? null;
$testSamples = $model['test_samples'] ?? null;
$trainPercent = $model['train_samples'] && $model['test_samples'] ? round($model['train_samples'] / ($model['train_samples'] + $model['test_samples']) * 100) : 80;
$testPercent = $model['train_samples'] && $model['test_samples'] ? round($model['test_samples'] / ($model['train_samples'] + $model['test_samples']) * 100) : 20;
$finalTrainingLoss = $model['final_training_loss'] ?? null;
$actualEpochs = $model['actual_epochs'] ?? null;
$trainingDuration = $model['training_duration_seconds'] ?? null;
$modelArtifact = basename((string) ($model['model_path'] ?? ''));
$scalerArtifact = basename((string) ($model['scaler_path'] ?? ''));
$metadataPath = basename((string) ($model['metadata_path'] ?? ''));
$mae = $model['mae'] ?? null;
$rmse = $model['rmse'] ?? null;
$mape = $model['mape'] ?? null;
$hasLog = $hasLog ?? false;

$showChart = !empty($testSeries);
?>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Model BiGRU</p>
            <h2><?= e($modelName) ?></h2>
            <p class="card-note">Detail konfigurasi, dataset, evaluasi, dan training.</p>
        </div>
        <div class="training-log-actions">
            <span class="status-pill <?= $status === 'success' ? 'ok' : ($status === 'running' ? 'warn' : '') ?>"><?= e($status === 'success' ? 'Success' : ucfirst($status)) ?></span>
            <?php if ($isActive && $status === 'success'): ?>
                <span class="model-active-chip">Model Aktif</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="model-detail-grid">
        <div><span>Trained at</span><strong><?= e(format_indonesian_date($trainedAt ?? null, true)) ?></strong></div>
        <div><span>Nama Model</span><strong><?= e($modelName) ?></strong></div>
        <div><span>Version</span><strong><?= e($version) ?></strong></div>
        <div><span>Status</span><strong><?= e($status) ?></strong></div>
        <div><span>Aktif</span><strong><?= $isActive ? 'Ya' : 'Tidak' ?></strong></div>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Hasil Evaluasi</p>
            <h2>MAE / RMSE / MAPE</h2>
        </div>
    </div>
    <div class="evaluation-metric-grid">
        <div>
            <span>MAE</span>
            <strong><?= $mae !== null ? e(number_format((float) $mae, 6)) : '-' ?></strong>
            <small>Rata-rata selisih absolut antara nilai aktual dan prediksi.</small>
        </div>
        <div>
            <span>RMSE</span>
            <strong><?= $rmse !== null ? e(number_format((float) $rmse, 6)) : '-' ?></strong>
            <small>Memberikan penalti lebih besar terhadap error yang besar.</small>
        </div>
        <div>
            <span>MAPE</span>
            <strong><?= $mape !== null ? e(number_format((float) $mape, 4)) . '%' : '-' ?></strong>
            <small>Rata-rata kesalahan relatif dalam persentase.</small>
        </div>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Konfigurasi Model</p>
            <h2>Pengaturan Arsitektur</h2>
        </div>
    </div>
    <div class="model-detail-grid">
        <div><span>Window Size</span><strong><?= e($windowSize) ?></strong></div>
        <div><span>GRU Units</span><strong><?= e($units) ?></strong></div>
        <div><span>Dropout</span><strong><?= e($dropout) ?></strong></div>
        <div><span>Batch Size</span><strong><?= e($batchSize) ?></strong></div>
        <div><span>Epoch</span><strong><?= e($epochCount) ?></strong></div>
        <div><span>Learning Rate</span><strong><?= e($learningRate) ?></strong></div>
        <div><span>Optimizer</span><strong><?= e($optimizer) ?></strong></div>
        <div><span>Loss</span><strong><?= e($loss) ?></strong></div>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Dataset</p>
            <h2>Informasi Dataset</h2>
        </div>
    </div>
    <div class="model-detail-grid">
        <div><span>Periode Dataset</span><strong><?= $datasetStart && $datasetEnd ? e(format_indonesian_date($datasetStart) . ' – ' . format_indonesian_date($datasetEnd)) : '-' ?></strong></div>
        <div><span>Total Observations</span><strong><?= $model['total_records'] ? e($model['total_records']) : '-' ?></strong></div>
        <div><span>Training Samples</span><strong><?= $trainSamples !== null ? e($trainSamples) : '-' ?></strong></div>
        <div><span>Testing Samples</span><strong><?= $testSamples !== null ? e($testSamples) : '-' ?></strong></div>
        <div><span>Split</span><strong><?= $trainPercent ?>% Training / <?= $testPercent ?>% Testing</strong></div>
        <div><span>Split Method</span><strong>Kronologis</strong></div>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Metodologi Pengujian</p>
            <h2>Ringkasan Metode</h2>
        </div>
    </div>
    <div class="model-detail-grid">
        <div><span>Split Dataset</span><strong>80% Training / 20% Testing</strong></div>
        <div><span>Urutan Data</span><strong>Kronologis</strong></div>
        <div><span>Normalisasi</span><strong>Min-Max 0–1</strong></div>
        <div><span>Scaler Fit</span><strong>Data training saja</strong></div>
        <div><span>Evaluasi</span><strong>Setelah inverse transform</strong></div>
        <div><span>Skema Pengujian</span><strong>One-step-ahead</strong></div>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Perbandingan Aktual vs Prediksi</p>
            <h2>Grafik Evaluasi</h2>
        </div>
    </div>
    <?php if ($showChart): ?>
        <div class="evaluation-chart-box">
            <canvas id="modelEvaluationChart"></canvas>
        </div>
    <?php else: ?>
        <div class="empty-chart historical-empty">
            <strong>Data grafik evaluasi tidak tersedia untuk model ini.</strong>
            <small>Pastikan metadata test_series tersedia dari ML service untuk model ini.</small>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Informasi Training</p>
            <h2>Ringkasan Training</h2>
        </div>
    </div>
    <div class="model-detail-grid">
        <div><span>Final Training Loss</span><strong><?= $finalTrainingLoss !== null ? e(number_format((float) $finalTrainingLoss, 6)) : '-' ?></strong></div>
        <div><span>Actual Epoch</span><strong><?= $actualEpochs !== null ? e($actualEpochs) : '-' ?></strong></div>
        <div><span>Training Duration</span><strong><?= $trainingDuration !== null ? e(number_format((float) $trainingDuration, 2)) . ' detik' : '-' ?></strong></div>
        <div><span>Model Artifact</span><strong><?= $modelArtifact !== '' ? e($modelArtifact) : '-' ?></strong></div>
        <div><span>Scaler Artifact</span><strong><?= $scalerArtifact !== '' ? e($scalerArtifact) : '-' ?></strong></div>
        <div><span>Metadata File</span><strong><?= $metadataPath !== '' ? e($metadataPath) : '-' ?></strong></div>
    </div>
</section>

<div class="training-log-actions" style="margin-top: 24px;">
    <a class="button-secondary" href="/models">Kembali</a>
    <?php if ($hasLog): ?>
        <a class="button-secondary" href="/models/log?id=<?= e($model['id']) ?>">Lihat Log Training</a>
    <?php endif; ?>
    <?php if ($status === 'success' && !$isActive): ?>
        <form method="post" action="/models/activate" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($model['id']) ?>">
            <button type="submit">Aktifkan Model</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($showChart): ?>
<script>
const evaluationData = <?= json_encode($testSeries, JSON_THROW_ON_ERROR) ?>;
const labels = evaluationData.map(item => item.date);
const actualValues = evaluationData.map(item => Number(item.actual));
const predictedValues = evaluationData.map(item => Number(item.predicted));

new Chart(document.getElementById('modelEvaluationChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            {
                label: 'Aktual',
                data: actualValues,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.12)',
                tension: 0.2,
                fill: false,
                pointRadius: 2,
            },
            {
                label: 'Prediksi BiGRU',
                data: predictedValues,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22, 163, 74, 0.12)',
                tension: 0.2,
                fill: false,
                pointRadius: 2,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    title(items) {
                        return items[0]?.label || '';
                    }
                }
            }
        },
        scales: {
            x: {
                title: { display: true, text: 'Tanggal data uji' },
                grid: { display: false }
            },
            y: {
                title: { display: true, text: 'Close Price' },
                grid: { color: 'rgba(17, 24, 39, 0.08)' }
            }
        }
    }
});
</script>
<?php endif; ?>
