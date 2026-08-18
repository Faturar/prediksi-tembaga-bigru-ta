<?php
$formatDate = static fn (?string $value): string => format_indonesian_date($value);
$formatNumber = static fn ($value, int $decimals = 4): string => $value === null ? '-' : number_format((float) $value, $decimals, ',', '.');
$rangeOptions = $priceRangeOptions ?? [];
$selectedRange = $selectedPriceRange ?? '1y';
$labels = array_map(fn ($row) => $row['date'], $priceSeries);
$values = array_map(fn ($row) => (float) $row['close'], $priceSeries);
?>
<section class="page-header">
    <div>
        <p class="eyebrow">Data Historis</p>
        <h1>Harga Tembaga Berdasarkan Data Historis</h1>
        <p>Tampilkan data historis harga tembaga dan grafik Close Price sesuai rentang yang dipilih.</p>
    </div>
    <div class="page-actions">
        <a href="/" class="button-secondary">Beranda</a>
        <a href="/forecast" class="button-primary">Prediksi</a>
    </div>
</section>

<section class="range-tabs">
    <?php foreach ($rangeOptions as $key => $option): ?>
        <a class="<?= $selectedRange === $key ? 'active' : '' ?>" href="/historical?<?= e(http_build_query(['range' => $key])) ?>"><?= e($option['label']) ?></a>
    <?php endforeach; ?>
</section>

<section class="panel">
    <div class="summary-list">
        <div><span>Total Record</span><strong><?= e($priceSummary['total_rows'] ?? 0) ?></strong></div>
        <div><span>Rentang Data</span><strong><?= e(format_indonesian_date($priceSummary['start_date'] ?? null) . ' - ' . format_indonesian_date($priceSummary['end_date'] ?? null)) ?></strong></div>
        <div><span>Close Terakhir</span><strong><?= e($formatNumber($priceSeries[array_key_last($priceSeries)]['close'] ?? null)) ?></strong></div>
        <div><span>Periode Tampilan</span><strong><?= e($selectedPriceRangeLabel) ?></strong></div>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Grafik Historis</p>
            <h2>Pergerakan Close Price</h2>
        </div>
    </div>
    <?php if (empty($labels)): ?>
        <p class="alert">Belum ada data historis untuk ditampilkan. Silakan tunggu Admin mengimpor dataset.</p>
    <?php else: ?>
        <div class="chart-box">
            <canvas id="historicalCloseChart"></canvas>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Ringkasan Tabel</p>
            <h2>Data Harga Tembaga</h2>
        </div>
    </div>
    <?php if (empty($priceSeries)): ?>
        <p class="alert">Tidak ada data untuk ditampilkan.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Open</th>
                        <th>High</th>
                        <th>Low</th>
                        <th>Close</th>
                        <th>Volume</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($priceSeries as $row): ?>
                        <tr>
                            <td><?= e($formatDate($row['date'] ?? null)) ?></td>
                            <td><?= e($formatNumber($row['open'] ?? null)) ?></td>
                            <td><?= e($formatNumber($row['high'] ?? null)) ?></td>
                            <td><?= e($formatNumber($row['low'] ?? null)) ?></td>
                            <td><?= e($formatNumber($row['close'] ?? null)) ?></td>
                            <td><?= e($row['volume'] === null ? '-' : number_format((int) $row['volume'], 0, ',', '.')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php if (!empty($labels)): ?>
<script>
new Chart(document.getElementById('historicalCloseChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels, JSON_THROW_ON_ERROR) ?>,
        datasets: [{
            label: 'Close Price',
            data: <?= json_encode($values, JSON_THROW_ON_ERROR) ?>,
            borderColor: '#c06b32',
            backgroundColor: 'rgba(192,107,50,0.12)',
            tension: 0.25,
            pointRadius: 0,
            fill: true,
        }]
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
