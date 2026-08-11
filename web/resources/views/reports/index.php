<?php
$formatTanggalIndonesia = function (?string $date, bool $withTime = false): string {
    if (!$date) {
        return '-';
    }

    $timestamp = strtotime($date);
    if (!$timestamp) {
        return $date;
    }

    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $formatted = $days[(int) date('w', $timestamp)] . ', ' . date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);

    return $withTime ? $formatted . ' ' . date('H:i', $timestamp) : $formatted;
};

$filterQuery = http_build_query(array_filter([
    'start_date' => $filters['start_date'] ?? null,
    'end_date' => $filters['end_date'] ?? null,
]));
$filterSuffix = $filterQuery ? '&' . $filterQuery : '';

$renderReportPagination = function (array $pagination, string $activeType) use ($filterSuffix): void {
    $pageWindowStart = max(1, ($pagination['page'] ?? 1) - 2);
    $pageWindowEnd = min($pagination['total_pages'] ?? 1, ($pagination['page'] ?? 1) + 2);
    $perPage = $pagination['per_page'] ?? 20;
?>
    <div class="server-pagination">
        <span><?= e($pagination['start'] ?? 0) ?>-<?= e($pagination['end'] ?? 0) ?> dari <?= e($pagination['total_rows'] ?? 0) ?> data</span>
        <form method="get">
            <input type="hidden" name="type" value="<?= e($activeType) ?>">
            <input type="hidden" name="page" value="1">
            <?php if (!empty($_GET['start_date'])): ?><input type="hidden" name="start_date" value="<?= e($_GET['start_date']) ?>"><?php endif; ?>
            <?php if (!empty($_GET['end_date'])): ?><input type="hidden" name="end_date" value="<?= e($_GET['end_date']) ?>"><?php endif; ?>
            <label>Rows
                <select name="per_page" onchange="this.form.submit()">
                    <?php foreach (($pagination['allowed_per_page'] ?? [20, 50, 100]) as $size): ?>
                        <option value="<?= e($size) ?>" <?= (int) $perPage === (int) $size ? 'selected' : '' ?>><?= e($size) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
        <nav class="table-pagination-nav" aria-label="Pagination laporan">
            <a class="table-page-button <?= ($pagination['page'] ?? 1) <= 1 ? 'disabled' : '' ?>" href="/reports?type=<?= e($activeType) ?>&page=<?= e(max(1, ($pagination['page'] ?? 1) - 1)) ?>&per_page=<?= e($perPage) ?><?= e($filterSuffix) ?>">Prev</a>
            <?php if ($pageWindowStart > 1): ?>
                <a class="table-page-button" href="/reports?type=<?= e($activeType) ?>&page=1&per_page=<?= e($perPage) ?><?= e($filterSuffix) ?>">1</a>
                <?php if ($pageWindowStart > 2): ?><span class="table-page-dots">...</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($pageNumber = $pageWindowStart; $pageNumber <= $pageWindowEnd; $pageNumber++): ?>
                    <a class="table-page-button <?= (int) ($pagination['page'] ?? 1) === $pageNumber ? 'active' : '' ?>" href="/reports?type=<?= e($activeType) ?>&page=<?= e($pageNumber) ?>&per_page=<?= e($perPage) ?><?= e($filterSuffix) ?>"><?= e($pageNumber) ?></a>
            <?php endfor; ?>
            <?php if ($pageWindowEnd < ($pagination['total_pages'] ?? 1)): ?>
                <?php if ($pageWindowEnd < ($pagination['total_pages'] ?? 1) - 1): ?><span class="table-page-dots">...</span><?php endif; ?>
                <a class="table-page-button" href="/reports?type=<?= e($activeType) ?>&page=<?= e($pagination['total_pages'] ?? 1) ?>&per_page=<?= e($perPage) ?><?= e($filterSuffix) ?>"><?= e($pagination['total_pages'] ?? 1) ?></a>
            <?php endif; ?>
            <a class="table-page-button <?= ($pagination['page'] ?? 1) >= ($pagination['total_pages'] ?? 1) ? 'disabled' : '' ?>" href="/reports?type=<?= e($activeType) ?>&page=<?= e(min($pagination['total_pages'] ?? 1, ($pagination['page'] ?? 1) + 1)) ?>&per_page=<?= e($perPage) ?><?= e($filterSuffix) ?>">Next</a>
        </nav>
    </div>
<?php
};
?>

