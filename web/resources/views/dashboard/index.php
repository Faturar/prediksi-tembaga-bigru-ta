<?php
$priceLabels = array_map(fn ($row) => $row['date'] ?? '', $priceSeries ?? []);
$priceValues = array_map(fn ($row) => (float) ($row['close'] ?? 0), $priceSeries ?? []);
$predictionLabels = array_reverse(array_map(fn ($row) => $row['input_end_date'] ?? '', $predictions));
$predictionValues = array_reverse(array_map(fn ($row) => (float) ($row['predicted_close'] ?? 0), $predictions));
$latestPrice = !empty($priceValues) ? end($priceValues) : null;
$latestPrediction = !empty($predictionValues) ? end($predictionValues) : null;
$datasetStart = $priceSummary['start_date'] ?? null;
$datasetEnd = $priceSummary['end_date'] ?? null;
$chartStart = $priceLabels[0] ?? null;
$chartEnd = !empty($priceLabels) ? end($priceLabels) : null;
?>

<section class="dashboard-welcome">
    <div>
        <p class="eyebrow">Copper futures intelligence</p>
        <h2>Sistem prediksi harga tembaga</h2>
        <p>Pantau kesiapan dataset, model aktif, status layanan ML, dan prediksi terbaru dari satu dashboard.</p>
    </div>
    <div class="welcome-metrics">
        <div>
            <span>Latest Close</span>
            <strong><?= $latestPrice === null ? '-' : e(number_format($latestPrice, 4)) ?></strong>
        </div>
        <div>
            <span>Latest Prediction</span>
            <strong><?= $latestPrediction === null ? '-' : e(number_format($latestPrediction, 4)) ?></strong>
        </div>
    </div>
</section>

<div class="dashboard-strip">
    <div class="strip-card">
        <span class="strip-icon">01</span>
        <div class="strip-content">
            <span class="strip-label">Dataset</span>
            <strong><?= e($priceCount) ?></strong>
            <small><?= $datasetStart && $datasetEnd ? e($datasetStart . ' - ' . $datasetEnd) : 'Baris historis tersimpan' ?></small>
        </div>
        <a href="/prices">Kelola</a>
    </div>
    <div class="strip-card">
        <span class="strip-icon">02</span>
        <div class="strip-content">
            <span class="strip-label">Model Aktif</span>
            <strong><?= e($activeModel['version'] ?? '-') ?></strong>
            <small><?= $activeModel ? 'Siap untuk prediksi' : 'Training belum dijalankan' ?></small>
        </div>
        <a href="/models">Model</a>
    </div>
    <div class="strip-card">
        <span class="strip-icon">03</span>
        <div class="strip-content">
            <span class="strip-label">Metode</span>
            <strong>BiGRU</strong>
            <small>Univariate close price</small>
        </div>
        <a href="/evaluation">Evaluasi</a>
    </div>
    <div class="strip-card">
        <span class="strip-icon <?= $mlStatus === 'ok' ? 'is-good' : 'is-warn' ?>">API</span>
        <div class="strip-content">
            <span class="strip-label">ML Service</span>
            <strong class="service-status">
                <span class="service-dot <?= $mlStatus === 'ok' ? 'ok' : 'warn' ?>"></span>
                <?= $mlStatus === 'ok' ? 'Online' : 'Offline' ?>
            </strong>
            <small>FastAPI endpoint: 127.0.0.1:8001</small>
        </div>
        <a href="/predictions">Prediksi</a>
    </div>
</div>

