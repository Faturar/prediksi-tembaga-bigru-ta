<?php
$metricCount = count($metrics);
$bestMae = $metricCount ? min(array_map(fn ($row) => (float) $row['mae'], $metrics)) : null;
$bestRmse = $metricCount ? min(array_map(fn ($row) => (float) $row['rmse'], $metrics)) : null;
$bestMape = $metricCount ? min(array_map(fn ($row) => (float) $row['mape'], $metrics)) : null;
$bestMaeRow = $metricCount ? array_reduce($metrics, fn ($best, $row) => $best === null || (float) $row['mae'] < (float) $best['mae'] ? $row : $best) : null;
?>

<section class="panel evaluation-summary">
    <div class="section-head">
        <div>
            <p class="eyebrow">Metric</p>
            <h2>Ringkasan Evaluasi</h2>
        </div>
        <a class="button-secondary evaluation-report-link" href="/reports?type=evaluation">Laporan Evaluasi</a>
    </div>
    <div class="evaluation-summary-grid">
        <div class="evaluation-best-card">
            <span>Model MAE Terbaik</span>
            <strong><?= $bestMaeRow ? e($bestMaeRow['version']) : '-' ?></strong>
            <small><?= $bestMaeRow ? 'Window ' . e($bestMaeRow['window_size']) . ' / Units ' . e($bestMaeRow['units']) . ' / Epoch ' . e($bestMaeRow['actual_epochs'] ?? $bestMaeRow['configured_epochs']) : 'Belum ada model yang dievaluasi.' ?></small>
        </div>
        <div class="evaluation-metric-grid">
            <div><span>Total Model</span><strong><?= e($metricCount) ?></strong></div>
            <div><span>MAE</span><strong><?= $bestMae === null ? '-' : e(number_format($bestMae, 6)) ?></strong></div>
            <div><span>RMSE</span><strong><?= $bestRmse === null ? '-' : e(number_format($bestRmse, 6)) ?></strong></div>
            <div><span>MAPE</span><strong><?= $bestMape === null ? '-' : e(number_format($bestMape, 4)) ?><?= $bestMape === null ? '' : '%' ?></strong></div>
        </div>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Test Data</p>
            <h2>Actual vs Predicted</h2>
            <?php if (!empty($selectedModel)): ?>
                <small>Model <?= e($selectedModel['version']) ?><?= $selectedModel['is_active'] ? ' - aktif' : '' ?></small>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($metadataError)): ?>
        <p class="alert"><?= e($metadataError) ?></p>
    <?php elseif (empty($testSeries)): ?>
        <div class="empty-state">
            <strong>Belum ada data Actual vs Predicted.</strong>
            <p>Pastikan model sukses memiliki metadata test_series dari training Python.</p>
        </div>
    <?php else: ?>
        <div class="chart-box evaluation-chart-box">
            <canvas id="actualPredictedChart" height="140"></canvas>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Comparison</p>
            <h2>Perbandingan Hasil Model BiGRU</h2>
        </div>
        <label class="chart-limit-field">Tampilkan
            <select id="metricChartLimit">
                <option value="10">10 model</option>
                <option value="25">25 model</option>
                <option value="all">Semua</option>
            </select>
        </label>
    </div>
    <?php if (empty($metrics)): ?>
        <div class="empty-state">
            <strong>Belum ada metrik evaluasi.</strong>
            <p>Training model terlebih dahulu untuk menampilkan perbandingan MAE, RMSE, dan MAPE.</p>
        </div>
    <?php else: ?>
        <div class="chart-box evaluation-chart-box">
            <canvas id="metricChart" height="120"></canvas>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Detail</p>
            <h2>Detail Metrik Model</h2>
        </div>
    </div>
    <div class="table-wrap evaluation-table-wrap">
        <table>
            <thead><tr><th>#</th><th>Model</th><th>Window</th><th>Units</th><th>Epoch</th><th>Train</th><th>Test</th><th>MAE</th><th>RMSE</th><th>MAPE</th><th>Durasi</th><th>Trained</th></tr></thead>
            <tbody>
            <?php foreach ($metrics as $index => $row): ?>
                <tr>
                    <td data-label="#"><?= e($index + 1) ?></td>
                    <td data-label="Model"><strong><?= e($row['version']) ?></strong></td>
                    <td data-label="Window"><?= e($row['window_size']) ?></td>
                    <td data-label="Units"><?= e($row['units']) ?></td>
                    <td data-label="Epoch"><?= e($row['actual_epochs'] ?? $row['configured_epochs']) ?></td>
                    <td data-label="Train"><?= e($row['train_samples']) ?></td>
                    <td data-label="Test"><?= e($row['test_samples']) ?></td>
                    <td data-label="MAE"><?= e($row['mae']) ?></td>
                    <td data-label="RMSE"><?= e($row['rmse']) ?></td>
                    <td data-label="MAPE"><?= e($row['mape']) ?>%</td>
                    <td data-label="Durasi"><?= e($row['training_duration_seconds']) ?>s</td>
                    <td data-label="Trained"><?= e(format_indonesian_date($row['trained_at'] ?? null, true)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (!empty($metrics)): ?>
<script>
const fullDateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
});
function formatChartFullDate(label) {
    if (!label) {
        return '';
    }

    const [year, month, day] = String(label).split('-').map(Number);
    const date = year && month ? new Date(year, month - 1, day || 1) : new Date(label);
    return Number.isNaN(date.getTime()) ? label : fullDateFormatter.format(date);
}