<div class="report-toolbar">
    <div class="report-switcher" aria-label="Jenis laporan">
        <?php foreach ($reportTypes as $type => $label): ?>
            <a class="<?= $activeType === $type ? 'active' : '' ?>" href="/reports?type=<?= e($type) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <button onclick="window.print()" class="full-width-button">Print Laporan</button>
</div>

<?php if (in_array($activeType, ['dataset', 'prediction'], true)): ?>
<form method="get" class="grid-form report-filter-form">
    <input type="hidden" name="type" value="<?= e($activeType) ?>">
    <label>Mulai <input type="date" name="start_date" value="<?= e($filters['start_date'] ?? '') ?>"></label>
    <label>Sampai <input type="date" name="end_date" value="<?= e($filters['end_date'] ?? '') ?>"></label>
    <button type="submit">Filter</button>
    <a class="button-secondary" href="/reports?type=<?= e($activeType) ?>">Reset</a>
</form>
<?php endif; ?>

<section class="panel report-document">
    <div class="report-title">
        <div>
            <p class="eyebrow">Dokumen Kampus</p>
            <h2><?= e($activeTitle) ?></h2>
            <?php if ($activeType === 'dataset'): ?>
                <p>Periode data: <?= e($formatTanggalIndonesia($priceSummary['start_date'] ?? null)) ?> sampai <?= e($formatTanggalIndonesia($priceSummary['end_date'] ?? null)) ?></p>
            <?php endif; ?>
        </div>
        <div class="report-print-meta">
            <span>Tanggal Cetak</span>
            <strong><?= e($formatTanggalIndonesia(date('Y-m-d H:i'), true)) ?></strong>
        </div>
    </div>

    <?php if ($activeType === 'dataset'): ?>
        <div class="report-summary-grid">
            <div><span>Total Data</span><strong><?= e(number_format((float) ($priceSummary['total_rows'] ?? 0), 0, ',', '.')) ?></strong><small>baris harga historis</small></div>
            <div><span>Periode Awal</span><strong><?= e($formatTanggalIndonesia($priceSummary['start_date'] ?? null)) ?></strong><small>tanggal data pertama</small></div>
            <div><span>Periode Akhir</span><strong><?= e($formatTanggalIndonesia($priceSummary['end_date'] ?? null)) ?></strong><small>tanggal data terakhir</small></div>
            <div><span>Min Close</span><strong><?= e(number_format((float) ($priceSummary['min_close'] ?? 0), 4)) ?></strong><small>nilai close terendah</small></div>
            <div><span>Max Close</span><strong><?= e(number_format((float) ($priceSummary['max_close'] ?? 0), 4)) ?></strong><small>nilai close tertinggi</small></div>
            <div><span>Rata-rata Close</span><strong><?= e(number_format((float) ($priceSummary['avg_close'] ?? 0), 4)) ?></strong><small>rata-rata seluruh data</small></div>
        </div>

        <h3>Riwayat Import</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>File</th><th>User</th><th>Total</th><th>Valid</th><th>Inserted</th><th>Updated</th><th>Invalid</th><th>Waktu</th></tr></thead>
                <tbody>
                <?php foreach ($imports as $row): ?>
                    <tr>
                        <td data-label="File"><?= e($row['original_filename']) ?></td>
                        <td data-label="User"><?= e($row['user_name'] ?? '-') ?></td>
                        <td data-label="Total"><?= e($row['total_rows']) ?></td>
                        <td data-label="Valid"><?= e($row['valid_rows']) ?></td>
                        <td data-label="Inserted"><?= e($row['imported_rows']) ?></td>
                        <td data-label="Updated"><?= e($row['updated_rows']) ?></td>
                        <td data-label="Invalid"><?= e($row['invalid_rows']) ?></td>
                        <td data-label="Waktu"><?= e($formatTanggalIndonesia($row['created_at'] ?? null, true)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3>Data Harga Terbaru</h3>
        <div class="table-wrap">
            <?php $renderReportPagination($reportPagination, $activeType); ?>
            <table data-no-client-pagination="true">
                <thead><tr><th>Tanggal</th><th>Open</th><th>High</th><th>Low</th><th>Close</th><th>Volume</th><th>Change %</th></tr></thead>
                <tbody>
                <?php foreach ($prices as $row): ?>
                    <tr>
                        <td data-label="Tanggal"><?= e($formatTanggalIndonesia($row['date'] ?? null)) ?></td>
                        <td data-label="Open"><?= e($row['open']) ?></td>
                        <td data-label="High"><?= e($row['high']) ?></td>
                        <td data-label="Low"><?= e($row['low']) ?></td>
                        <td data-label="Close"><?= e($row['close']) ?></td>
                        <td data-label="Volume"><?= e($row['volume']) ?></td>
                        <td data-label="Change %"><?= e($row['change_percent']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($activeType === 'training'): ?>
        <div class="table-wrap">
            <?php $renderReportPagination($reportPagination, $activeType); ?>
            <table data-no-client-pagination="true">
                <thead><tr><th>Version</th><th>Status</th><th>Aktif</th><th>Window</th><th>Units</th><th>Dropout</th><th>Batch</th><th>Epoch</th><th>Learning Rate</th><th>Trained</th></tr></thead>
                <tbody>
                <?php foreach ($models as $row): ?>
                    <tr>
                        <td data-label="Version"><?= e($row['version']) ?></td>
                        <td data-label="Status"><?= e($row['status']) ?></td>
                        <td data-label="Aktif"><?= $row['is_active'] ? 'Ya' : '-' ?></td>
                        <td data-label="Window"><?= e($row['window_size']) ?></td>
                        <td data-label="Units"><?= e($row['units']) ?></td>
                        <td data-label="Dropout"><?= e($row['dropout']) ?></td>
                        <td data-label="Batch"><?= e($row['batch_size']) ?></td>
                        <td data-label="Epoch"><?= e($row['actual_epochs'] ?? $row['configured_epochs']) ?></td>
                        <td data-label="Learning Rate"><?= e($row['learning_rate']) ?></td>
                        <td data-label="Trained"><?= e($formatTanggalIndonesia($row['trained_at'] ?? null, true)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($activeType === 'evaluation'): ?>
        <div class="table-wrap">
            <?php $renderReportPagination($reportPagination, $activeType); ?>
            <table data-no-client-pagination="true">
                <thead><tr><th>Version</th><th>Train</th><th>Test</th><th>Loss</th><th>MAE</th><th>RMSE</th><th>MAPE</th><th>Durasi</th></tr></thead>
                <tbody>
                <?php foreach ($metrics as $row): ?>
                    <tr>
                        <td data-label="Version"><?= e($row['version']) ?></td>
                        <td data-label="Train"><?= e($row['train_samples']) ?></td>
                        <td data-label="Test"><?= e($row['test_samples']) ?></td>
                        <td data-label="Loss"><?= e($row['final_training_loss']) ?></td>
                        <td data-label="MAE"><?= e($row['mae']) ?></td>
                        <td data-label="RMSE"><?= e($row['rmse']) ?></td>
                        <td data-label="MAPE"><?= e($row['mape']) ?></td>
                        <td data-label="Durasi"><?= e($row['training_duration_seconds']) ?> detik</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <?php $renderReportPagination($reportPagination, $activeType); ?>
            <table data-no-client-pagination="true">
                <thead><tr><th>Model</th><th>Window</th><th>Input Awal</th><th>Input Akhir</th><th>Periode Prediksi</th><th>Prediksi Close</th><th>Dibuat</th></tr></thead>
                <tbody>
                <?php foreach ($predictions as $row): ?>
                    <tr>
                        <td data-label="Model"><?= e($row['version']) ?></td>
                        <td data-label="Window"><?= e($row['window_size']) ?></td>
                        <td data-label="Input Awal"><?= e($formatTanggalIndonesia($row['input_start_date'] ?? null)) ?></td>
                        <td data-label="Input Akhir"><?= e($formatTanggalIndonesia($row['input_end_date'] ?? null)) ?></td>
                        <td data-label="Periode Prediksi">Periode berikutnya</td>
                        <td data-label="Prediksi Close"><?= e($row['predicted_close']) ?></td>
                        <td data-label="Dibuat"><?= e($formatTanggalIndonesia($row['created_at'] ?? null, true)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
