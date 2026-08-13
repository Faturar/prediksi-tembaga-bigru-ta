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
$chartObservationCount = count($priceValues);
$formatDate = static fn (?string $date): string => format_indonesian_date($date);
$formatNumber = static fn (int|float|string|null $value, int $decimals = 0): string => $value === null ? '-' : number_format((float) $value, $decimals, ',', '.');
$rangeOptions = $priceRangeOptions ?? [];
$selectedRange = $selectedPriceRange ?? '1y';
$selectedRangeLabel = $selectedPriceRangeLabel ?? '1 Tahun Terakhir';
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

<section class="analytics-card historical-close-card">
    <div class="section-head historical-chart-head">
        <div>
            <p class="eyebrow"><?= $selectedRange === 'all' ? 'Dataset Historis Keseluruhan' : 'Dataset Historis' ?></p>
            <h2>Pergerakan Harga Penutupan Tembaga</h2>
            <p class="card-note">Visualisasi Close Price berdasarkan data historis yang tersimpan pada sistem.</p>
        </div>
        <?php if (!empty($priceValues)): ?>
        <button type="button" class="button-secondary export-chart-button" id="downloadClosePriceChart">Unduh Grafik PNG</button>
        <?php endif; ?>
    </div>

    <div class="dataset-summary-grid">
        <div>
            <span>Total Data</span>
            <strong><?= e($formatNumber($priceSummary['total_rows'] ?? 0)) ?></strong>
        </div>
        <div>
            <span>Periode Dataset</span>
            <strong><?= e($formatDate($datasetStart) . ' - ' . $formatDate($datasetEnd)) ?></strong>
        </div>
        <div>
            <span>Close Terendah</span>
            <strong><?= e($formatNumber($priceSummary['min_close'] ?? null, 4)) ?></strong>
        </div>
        <div>
            <span>Close Tertinggi</span>
            <strong><?= e($formatNumber($priceSummary['max_close'] ?? null, 4)) ?></strong>
        </div>
    </div>

    <nav class="range-selector" aria-label="Pilih rentang grafik harga penutupan">
        <?php foreach ($rangeOptions as $key => $option): ?>
        <a class="<?= $selectedRange === $key ? 'active' : '' ?>" href="/dashboard?<?= e(http_build_query(['range' => $key])) ?>">
            <?= e($option['label']) ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <?php if (empty($priceValues)): ?>
    <div class="empty-chart historical-empty">
        <strong>Belum ada data harga untuk divisualisasikan.</strong>
        <small>Import CSV atau input data manual untuk menampilkan grafik Close Price.</small>
        <a href="/import">Import Dataset</a>
    </div>
    <?php else: ?>
    <div class="chart-box historical-chart-box">
        <canvas id="dashboardPriceChart"></canvas>
    </div>

    <div class="selected-range-info">
        <div>
            <span>Menampilkan</span>
            <strong><?= e($selectedRangeLabel) ?></strong>
        </div>
        <div>
            <span>Periode</span>
            <strong><?= e($formatDate($chartStart) . ' - ' . $formatDate($chartEnd)) ?></strong>
        </div>
        <div>
            <span>Jumlah Observasi</span>
            <strong><?= e($formatNumber($chartObservationCount)) ?> observasi</strong>
        </div>
    </div>
    <?php endif; ?>
</section>

<div class="dashboard-grid dashboard-secondary-grid">
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
</div>

<div class="dashboard-grid bottom-grid">
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
                        <td data-label="Tanggal Input"><?= e($formatDate($row['input_end_date'])) ?></td>
                        <td data-label="Model"><?= e($row['version']) ?></td>
                        <td data-label="Prediksi Close"><strong><?= e($row['predicted_close']) ?></strong></td>
                        <td data-label="Dibuat"><?= e(format_indonesian_date($row['created_at'] ?? null, true)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>

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

<?php if (!empty($priceValues)): ?>
const closePriceLabels = <?= json_encode($priceLabels, JSON_THROW_ON_ERROR) ?>;
const closePriceValues = <?= json_encode($priceValues, JSON_THROW_ON_ERROR) ?>;
const closePriceDateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
});
const dashboardPriceChart = new Chart(document.getElementById('dashboardPriceChart'), {
    type: 'line',
    data: {
        labels: closePriceLabels,
        datasets: [{
            label: 'Close Price',
            data: closePriceValues,
            borderColor: '#c06b32',
            backgroundColor: 'rgba(192, 107, 50, 0.08)',
            borderWidth: closePriceValues.length > 1200 ? 1.6 : 2.2,
            tension: 0.18,
            fill: true,
            pointRadius: 0,
            pointHoverRadius: 4,
            pointHitRadius: 12
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                display: true,
                labels: {
                    boxWidth: 12,
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    title(items) {
                        const label = items[0]?.label || '';
                        return label ? closePriceDateFormatter.format(new Date(label)) : '';
                    },
                    label(context) {
                        const value = Number(context.parsed.y || 0).toLocaleString('id-ID', {
                            minimumFractionDigits: 4,
                            maximumFractionDigits: 4
                        });
                        return `Close Price: ${value}`;
                    }
                }
            }
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Tanggal'
                },
                grid: {
                    display: false
                },
                ticks: {
                    maxRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: closePriceValues.length > 900 ? 8 : 10,
                    callback(value) {
                        return formatChartFullDate(this.getLabelForValue(value));
                    }
                }
            },
            y: {
                beginAtZero: false,
                title: {
                    display: true,
                    text: 'Close Price'
                },
                grid: {
                    color: 'rgba(17, 24, 39, 0.08)'
                },
                ticks: {
                    callback(value) {
                        return Number(value).toLocaleString('id-ID', {
                            maximumFractionDigits: 4
                        });
                    }
                }
            }
        }
    }
});

document.getElementById('downloadClosePriceChart')?.addEventListener('click', () => {
    const link = document.createElement('a');
    link.href = dashboardPriceChart.toBase64Image('image/png', 1);
    link.download = 'grafik-close-price-tembaga.png';
    link.click();
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
            },
            tooltip: {
                callbacks: {
                    title(items) {
                        return formatChartFullDate(items[0]?.label || '');
                    }
                }
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
                    callback(value) {
                        return formatChartFullDate(this.getLabelForValue(value));
                    }
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