<?php if (!empty($testSeries)): ?>
const testSeriesRows = <?= json_encode($testSeries, JSON_THROW_ON_ERROR) ?>;
new Chart(document.getElementById('actualPredictedChart'), {
    type: 'line',
    data: {
        labels: testSeriesRows.map(row => row.date),
        datasets: [
            { label: 'Actual', data: testSeriesRows.map(row => Number(row.actual)), borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.08)', tension: 0.25 },
            { label: 'Predicted', data: testSeriesRows.map(row => Number(row.predicted)), borderColor: '#f97316', backgroundColor: 'rgba(249, 115, 22, 0.08)', tension: 0.25 }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    title(items) {
                        return formatChartFullDate(items[0]?.label || '');
                    }
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    autoSkip: true,
                    maxRotation: 0,
                    callback(value) {
                        return formatChartFullDate(this.getLabelForValue(value));
                    }
                }
            },
            y: { beginAtZero: false }
        }
    }
});
<?php endif; ?>

const metricRows = <?= json_encode($metrics, JSON_THROW_ON_ERROR) ?>;
const metricLimit = document.getElementById('metricChartLimit');
const metricContext = document.getElementById('metricChart');
const metricChart = new Chart(metricContext, {
    type: 'bar',
    data: { labels: [], datasets: [] },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            x: { ticks: { autoSkip: true, maxRotation: 0 } },
            y: { beginAtZero: true }
        }
    }
});

function metricChartRows() {
    const sorted = [...metricRows].sort((a, b) => Number(a.mae) - Number(b.mae));
    if (metricLimit.value === 'all') {
        return sorted;
    }
    return sorted.slice(0, Number(metricLimit.value));
}

function renderMetricChart() {
    const rows = metricChartRows();
    metricChart.data.labels = rows.map(row => row.version);
    metricChart.data.datasets = [
        { label: 'MAE', data: rows.map(row => Number(row.mae)), backgroundColor: '#2563eb' },
        { label: 'RMSE', data: rows.map(row => Number(row.rmse)), backgroundColor: '#16a34a' },
        { label: 'MAPE', data: rows.map(row => Number(row.mape)), backgroundColor: '#f97316' }
    ];
    metricChart.update();
}

metricLimit.addEventListener('change', renderMetricChart);
renderMetricChart();
</script>
<?php endif; ?>
