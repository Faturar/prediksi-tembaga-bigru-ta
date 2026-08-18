<?php $latestDate=$priceSummary['end_date'] ?? null;
$latestClose= !empty($priceSeries) ? end($priceSeries)['close'] : null;
$activeModelVersion=$activeModel['version'] ?? null;
$activeModelStatus=$activeModel ? 'Tersedia' : 'Tidak tersedia';
$metricSummary=$activeModel ? [ 'mae'=>$activeModel['mae'] ?? null,
'rmse'=>$activeModel['rmse'] ?? null,
'mape'=>$activeModel['mape'] ?? null,
] : null;
$labels=array_map(fn ($row)=> $row['date'], $priceSeries);
$values=array_map(fn ($row)=> (float) $row['close'], $priceSeries);
$formatNumber=static fn ($value, int $decimals=2): string=>$value===null ? '-' : number_format((float) $value, $decimals, ',', '.');
$formatDate = static fn ($value): string => format_indonesian_date($value);
$formatTanggalIndonesia = static fn (?string $date, bool $withTime = false): string => format_indonesian_date($date, $withTime);
?><section class="hero-card">
    <div>
        <p class="eyebrow">PreTem</p>
        <h1>Prediksi harga penutupan tembaga berbasis BiGRU</h1>
        <p>Temukan data historis dan perkiraan prediksi hingga 7 periode perdagangan berikutnya.</p>
        <div class="hero-actions"><a class="button-primary" href="/forecast">Lihat Prediksi</a><a
                class="button-secondary" href="/historical">Lihat Data Historis</a></div>
    </div>
    <div class="hero-stats">
        <div><span>Harga Close
                Terakhir</span><strong><?=e($latestClose !==null ? number_format($latestClose, 4, ',', '.') : '-') ?></strong>
        </div>
        <div><span>Tanggal Terakhir</span><strong><?=e($formatDate($latestDate)) ?></strong></div>
        <div><span>Total Observasi</span><strong><?=e($priceSummary['total_rows'] ?? 0) ?></strong></div>
        <div><span>Status Model Aktif</span><strong><?=e($activeModelStatus) ?></strong></div>
    </div>
</section>
<section class="public-summary-grid">
    <article class="panel">
        <h2>Ringkasan Model Aktif</h2><?php if ($activeModel): ?><div class="summary-list">
            <div><span>Model Version</span><strong><?=e($activeModelVersion) ?></strong></div>
            <div><span>Window Size</span><strong><?=e($activeModel['window_size']) ?></strong></div>
            <div><span>MAE</span><strong><?=e($formatNumber($metricSummary['mae'], 4)) ?></strong></div>
            <div><span>RMSE</span><strong><?=e($formatNumber($metricSummary['rmse'], 4)) ?></strong></div>
            <div><span>MAPE</span><strong><?=e($formatNumber($metricSummary['mape'], 4)) ?>%</strong></div>
        </div>
        <p class="note">Metrik evaluasi dihitung pada data uji secara one-step-ahead.</p><?php else: ?><p>Model aktif
            belum tersedia. Silakan kunjungi halaman Prediksi setelah Admin menyiapkan model.</p><?php endif;
?>
    </article>
    <article class="panel">
        <h2>Ringkasan Data Historis</h2>
        <div class="summary-list">
            <div><span>Periode
                    Data</span><strong><?=e($formatDate($priceSummary['start_date'] ?? null) . ' - '. $formatDate($priceSummary['end_date'] ?? null)) ?></strong>
            </div>
            <div><span>Close
                    Minimum</span><strong><?=e($formatNumber($priceSummary['min_close'] ?? null, 4)) ?></strong></div>
            <div><span>Close
                    Maksimum</span><strong><?=e($formatNumber($priceSummary['max_close'] ?? null, 4)) ?></strong></div>
            <div><span>Rata-rata
                    Close</span><strong><?=e($formatNumber($priceSummary['avg_close'] ?? null, 4)) ?></strong></div>
        </div>
    </article>
</section>
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Grafik Historis</p>
            <h2>Pergerakan Harga Tembaga</h2>
        </div>
    </div><?php if (empty($labels)): ?><p class="alert">Belum ada data historis untuk ditampilkan. Silakan tunggu Admin
        mengimpor dataset.</p><?php else: ?><div class="chart-box"><canvas id="publicHomeChart"></canvas></div><?php endif;
?>
</section><?php if ($latestPrediction): ?><section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Ringkasan Prediksi Terbaru</p>
            <h2>Hasil Prediksi Publik / Terakhir</h2>
        </div>
    </div>
    <div class="summary-list">
        <div><span>Model Version</span><strong><?=e($latestPrediction['version'] ?? '-') ?></strong></div>
        <div><span>Prediksi
                Terakhir</span><strong><?=e(number_format((float) ($latestPrediction['predicted_close'] ?? 0), 4, ',', '.')) ?></strong>
        </div>
        <div><span>Input
                Terakhir</span><strong><?=e($formatTanggalIndonesia($latestPrediction['input_start_date'] ?? null)) ?>
                s.d. <?=e($formatTanggalIndonesia($latestPrediction['input_end_date'] ?? null)) ?></strong></div>
        <div><span>Prediksi Close
                Terbaru</span><strong><?=e($formatTanggalIndonesia($latestPrediction['prediction_date'] ?? null)) ?></strong>
        </div>
        <div><span>Dibuat
                pada</span><strong><?=e($formatTanggalIndonesia($latestPrediction['created_at'] ?? null, true)) ?></strong>
        </div>
    </div>
</section><?php endif;
?><section class="panel disclaimer-panel">
    <h2>Disclaimer</h2>
    <p>Hasil prediksi merupakan hasil pemodelan berdasarkan data historis dan tidak merupakan rekomendasi investasi atau
        keputusan perdagangan.</p>
</section><?php if ( !empty($labels)): ?><script>
const homeChartLabels = <?=json_encode($labels, JSON_THROW_ON_ERROR) ?>;
const homeChartData = <?=json_encode($values, JSON_THROW_ON_ERROR) ?>;

new Chart(document.getElementById('publicHomeChart'), {

        type: 'line',
        data: {

            labels: homeChartLabels,
            datasets: [{
                    label: 'Close Price',
                    data: homeChartData,
                    borderColor: '#c06b32',
                    backgroundColor: 'rgba(192,107,50,0.12)',
                    tension: 0.25,
                    pointRadius: 0,
                    fill: true,
                }

            ]
        }

        ,
        options: {

            responsive: true,
            scales: {
                x: {
                    display: true
                }

                ,
                y: {
                    display: true,
                    beginAtZero: false
                }
            }
        }
    }

);
</script><?php endif;
?>
