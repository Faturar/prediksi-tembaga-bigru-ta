<?php
$activeModelVersion = $activeModel['version'] ?? null;
$activeWindowSize = $activeModel['window_size'] ?? null;
$activeModelSummary = $activeModel ? [
    'version' => $activeModelVersion,
    'window_size' => $activeWindowSize,
    'mae' => $activeModel['mae'] ?? null,
    'rmse' => $activeModel['rmse'] ?? null,
    'mape' => $activeModel['mape'] ?? null,
] : null;
$forecastResult = $forecastResult ?? null;
$formatNumber = static fn ($value, int $decimals = 4): string => $value === null ? '-' : number_format((float) $value, $decimals, ',', '.');
$forecastLabels = [];
$forecastValues = [];
$historicalLabels = [];
$historicalValues = [];
if (!empty($forecastResult['window'])) {
    foreach ($forecastResult['window'] as $row) {
        $historicalLabels[] = $row['date'];
        $historicalValues[] = (float) $row['close'];
    }
}
if (!empty($forecastResult['result']['predictions'])) {
    foreach ($forecastResult['result']['predictions'] as $item) {
        $forecastLabels[] = 'P+' . $item['step'];
        $forecastValues[] = (float) $item['predicted_close'];
    }
}
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Prediksi Harga Tembaga</p>
        <h1>Prediksi harga penutupan sampai 7 periode perdagangan ke depan</h1>
        <p>Model menggunakan data historis harga penutupan untuk menghasilkan proyeksi hingga 7 periode perdagangan.</p>
    </div>
    <div class="page-actions">
        <a href="/" class="button-secondary">Beranda</a>
        <a href="/historical" class="button-primary">Data Historis</a>
    </div>
</section>

<?php if (!$activeModel): ?>
    <section class="panel">
        <h2>Model Belum Tersedia</h2>
        <p>Model prediksi belum tersedia. Silakan mencoba kembali setelah model selesai disiapkan oleh Admin.</p>
    </section>
<?php else: ?>
    <section class="panel">
        <h2>Model Aktif</h2>
        <div class="summary-list">
            <div><span>Model Version</span><strong><?= e($activeModelSummary['version']) ?></strong></div>
            <div><span>Window Size</span><strong><?= e($activeModelSummary['window_size']) ?> observasi</strong></div>
            <div><span>MAE</span><strong><?= e($formatNumber($activeModelSummary['mae'], 4)) ?></strong></div>
            <div><span>RMSE</span><strong><?= e($formatNumber($activeModelSummary['rmse'], 4)) ?></strong></div>
            <div><span>MAPE</span><strong><?= e($formatNumber($activeModelSummary['mape'], 4)) ?>%</strong></div>
        </div>
        <p class="note">Metrik evaluasi dihitung pada data uji secara one-step-ahead.</p>
    </section>

    <section class="panel">
        <form method="post" action="/forecast" class="forecast-form">
            <?= csrf_field() ?>
            <div class="field-group">
                <label for="horizon">Pilih Horizon</label>
                <select id="horizon" name="horizon" required>
                    <?php for ($i = 1; $i <= 7; $i++): ?>
                        <option value="<?= $i ?>" <?= isset($_POST['horizon']) && (int) $_POST['horizon'] === $i ? 'selected' : '' ?>><?= $i ?> Periode Perdagangan</option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="button-primary">Jalankan Prediksi</button>
        </form>
    </section>

    <?php if (!empty($forecastResult)): ?>
        <section class="panel">
            <h2>Hasil Prediksi</h2>
            <div class="summary-list">
                <div><span>Model Version</span><strong><?= e($forecastResult['model']['version']) ?></strong></div>
                <div><span>Horizon</span><strong><?= e($forecastResult['result']['horizon']) ?> Periode Perdagangan</strong></div>
                <div><span>Window Size</span><strong><?= e($forecastResult['model']['window_size']) ?> observasi</strong></div>
                <div><span>Dibuat pada</span><strong><?= e(format_indonesian_date($forecastResult['created_at'] ?? null, true)) ?></strong></div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Periode</th><th>Prediksi Close</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forecastResult['result']['predictions'] as $prediction): ?>
                            <tr>
                                <td><?= e('P+' . $prediction['step']) ?></td>
                                <td><?= e($formatNumber($prediction['predicted_close'], 4)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Historis dan Proyeksi Harga</p>
                    <h2>Grafik Prediksi</h2>
                </div>
            </div>
            <div class="chart-box">
                <canvas id="forecastChart"></canvas>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php if (!empty($historicalLabels) && !empty($forecastLabels)): ?>
<script>
const forecastChartLabels = <?= json_encode(array_merge($historicalLabels, $forecastLabels), JSON_THROW_ON_ERROR) ?>;
const forecastHistoricalData = <?= json_encode($historicalValues, JSON_THROW_ON_ERROR) ?>;
const forecastValues = <?= json_encode($forecastValues, JSON_THROW_ON_ERROR) ?>;
const forecastPadding = Array(forecastHistoricalData.length).fill(null).concat(forecastValues);
new Chart(document.getElementById('forecastChart'), {
    type: 'line',
    data: {
        labels: forecastChartLabels,
        datasets: [
            {
                label: 'Historis',
                data: forecastHistoricalData,
                borderColor: '#0f766e',
                backgroundColor: 'rgba(13,148,136,0.12)',
                tension: 0.25,
                pointRadius: 0,
                fill: false,
            },
            {
                label: 'Prediksi',
                data: forecastPadding,
                borderColor: '#047857',
                backgroundColor: 'rgba(22,163,74,0.15)',
                tension: 0.25,
                pointRadius: 2,
                borderDash: [6, 4],
                fill: false,
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: { display: true },
            y: { display: true, beginAtZero: false }
        }
    }
});
</script>
<?php endif; ?>