<div class="dashboard-grid">
    <section class="analytics-card small-card">
        <div class="section-head">
            <div>
                <p class="eyebrow">Dataset</p>
                <h2>Data Coverage</h2>
            </div>
            <span class="trend-badge"><?= e($priceCount) ?> row</span>
        </div>
        <?php if (empty($priceValues)): ?>
        <div class="empty-chart">
            <strong>Belum ada data harga.</strong>
            <small>Import CSV atau input data manual untuk menampilkan chart close price.</small>
            <a href="/import">Import Dataset</a>
        </div>
        <?php else: ?>
        <canvas id="dashboardPriceChart" height="160"></canvas>
        <?php endif; ?>
        <p class="card-note">Chart menampilkan maksimal 30 data
            terbaru<?= $chartStart && $chartEnd ? e(" ({$chartStart} - {$chartEnd})") : '' ?> dari seluruh dataset.</p>
    </section>

    <section class="analytics-card wide-card">
        <div class="section-head">
            <div>
                <p class="eyebrow">Prediction trend</p>
                <h2>Prediksi Close Terbaru</h2>
            </div>
            <span class="trend-badge"><?= count($predictions) ?> data</span>
        </div>
        <?php if (empty($predictionValues)): ?>
        <div class="empty-chart">
            <strong>Belum ada prediksi.</strong>
            <small>Jalankan training model, lalu buat prediksi periode berikutnya.</small>
            <a href="/models">Training Model</a>
        </div>
        <?php else: ?>
        <canvas id="dashboardPredictionChart" height="120"></canvas>
        <?php endif; ?>
    </section>
</div>

<div class="dashboard-grid bottom-grid">
    <section class="analytics-card">
        <div class="section-head">
            <div>
                <p class="eyebrow">Quick action</p>
                <h2>Alur Kerja</h2>
            </div>
        </div>
        <div class="workflow-list">
            <a href="/import"><span>1</span><strong>Import Dataset</strong><small>Unggah CSV harga historis</small></a>
            <a href="/models"><span>2</span><strong>Training Model</strong><small>Latih dan aktifkan model</small></a>
            <a href="/predictions"><span>3</span><strong>Run Prediction</strong><small>Simpan prediksi close</small></a>
            <a href="/reports"><span>4</span><strong>Cetak Laporan</strong><small>Siapkan bukti BAB IV</small></a>
        </div>
    </section>

    <section class="analytics-card">
        <div class="section-head">
            <div>
                <p class="eyebrow">Monitoring</p>
                <h2>Prediksi Terbaru</h2>
            </div>
            <a class="button-secondary" href="/predictions">Lihat Semua</a>
        </div>
        <?php if (empty($predictions)): ?>
        <div class="empty-state">
            <strong>Belum ada prediksi.</strong>
            <p>Training model terlebih dahulu, lalu jalankan prediksi dari menu Prediksi.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal Input</th>
                        <th>Model</th>
                        <th>Prediksi Close</th>
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($predictions as $row): ?>
                    <tr>
                        <td data-label="Tanggal Input"><?= e($row['input_end_date']) ?></td>
                        <td data-label="Model"><?= e($row['version']) ?></td>
                        <td data-label="Prediksi Close"><strong><?= e($row['predicted_close']) ?></strong></td>
                        <td data-label="Dibuat"><?= e($row['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>

<script>
<?php if (!empty($priceValues)): ?>
new Chart(document.getElementById('dashboardPriceChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($priceLabels, JSON_THROW_ON_ERROR) ?>,
        datasets: [{
            label: 'Close Price',
            data: <?= json_encode($priceValues, JSON_THROW_ON_ERROR) ?>,
            backgroundColor: <?= json_encode(array_map(fn ($i) => $i % 2 === 0 ? '#4ade80' : '#202020', array_keys($priceValues)), JSON_THROW_ON_ERROR) ?>,
            borderRadius: 7,
            maxBarThickness: 26
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    maxRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: 5
                }
            },
            y: {
                beginAtZero: false,
                grid: {
                    color: 'rgba(17, 24, 39, 0.08)'
                }
            }
        }
    }
});
<?php endif; ?>

<?php if (!empty($predictionValues)): ?>
new Chart(document.getElementById('dashboardPredictionChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($predictionLabels, JSON_THROW_ON_ERROR) ?>,
        datasets: [{
            label: 'Predicted Close',
            data: <?= json_encode($predictionValues, JSON_THROW_ON_ERROR) ?>,
            borderColor: '#4ade80',
            backgroundColor: 'rgba(74, 222, 128, 0.12)',
            tension: 0.42,
            fill: true,
            pointRadius: 3,
            pointBackgroundColor: '#4ade80'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                }
            },
            y: {
                beginAtZero: false,
                grid: {
                    color: 'rgba(17, 24, 39, 0.08)'
                }
            }
        }
    }
});
<?php endif; ?>
</script>
