<?php
$screenshotMode = (string) ($_GET['screenshot'] ?? '');
$isTableShot = $screenshotMode === 'table';
$isChartShot = $screenshotMode === 'chart';
$statusClass = ($results['status'] ?? '') === 'ok' ? 'ok' : (($results['status'] ?? '') === 'warning' ? 'warn' : '');
$formatPrice = fn ($value, int $decimals = 4) => number_format((float) $value, $decimals, ',', '');
$formatMetric = fn ($value, int $decimals) => $value !== null ? number_format((float) $value, $decimals, '.', '') : '-';
$formatDate = function (?string $date): string {
    if (!$date) {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('d-m-Y', $timestamp) : $date;
};
?>

<div class="thesis-doc-shell research-doc-shell test-results-doc-shell <?= $isTableShot ? 'test-results-table-shot' : '' ?> <?= $isChartShot ? 'test-results-chart-shot' : '' ?>">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-training">Tabel 4.7</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/log-training">Gambar 4.5</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/evaluasi-model">Tabel 4.9</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-test?screenshot=table">Screenshot Tabel</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-test?screenshot=chart">Screenshot Grafik</a>
        <button type="button" id="copyTestResultsTable">Salin Tabel</button>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel test-results-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow"><?= $isChartShot ? 'Gambar 4.6' : 'Tabel 4.8' ?></p>
                <h2><?= $isChartShot ? 'Perbandingan Harga Aktual dan Hasil Prediksi' : 'Hasil Prediksi Data Uji' ?></h2>
                <p class="card-note no-screenshot">Tabel dan grafik memakai test_series model final, bukan forecast operasional 1-7 periode.</p>
            </div>
            <?php if (!empty($results['version'])): ?>
                <span class="thesis-model-chip">Model Version: <?= e($results['version']) ?></span>
            <?php endif; ?>
        </div>

        <div class="research-sync-banner <?= e($statusClass) ?> no-screenshot">
            <strong><?= e($results['message'] ?? 'Test series model final tidak tersedia.') ?></strong>
            <small>Prediction Scale: <?= e($results['prediction_scale'] ?? 'Original Close Price') ?>; validasi metrik dihitung ulang dari seluruh test_series.</small>
        </div>

        <?php if (!empty($results['available'])): ?>
            <?php if (!$isChartShot): ?>
                <section class="test-results-table-section">
                    <div class="test-results-section-head">
                        <div>
                            <p class="eyebrow">Tabel 4.8</p>
                            <h3>Format Hasil Prediksi pada Data Uji</h3>
                        </div>
                        <span>Jumlah total data uji: <?= e((string) $results['test_series_records']) ?>; baris ditampilkan: <?= e((string) $results['displayed_rows']) ?></span>
                    </div>
                    <div class="research-table-wrap">
                        <table id="testResultsTable" class="research-table test-results-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Aktual</th>
                                    <th>Prediksi</th>
                                    <th>Selisih Absolut</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($results['rows'] as $row): ?>
                                <tr>
                                    <td data-label="Tanggal"><strong><?= e($formatDate($row['date'])) ?></strong></td>
                                    <td data-label="Aktual"><?= e($formatPrice($row['actual'], 4)) ?></td>
                                    <td data-label="Prediksi"><?= e($formatPrice($row['predicted'], 4)) ?></td>
                                    <td data-label="Selisih Absolut"><?= e($formatPrice($row['absolute_error'], 6)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="thesis-caption">Tabel 4.8 Format Hasil Prediksi pada Data Uji. Sumber: Hasil prediction test set model final</p>
                </section>
            <?php endif; ?>

            <?php if (!$isTableShot): ?>
                <section class="test-results-chart-section">
                    <div class="test-results-section-head">
                        <div>
                            <p class="eyebrow">Gambar 4.6</p>
                            <h3>Perbandingan Harga Aktual dan Hasil Prediksi</h3>
                        </div>
                        <span><?= e($results['test_period']) ?></span>
                    </div>
                    <div class="thesis-chart-box test-results-chart-box">
                        <canvas id="testPredictionChart"></canvas>
                    </div>
                    <p class="thesis-caption">Gambar 4.6 Perbandingan Harga Aktual dan Hasil Prediksi. Sumber: Dokumen Pribadi (2026)</p>
                </section>
            <?php endif; ?>

            <section class="test-results-detail no-screenshot">
                <h3>Detail Dokumentasi</h3>
                <div class="model-detail-grid">
                    <div><span>Model Version</span><strong><?= e($results['version']) ?></strong></div>
                    <div><span>Model Run ID</span><strong><?= e((string) $results['model_run_id']) ?></strong></div>
                    <div><span>Status</span><strong><?= e(ucfirst($results['model_status'])) ?></strong></div>
                    <div><span>Active</span><strong><?= $results['is_active'] ? 'Ya' : 'Tidak' ?></strong></div>
                    <div><span>Test Start</span><strong><?= e($formatDate($results['test_start_date'])) ?></strong></div>
                    <div><span>Test End</span><strong><?= e($formatDate($results['test_end_date'])) ?></strong></div>
                    <div><span>Test Samples</span><strong><?= e((string) $results['test_samples']) ?></strong></div>
                    <div><span>Test Series Records</span><strong><?= e((string) $results['test_series_records']) ?></strong></div>
                    <div><span>MAE</span><strong><?= e($formatMetric($results['mae'], 6)) ?></strong></div>
                    <div><span>RMSE</span><strong><?= e($formatMetric($results['rmse'], 6)) ?></strong></div>
                    <div><span>MAPE</span><strong><?= e($formatMetric($results['mape'], 4)) ?>%</strong></div>
                    <div><span>Actual Min</span><strong><?= e($formatPrice($results['actual_min'], 4)) ?></strong></div>
                    <div><span>Actual Max</span><strong><?= e($formatPrice($results['actual_max'], 4)) ?></strong></div>
                    <div><span>Predicted Min</span><strong><?= e($formatPrice($results['predicted_min'], 4)) ?></strong></div>
                    <div><span>Predicted Max</span><strong><?= e($formatPrice($results['predicted_max'], 4)) ?></strong></div>
                    <div><span>Mean Absolute Error</span><strong><?= e($formatMetric($results['mean_absolute_error'], 6)) ?></strong></div>
                    <div><span>Prediction Scale</span><strong><?= e($results['prediction_scale']) ?></strong></div>
                    <div><span>Urutan Tanggal</span><strong><?= e($results['date_order_status']) ?></strong></div>
                    <div><span>Duplikat Tanggal</span><strong><?= e((string) $results['duplicate_date_count']) ?></strong></div>
                    <div><span>Status Sinkronisasi</span><strong><?= e($results['metadata_sync']) ?></strong></div>
                </div>
            </section>
        <?php else: ?>
            <div class="empty-chart historical-empty"><strong>Test series model final tidak tersedia.</strong><small>Jalankan training final yang menyimpan metadata test_series untuk menampilkan tabel dan grafik.</small></div>
        <?php endif; ?>
    </section>
</div>

<?php if (!empty($results['available']) && !$isTableShot): ?>
<script>
const testPredictionRows = <?= json_encode($results['chart_rows'], JSON_THROW_ON_ERROR) ?>;
const testPredictionFormatter = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
function formatTestDate(label) {
    const [year, month, day] = String(label).split('-').map(Number);
    const date = year && month && day ? new Date(year, month - 1, day) : new Date(label);
    return Number.isNaN(date.getTime()) ? label : testPredictionFormatter.format(date);
}
function numberLabel(value, decimals = 4) {
    return Number(value).toLocaleString('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
}

new Chart(document.getElementById('testPredictionChart'), {
    type: 'line',
    data: {
        labels: testPredictionRows.map(row => row.date),
        datasets: [
            {
                label: 'Aktual',
                data: testPredictionRows.map(row => Number(row.actual)),
                borderColor: '#1f5f99',
                borderWidth: 2,
                borderDash: [],
                pointRadius: 0,
                fill: false,
                tension: 0
            },
            {
                label: 'Prediksi',
                data: testPredictionRows.map(row => Number(row.predicted)),
                borderColor: '#a15c22',
                borderWidth: 2,
                borderDash: [6, 4],
                pointRadius: 0,
                fill: false,
                tension: 0
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true } },
            tooltip: {
                callbacks: {
                    title(items) {
                        return 'Tanggal: ' + formatTestDate(items[0]?.label || '');
                    },
                    afterBody(items) {
                        const row = testPredictionRows[items[0].dataIndex];
                        return 'Selisih Absolut: ' + numberLabel(row.absolute_error, 6);
                    },
                    label(item) {
                        return item.dataset.label + ': ' + numberLabel(item.parsed.y, 4);
                    }
                }
            }
        },
        scales: {
            x: {
                title: { display: true, text: 'Tanggal data uji' },
                grid: { display: false },
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 12,
                    maxRotation: 0,
                    callback(value) {
                        return formatTestDate(this.getLabelForValue(value));
                    }
                }
            },
            y: {
                title: { display: true, text: 'Close Price' },
                beginAtZero: false,
                grid: { color: 'rgba(17, 24, 39, 0.08)' }
            }
        }
    }
});
</script>
<?php endif; ?>

<script>
const copyTestResultsButton = document.getElementById('copyTestResultsTable');
if (copyTestResultsButton) {
    copyTestResultsButton.addEventListener('click', async () => {
        const rows = Array.from(document.querySelectorAll('#testResultsTable tr'));
        const text = rows.map(row => Array.from(row.children).map(cell => cell.innerText.trim()).join('\t')).join('\n');
        await navigator.clipboard.writeText(text);
        copyTestResultsButton.textContent = 'Tersalin';
        setTimeout(() => copyTestResultsButton.textContent = 'Salin Tabel', 1400);
    });
}
</script>
